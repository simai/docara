---
extends: _core._layouts.documentation
section: content
title: 'Прослушиватели событий'
description: 'Прослушиватели событий'
---

# Прослушиватели событий

Jigsaw предоставляет три события, к которым вы можете подключиться, чтобы запустить пользовательский код до и после завершения сборки
обработанный.
!links

- ***A `beforeBuild` срабатывает раньше любого `source` Файлы были обработаны.*** Это дает вам возможность
  Программная модификация `config.php` переменные, извлекать данные из внешних `sources`или изменить файлы в исходном коде
  папка.

- ***Ан `afterCollections` Событие запускается после обработки всех коллекций, но до того, как будут обработаны какие-либо выходные файлы
  Построен.***
  Это дает вам доступ к проанализированному содержимому элементов коллекции.

- ***Ан `afterBuild` запускается после завершения сборки и записи всех выходных файлов в файл `build`
   каталог.*** Это позволяет получить список путей к выходным файлам (для использования, например, при создании
  `sitemap.xml`
  файлов), программно создавать выходные файлы или выполнять любые другие задачи постобработки.

!endlinks

---

## Регистрация прослушивателей событий в качестве замыканий

Чтобы добавить прослушиватель событий, перейдите в раздел `bootstrap.php`. Там вы можете получить доступ к автобусу мероприятия с помощью `$events` переменная
Добавление слушателей путем вызова названия события:

> bootstrap.php

```php 
$events->beforeBuild(function ($jigsaw) {
// your code here
});

$events->afterCollections(function ($jigsaw) {
// your code here
});

$events->afterBuild(function ($jigsaw) {
// your code here
});
```

В простейшем случае вы можете определить прослушиватели событий как замыкания, которые принимают экземпляр `Jigsaw`. Тем `Jigsaw`
пример
содержит ряд вспомогательных методов, позволяющих получить доступ к информации о сайте и взаимодействовать с файлами и конфигурацией
 Параметры.

Например, следующий прослушиватель получит текущую погоду из внешнего API и добавит ее в качестве переменной в
`config.php`, где на него можно ссылаться в ваших шаблонах:

> bootstrap.php

```php
$events->beforeBuild(function ($jigsaw) {
$url = "http://api.openweathermap.org/data/2.5/weather?" . http_build_query([
'q' => $jigsaw->getConfig('city'),
'appid' => $jigsaw->getConfig('openweathermap_api_key'),
'units' => 'imperial',
]);

    $jigsaw->setConfig('current_weather', json_decode(file_get_contents($url))->main);

});
```

---

## Регистрация прослушивателей событий в качестве классов

Для более сложных прослушивателей событий можно указать имя класса или массив имен классов вместо замыкания.
Эти классы могут либо находиться непосредственно в `bootstrap.php` или в отдельную директорию. Классы прослушивателя должны подсчитывать
`handle()` принимает экземпляр `Jigsaw`:

> bootstrap.php

```php 
$events->afterBuild(GenerateSitemap::class);

$events->afterBuild([GenerateSitemap::class, SendNotification::class]);
```

> слушатели/GenerateSitemap.php

```php 
<?php

namespace App\Listeners;

use TightenCo\Jigsaw\Jigsaw;
use samdark\sitemap\Sitemap;

class GenerateSitemap
{
    public function handle(Jigsaw $jigsaw)
    {
        $baseUrl = $jigsaw->getConfig('baseUrl');
        $sitemap = new Sitemap($jigsaw->getDestinationPath() . '/sitemap.xml');

        collect($jigsaw->getOutputPaths())->each(function ($path) use ($baseUrl, $sitemap) {
            if (! $this->isAsset($path)) {
                $sitemap->addItem($baseUrl . $path, time(), Sitemap::DAILY);
            }
        });

        $sitemap->write();
    }

    public function isAsset($path)
    {
        return starts_with($path, '/assets');
    }
}
```

Если для одного события определено несколько прослушивателей, они будут запускаться в том порядке, в котором они были определены.

Чтобы вызвать класс прослушивателя, который находится в отдельном каталоге, пространство имен класса должно быть добавлено в `composer.json`
 файл:

> composer.json

```json 
{
  "autoload": {
    "psr-4": {
      "App\\Listeners\\": "listeners"
    }
  }
}
```

---

## Вспомогательные методы в $jigsaw

Экземпляр `Jigsaw` Доступный каждому прослушивателю событий включает в себя следующие вспомогательные методы:
---
`getEnvironment()`

Возвращает текущую среду, например: `local` или `production`

---
`getCollections()`

В `beforeBuild`возвращает массив имен коллекций; в ***afterCollections*** и ***afterBuild***возвращает коллекцию элементов коллекции с ключом по имени коллекции.

---
`getCollection($collection)` (***afterCollections*** и ***afterBuild*** только)

Возвращает элементы в определенной коллекции, заданные по их ключу `source` Имена. Каждый элемент содержит переменные
определенный для элемента коллекции, а также доступ ко всем методам элемента коллекции, таким как `getContent()`.

---
`getConfig()`

Возвращает массив настроек из `config.php`

---
`getConfig($key)`

Возвращает определенную настройку из `config.php`.

Точечная нотация (например, `getConfig('collections.posts.items')` может быть использован для получения вложенных элементов.

---
`setConfig($key, $value)`

Добавление или изменение параметра в config.php.
Точечная нотация может использоваться для установки вложенных элементов.

---
`getSourcePath()`

Возвращает абсолютный путь к `source` каталог

---
`setSourcePath($path)`

Задает путь к `source` каталог

---
`getDestinationPath()`

Возвращает абсолютный путь к `build` каталог

---
`setDestinationPath($path)`

Задает путь к `build` каталог

---
`getPages()` (***afterBuild*** только)

Возвращает коллекцию всех созданных выходных файлов. Для каждого элемента ключ содержит путь к выходу
относительно файла `build` (например, `/posts/my-first-post`), в то время как значение содержит содержимое элемента
`$page`
для исходного файла. Это открывает доступ к функциям метаданных страницы, таким как `getPath()` и `getModifiedTime()` для
каждый
page, а также любые переменные, определенные в YAML-заголовке страницы.

---
`getOutputPaths()` (***afterBuild*** только)

Возвращает коллекцию путей к выходным файлам, которые были созданы, относительно метода `build` каталог

---
`readSourceFile($fileName)`

Возвращает содержимое файла в папке `source` каталог

---
`writeSourceFile($fileName, $contents)`

Позволяет записывать файл в файл `source` каталог

---
`readOutputFile($fileName)`

Возвращает содержимое файла в папке `build` каталог

---
`writeOutputFile($fileName, $contents)`

Позволяет записывать файл в файл `build` каталог
