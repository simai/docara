---
extends: _core._layouts.documentation
section: content
title: Многоязычия
description: Многоязычия
---

# Многоязычная поддержка в документации

Ваша система документирования построена с первоклассной многоязычной поддержкой, основанной на `Configurator` class и
Соглашения о структурированных папках.

## Структура папок для каждого языкового стандарта

Для каждой локали есть своя папка в папке:

```bash
source/_docs-<locale>/
```

> Примеры:

```bash
source/_docs-ru/
source/_docs-en/
source/_docs-de/
source/_docs-es/
```

Каждая папка может содержать:
!links

- `.settings.php` — метаданные страницы (заголовки, порядок, пользовательские меню)

- `.lang.php` — словарь перевода UI-фраз

- `index.md`, `intro.md`и т.д. — актуальное содержание документации

!endlinks

## Локальная конфигурация

В вашем `config.php`, локали определяются следующим образом:

```php
'locales' => [
'en' => 'English',
'ru' => 'Русский',
'de' => 'Deutsch',
'es' => 'Spanish',
],
'defaultLocale' => 'ru',
```

Это обеспечивает:
!links

- Карта кодов локалей к их названиям

- Локаль по умолчанию, к которой можно вернуться

!endlinks

## Сборка с учетом локали с помощью конфигуратора

Когда Jigsaw начинает сборку: (.test1.test2.test3)

1. Тем `Configurator` инициализируется списком локали:

    ```php
    
    $locales = $jigsaw->getConfig('locales');
    $configurator = new Configurator($locales);
    $jigsaw->setConfig('configurator', $configurator);
    ```
2. Для каждой локали: (.links)
    - Он загружается `.lang.php` в `$translations[$locale]` (.test)
    - Он загружает все `.settings.php` в `$settings[$locale]`
    - Он строит:
        - Вложенное меню: `$menu[$locale]`
        - Плоское меню: `$flattenMenu[$locale]`
        - Все страницы: `$realFlatten[$locale]`

    
## Переводы через `.lang.php`

Каждая папка локали может содержать файл `.lang.php` файл:

```php
return [
    'home' => 'Главная',
    'installation' => 'Установка',
    'next' => 'Далее',
    'prev' => 'Назад',
];
```

Затем, в шаблонах:

```php
{{ $page->translate('home') }}
```

За кулисами:

```php
$page->configurator->getTranslate('home', $page->locale());
```

## Маршрутизация с учетом языка

Благодаря структуре папок и `Configurator` логика:
!links

- URL-адреса имеют префикс в соответствии с языковым стандартом, например:

    ```bash
    /ru/guide/install
    /en/guide/install
    ```
- Локаль автоматически извлекается в `config.php` дорога:

    ```php
    'locale' => function ($page) {
        $path = str_replace('\\', '/', $page->getPath());
        $locale = explode('/', $path);
        $current = 'ru';
        $locales = array_keys($page->locales->toArray());
        foreach ($locale as $segment) {
            if (in_array($segment, $locales)) {
                $current = $segment;
                break;
            }
        }
        return $current;
    },
    ```

!endlinks

Это означает, что локаль выводится из пути URL-адреса или расположения файла.

## Меню и навигация для конкретных языков

> Все меню хранятся для каждого региона:

```php 
$page->configurator->getMenu($page->locale())         // nested sidebar menu
$page->configurator->realFlatten[$page->locale()]     // all pages
$page->configurator->flattenMenu[$page->locale()]     // navigable pages
```

> И для каждого локаля предыдущий/следующий:

```php 
$page->configurator->getPrevAndNext($page->getPath(), $page->locale());
```
> Хлебные крошки также локализованы:

```php

$page->configurator->generateBreadCrumbs($locale, $segments);
```


