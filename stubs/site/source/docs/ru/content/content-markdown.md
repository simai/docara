---
extends: _core._layouts.documentation
section: content
title: Уценка
description: Уценка
---

# Уценка

У вас есть страницы, которые вы бы предпочли написать в Markdown, а не в Blade? Мы знаем это чувство.

Использовать Markdown в Jigsaw так же просто, как использовать расширение .markdown или .md и указать несколько деталей в YAML front
материя.

Например, предположим, что у вас есть этот макет и вы хотите заполнить раздел содержимого с помощью Markdown:

```blade 
<html>
    <head><!-- ... --></head>
    <body>
        @yield('content')
    </body>
</html>
```

Если этот макет был назван master в папке _layouts, вы можете создать страницу Markdown, которая использует этот макет следующим образом:

```yaml
---
extends: _layouts.master
section: content
---

# My awesome heading!

My awesome content!
```

Конечным результатом будет сгенерированная страница, которая будет выглядеть следующим образом:

```blade 
<html>
    <head><!-- ... --></head>
    <body>
        <h1>My awesome heading!</h1>
        <p>My awesome content!</p>
    </body>
</html>
```

## Пользовательские переменные передней части

Представьте, что у вас есть макет с именем post.blade.php в папке _layouts, который выглядит следующим образом:

> _layouts/post.blade.php

```blade 
@extends('_layouts.master')

@section('content')
<h1>{{ $page->title }}</h1>
<h2>by {{ $page->author }}</h2>

    @yield('postContent')
@endsection
```

Вы можете заполнить переменные title и author, добавив пользовательские ключи в переднюю часть YAML:

> my-post.md

```yaml 
---
extends: _layouts.post
section: postContent
title: "Jigsaw is awesome!"
author: "Adam Wathan"
---

Jigsaw is one of the greatest static site generators of all time.
```

… что привело бы к следующему:

```html 

<html>
<head><!-- ... --></head>
<body>
<h1>Jigsaw is awesome!</h1>
<h2>by Adam Wathan</h2>

<p>Jigsaw is one of the greatest static site generators of all time.</p>
</body>
</html>
```

## Форматирование дат

Процессор YAML преобразует любые даты, найденные в передней части файла Markdown, в целочисленные временные метки. Когда
выводя переменную date в шаблоне Blade, вы можете использовать PHP `date()` функция для указания формата даты. Для
пример:


> my-post.md

```blade 
---
extends: _layouts.post
section: postContent
date: 2018-02-16
---
```

> _layouts/post.blade.php

```blade 
<p>The formatted date is {{ date('F j, Y', $post->date) }}</p>
```

## Указание постоянной ссылки

Вы можете указать параметр `permalink` в переднем крае YAML, чтобы переопределить путь к файлу по умолчанию при создании сайта.
Это может быть использовано, например, для создания пользовательской страницы 404, которая выводится в `404.html` (вместо значения по умолчанию
`404/index.html`):


> Источник/404.md

```yaml
---
extends: _layouts.master
section: content
permalink: 404.html
---

### Sorry, that page does not exist.
```
