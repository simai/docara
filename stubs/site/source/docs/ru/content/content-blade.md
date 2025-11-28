---
extends: _core._layouts.documentation
section: content
title: 'Шаблоны и частичные лезвия'
description: 'Шаблоны и частичные лезвия'
---

# Шаблоны и частичные лезвия

Одним из самых больших преимуществ языка шаблонов является возможность создания многократно используемых макетов и частичных макетов. Пазл
предоставляет вам доступ ко всем функциям шаблонов и структурам управления Blade, доступным в Laravel (узнать больше
подробнее о макетах Blade в официальной документации Blade).

## Определение макета

Сами макеты — это просто базовые шаблоны Blade, которые имеют один или несколько вызовов @yield, для которых дочерние шаблоны могут отображаться
их содержание.

Базовый мастер-макет может выглядеть следующим образом:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>The Amazing Web</title>
</head>
<body>
<header>
    My Amazing Site
</header>

@yield('content')

<footer>
    <p>©{{ date('Y') }} Awesome Co</p>
</footer>
</body>
</html>
```

Лобзик предоставляет `/source/_core/_layouts` Директория из коробки с базовым мастер-макетом.

## Расширение макета

Чтобы расширить макет, создайте шаблон, указывающий, какой макет следует расширить в `@extends` директива, и которая
Раздел(ы) для заполнения с помощью команды `@section` директива:

```blade

@extends('_core._layouts.master')

@section('content')
<div>
    <p>The contents of my amazing homepage.</p>
</div>
@endsection
```

Ссылки на макеты и партиалы относятся к каталогу `source` с использованием точечной нотации, где каждая точка представляет собой
в имени файла и `.blade.php` Расширение опущено.

## Частичные

Чтобы включить шаблон в другой шаблон, используйте метод `@include` директива:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>The Amazing Web</title>
</head>
<body>
@include('_partials.header')

@yield('content')

@include('_partials.footer')
</body>
</html>
```

Вы можете передать данные в частичный, передав ассоциативный массив в качестве второго параметра:

```blade
<!DOCTYPE html>
<html>
<head>
    <title>The Amazing Web</title>
</head>
<body>
@include('_partials.header', ['page_title' => 'My Amazing Site'])

@yield('content')

@include('_partials.footer')
</body>
</html>
```

Эти данные затем доступны в вашем partial как обычная переменная:

```blade
<!-- _partials/header.blade.php -->
<header>
    {{ $page_title }}
</header>
```

## Компоненты

Лобзик поддерживает как классовые, так и анонимные компоненты Blade.

Чтобы отобразить компонент, можно использовать тег компонента Blade в одном из шаблонов Blade. Начало тегов компонентов блейд-серверов
со строкой x-, за которой следует имя регистра kebab класса компонента:

```blade

<x-input/>
```

В Jigsaw виды автоматически обнаруживаются из команды `source/_core/_components` каталог; Чтобы создать анонимный `<x--style`
компонентов, вам нужно только поместить шаблон Blade в эту директорию.

Компоненты, основанные на классах, могут быть зарегистрированы вручную с помощью $bladeCompiler->component(), как описано в разделе Расширение блейда
с пользовательскими директивами в разделе ниже; или они могут быть автоматически обнаружены с помощью пространства имен Components. Для автозагрузки
компоненты на основе классов, использующие пространство имен Components, добавляют запись автозагрузки в файл composer.json:

> composer.json

```json
"autoload": {
  "psr-4": {
  "Components": "where/you/want/to/keep/component/classes/"
  }
}
```

… а затем обновите автозагрузку ссылок Composer, выполнив `composer dump-autoload` в терминале.

## Предотвращение рендеринга макетов, фрагментов и компонентов

Поскольку важно, что макеты, частичные разделы и компоненты никогда не визуализируются сами по себе, вы должны быть в состоянии определить,
Пазл, когда файл не должен быть отрисован.

Чтобы предотвратить рендеринг файла или папки, просто добавьте к ним префикс подчеркивания:

<div class="files">
    <div class="folder folder--open">source
        <div class="folder folder--open">_core
        <div class="folder">_assets</div>
        <div class="folder folder--open focus">_layouts
            <div class="file">master.blade.php</div>
        </div>
        </div>
        <div class="folder">assets</div>
        <div class="file">index.blade.php</div>
    </div>
    <div class="ellipsis">...</div>
</div>

Пазл дает вам `/_layouts` директория по умолчанию, но вы можете создать любые файлы или директории, которые вам нужны; что-нибудь
с префиксом подчеркивания не будет отображаться непосредственно в `/build_local`.

Например, если вам нужно место для хранения всех ваших частичных данных, вы можете создать каталог с именем `_partials`:

<div class="files">
    <div class="folder folder--open">source
        <div class="folder folder--open">_core
        <div class="folder">_assets</div>
        <div class="folder">_layouts</div>
        <div class="folder folder--open focus">_partials
            <div class="file">footer.blade.php</div>
            <div class="file">header.blade.php</div>
        </div>
        </div>
        <div class="folder">assets</div>
        <div class="file">index.blade.php</div>
    </div>
    <div class="ellipsis">...</div>
</div>

Так как `_partials` начинается с символа подчеркивания, эти файлы не будут отображаться при создании сайта,
но по-прежнему будет доступен для @include в других ваших шаблонах.

## Расширение Blade с помощью пользовательских директив

Лобзик дает вам возможность удлинять лезвие
с [Пользовательские директивы](https://laravel.com/docs/12.x/blade#extending-blade), так же как и с Laravel. Для этого
Создайте файл
`blade.php` файл на корневом уровне вашего проекта Jigsaw (на том же уровне, что и `config.php`), и возвращает массив
Директивы с ключом по имени директивы, каждая из которых возвращает замыкание.

Например, вы можете создать пользовательский `@datetime($timestamp)` для форматирования заданной целочисленной метки времени в виде даты в
Ваши шаблоны Blade:

> blade.php

```php 
return [
'datetime' => function ($timestamp) {
return '<?php echo date("l, F j, Y", ' . $timestamp . '); ?>';
}
];
```

В качестве альтернативы, метод `blade.php` file получает переменную с именем `$bladeCompiler`, который предоставляет экземпляр
`\Illuminate\View\Compilers\BladeCompiler`. С его помощью вы можете создать собственный клинок
Директивы [Сглаженные компоненты](https://laravel.com/docs/12.x/blade#extending-blade)названный
`@include` или другие расширенные структуры управления Blade:


> blade.php

```php 
<?php

/** @var \Illuminate\View\Compilers\BladeCompiler $bladeCompiler */

$bladeCompiler->directive('datetime', function ($timestamp) {
    return '<?php echo date("l, F j, Y", ' . $timestamp . '); ?>';
});

$bladeCompiler->aliasComponent('_components.alert');

$bladeCompiler->include('includes.copyright');
```

> page.blade.php

```php 
<!-- Before -->

@component('_components.alert')
Pay attention to this!
@endcomponent

@include('_partials.meta.copyright', ['year' => '2018'])

<!-- After -->

@alert
Pay attention to this!
@endalert

@copyright(['year' => '2018'])
```

## Указание путей подсказок Blade

Чтобы использовать пути и пространства имен подсказок Blade в разметке (например, `email:components::section`), указываем путь к модулю
с помощью каталога `viewHintPaths` Введите ключ `config.php`:

> config.php

```php
<?php

return [
    'viewHintPaths' => [
        'email:templates' => __DIR__.'/source/_layouts',
        'email:components' => __DIR__.'/source/_components',
        'email:partials' => __DIR__.'/source/_partials'
    ]
];
```
