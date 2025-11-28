---
extends: _core._layouts.documentation
section: content
title: 'Регистрация пользовательских тегов'
description: 'Регистрация пользовательских тегов'
---

# Регистрация пользовательских тегов

На этой странице описано, как добавить новый пользовательский тег Markdown в проект и сделать его доступным для сборки Jigsaw.

---

## Необходимые условия
- Класс тега должен расширяться `App\Helpers\CommonMark\BaseTag` (или внедрить `CustomTagInterface`).
- Автозагрузка Composer сопоставляет `App\` namespace в ваш `source/_core` дерево.
- Сборка Jigsaw использует наш пользовательский `Parser` с помощью функции `CustomTagExtension` установленный (см. **Проводка лобзика** ниже).

---

## Шаг 1 — Создание класса тегов
Поместите класс в раздел `App\Helpers\CustomTags` и вернуть уникальный `type()`.

```php
<?php

namespace App\Helpers\CustomTags;

use App\Helpers\CommonMark\BaseTag;

final class ExampleTag extends BaseTag
{
    public function type(): string { return 'example'; }

    public function baseAttrs(): array
    {
        return ['class' => 'example overflow-hidden radius-1/2 overflow-x-auto'];
    }
}
```

**Примечания**
- `type()` — маркер, используемый в Markdown (`!example` … `!endexample`).
- `baseAttrs()` предоставляет атрибуты по умолчанию; Атрибуты, предоставленные автором, объединяются (классы объединяются и дедуплицируются).

---

## Шаг 2 — Объявите тег в `config.php`
Перечислите **короткий** Имена классов (без пространства имен) в разделе `tags` массив. Каждое короткое имя разрешается в `App\\Helpers\\CustomTags\\<ShortName>`.

```php
<?php

return [
    'tags' => [
        'ExampleTag',
        // 'CalloutTag', 'VideoTag', ...
    ],
];
```

---

## Шаг 3 — Проводка лобзика (bootstrap.php)
Наш `bootstrap.php` Связывает реестр тегов с помощью метода `tags` из конфига и поменяет местами парсер front matter Jigsaw на наш кастомный `Parser`.

```php
<?php

/** @var $container \Illuminate\Container\Container */
/** @var $events \TightenCo\Jigsaw\Events\EventBus */

use App\Helpers\CommonMark\CustomTagRegistry;
use App\Helpers\Interface\CustomTagInterface;
use App\Helpers\Parser;
use App\Helpers\Tags\TagRegistry;
use TightenCo\Jigsaw\Parsers\FrontMatterParser;

try {
    $container->bind(CustomTagRegistry::class, function ($c) {
        $namespace = 'App\\Helpers\\CustomTags\\';
        $shorts = (array) $c['config']->get('tags', []);
        $instances = [];
        foreach ($shorts as $short) {
            $class = $namespace . $short;
            if (class_exists($class)) {
                $obj = new $class(); // If you need DI, see the tip below
                if ($obj instanceof CustomTagInterface) $instances[] = $obj;
            }
        }
        return TagRegistry::register($instances);
    });
} catch (\ReflectionException $e) {
    // optionally log
}

try {
    $container->bind(FrontMatterParser::class, Parser::class);
} catch (\ReflectionException $e) {
    // optionally log
}
```

**Наконечник (опционально DI)**: Если тегу требуются зависимости конструктора, замените `new $class()`с `$c->make($class)` , чтобы контейнер разрешил их.

---

## Шаг 4 — Перестройка
Перегенерируйте автозагрузки и соберите сайт:

```bash
composer dump-autoload
vendor/bin/jigsaw build
```

При использовании `serve`, перезапустите его после добавления новых классов.

---

## Шаг 5 — Проверьте с помощью приспособления
Создайте быстрый сниппет Markdown и подтвердите вывод:

```md
!example class:"mb-4 border" data-x=42 .demo #hello
**Inside** the example tag.
!endexample
```

Ожидается (упрощённо):

```html
<div id="hello" class="example overflow-hidden radius-1/2 overflow-x-auto mb-4 border demo" data-x="42">
  <p><strong>Inside</strong> the example tag.</p>
</div>
```

!example class:"mb-4 border" data-x=42 .demo #hello
**Внутри ** Пример тега.
!endexample

---

## Как происходит регистрация под капотом
1. **Конфигурация**: `config('tags')` списки тегов короткие имена.
2. **Привязка к реестру**: `bootstrap.php` создает экземпляры этих классов и регистрирует их через `TagRegistry::register(...)`.
3. **Привязка парсера**: Пазл `FrontMatterParser` имеет псевдоним нашего `Parser`, который устанавливает `CustomTagExtension` в среду CommonMark.
4. **Разбор**: `UniversalBlockParser` спички `openRegex()`/`closeRegex()` для каждого зарегистрированного тега и сборки `CustomTagNode` АСТ.
5. **Перевод**: `CustomTagRender` объединяет атрибуты, применяет `attrsFilter()`, а либо вызывает `renderer()` или выдает обёртку по умолчанию (`htmlTag()`).

---

## Включение/отключение по среде (опционально)
Вы можете выполнить ветвление от environment, чтобы включить экспериментальные теги только в `dev`:

```php
$container->bind(CustomTagRegistry::class, function ($c) {
    $namespace = 'App\\Helpers\\CustomTags\\';
    $shorts = (array) $c['config']->get('tags', []);

    // Example: filter based on an env flag in config
    $env = $c['config']->get('env'); // adapt to how you expose environment
    if ($env !== 'production') {
        $shorts[] = 'ExperimentalTag';
    }

    // ...instantiate as shown above
});
```

(Настройте способ отображения сред в соответствии с вашим проектом.)

---

## Распространенные подводные камни
- **Класс не найден**:Бежать `composer dump-autoload`, проверьте пространство имен и путь к файлу в разделе `source/_core/helpers`.
- **Не зарегистрирован**: Краткое название в `config('tags')` должен точно совпадать с базовым именем класса.
- **Дублировать `type()`**: Убедитесь, что каждый тег `type()` является уникальным; в противном случае побеждает первый.
- **Неправильный HTML**:Проверка `htmlTag()`/`renderer()` и подтвердите `attrsFilter()` не лишает ценности.
- **Атрибуты не анализируются**: Убедитесь, что в строке атрибута используются обычные кавычки/пробелы; наш парсер нормализует пробелы/кавычки в Юникоде, но подтверждает, что ввод находится в поле **Открытая линия**.

---

## Отмена регистрации тега
- Удалите его короткое название из `config('tags')`.
- Перестройте сайт. Тег больше не будет распознаваться во время синтаксического анализа.

---

## Быстрый чек-лист
- [ ] Класс в `App/Helpers/CustomTags` растягивающий `BaseTag`
- [ ] Уникальный `type()`
- [ ] Добавлено в `config.php => tags`
- [ ] Обновлена автозагрузка компоновщика (`composer dump-autoload`)
- [ ] `bootstrap.php` Binds registry и custom `Parser`
- [ ] Перезапуск сборки/обслуживания пазла (`vendor/bin/jigsaw build` или перезапустить `serve`)
- [ ] Атрибуты правильно разбираются (в кавычках/без кавычек, `.class`, `#id`)
- [ ] Опционально: `attrsFilter()` Добавлено для нормализации/белого списка
- [ ] Опционально: `renderer()` реализовано для пользовательского HTML
- [ ] Необязательно: подтвердить `allowNestingSame()` поведение
- [ ] Страница прибора отображается должным образом


