---
extends: _core._layouts.documentation
section: content
title: Site Variables
description: Site Variables
---

# Site Variables
Anything you add to the array in `config.php` will be made available in all of your templates, as a property of the `$page` object.

For example, if your `config.php` looks like this:

```php
<?php

return [
    'contact_email' => 'support@example.com',
];
```

…you can use that variable in any of your templates like so:

```blade 
@extends('_layouts.master')

@section('content')
    <p>Contact us at {{ $page->contact_email }}</p>
@stop
```

If you prefer, site variables can also be accessed as arrays:

```blade 
<p>Contact us at {{ $page['contact_email'] }}</p>
```

Docara also supports environment-specific site variables.
