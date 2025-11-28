---
extends: _core._layouts.documentation
section: content
title: 'Расширение родительских шаблонов'
description: 'Расширение родительских шаблонов'
---

# Расширение родительских шаблонов

Чтобы каждый из элементов вашей коллекции отображался на отдельной странице, вам необходимо указать родительский шаблон. Сделать это можно в
тем `extends` ключа лицевой материи YAML, либо с помощью ключа `@extends` в файле Blade:


> my-first-post.md

```yaml 
---
extends: _layouts.post
title: My First Blog Post
author: Keith Damiani
date: 2017-03-23
section: content
---

This post is *profoundly* interesting.
```

> my-second-post.blade.php

```yaml

---
title: My Second Blog Post
author: Keith Damiani
date: 2017-03-25
section: content
---
@extends ('_layouts.post')

This is {{ $page->author }}'s second <strong>amazing</strong> post.
```

## Элементы коллекции без родительского шаблона

Однако родительские шаблоны являются необязательными для элементов коллекции. В некоторых случаях, например, для коллекции биографий сотрудников, которые
отображаются на странице «О нас» — возможно, вам не нужно отображать каждый из элементов вашей коллекции на отдельных страницах. Для этого
просто опустите клавишу extends из переднего элемента YAML, или `@extends` из файла Blade.

## Элементы коллекции с несколькими родительскими шаблонами

Предметы коллекции также могут быть расширены *многократный* родительских шаблонов, указав шаблоны в виде массива в `extends`
ключ
во фронте YAML. При этом будет сгенерирован отдельный URL-адрес для каждого шаблона, что позволит, например, создать элемент коллекции
иметь и то, и другое `/web/item` и `/api/item` конечные точки, или `/summary` и `/detail` Представления.

> _people/abraham-lincoln.md


```yaml
---
name: Abraham Lincoln
role: President
number: 16
extends:
web: _layouts.person
api: _layouts.api.person
section: content
---
...
```


> _layouts.person.blade.php


```yaml
@extends('_layouts.master')

@section('body')
<header>
<h1>{{ $page->name }}</h1>
<h2>{{ $page->role }}</h2>
</header>

@yield('content')

@endsection
```


> _layouts.api.person.blade.js


```yaml
{ !! $page->api() !! }
```

Если вы используете несколько родительских шаблонов, вы можете указать отдельные пути в `config.php` Для каждой результирующей страницы:

> config.php

```php 
<?php

use Illuminate\Support\Str;

return [
    'collections' => [
        'people' => [
            'path' => [
                'web' => 'people/{number}/{filename}',
                'api' => 'people/api/{number}/{filename}',
            ],
            'api' => function ($page) {
                return [
                    'slug' => Str::slug($page->getFilename()),
                    'name' => $page->name,
                    'number' => $page->number,
                    'content' => $page->getContent(),
                ];
            },
        ],
    ],
];
```
