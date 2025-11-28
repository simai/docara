---
extends: _core._layouts.documentation
section: content
title: 'Вариативный сайт'
description: 'Вариативный сайт'
---

# Вариативный сайт
Все, что вы добавляете в массив в `config.php` будет доступен во всех ваших шаблонах, как свойство `$page` объект.

Например, если ваш `config.php` Выглядит так:

```php
<?php

return [
    'contact_email' => 'support@example.com',
];
```

… Вы можете использовать эту переменную в любом из ваших шаблонов, например:

```blade 
@extends('_layouts.master')

@section('content')
    <p>Contact us at {{ $page->contact_email }}</p>
@stop
```

При желании переменные сайта также могут быть доступны в виде массивов:

```blade 
<p>Contact us at {{ $page['contact_email'] }}</p>
```

Jigsaw также поддерживает переменные сайта, специфичные для среды.
