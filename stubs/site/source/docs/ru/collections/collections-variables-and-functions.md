---
extends: _core._layouts.documentation
section: content
title: 'Переменные и вспомогательные функции'
description: 'Переменные и вспомогательные функции'
---

# Переменные и вспомогательные функции

Каждая коллекция может иметь свой собственный набор переменных и вспомогательных методов, определенных в массиве коллекции в `config.php`.
Они имеют тот же формат, что и общеузловые переменные и вспомогательные методы, определенные на верхнем уровне метода
`config.php`массив.

## Переменные

Как и в случае с переменными на уровне сайта, переменные-коллекции, определенные в `config.php` могут выступать в качестве значений по умолчанию, которые могут быть
переопределяются переменными с тем же именем, указанными в YAML-лице элемента коллекции. По сути, верхнего уровня
переменных в `config.php` будет переопределен переменными с таким же именем в массиве коллекции, который будет
дальнейший
переопределяются ссылками в YAML-заголовке любой отдельной страницы, что позволяет настроить каскад переменных
По умолчанию. Например:

> config.php

```php 
<?php

return [
    'author' => 'Default Site Author',
    'collections' => [
        'posts' => [
            'author' => 'Default Blog Author',
        ],
    ],
];
```

> _posts/blog-post-1.blade.php

```yaml
---
extends: _layouts.post
title: My First Post
author: Keith Damiani
---
@section ('content')

<h1>{{ $page->title }}</h1>
<p>By {{ $page->author }}</p>

@endsection
```

Для этого элемента коллекции автором будет Кит Дамиани, значение из заголовка YAML.

> _posts/blog-post-2.blade.php

```yaml
---
extends: _layouts.post
title: My Second Post
---
@section ('content')

<h1>{{ $page->title }}</h1>
<p>By {{ $page->author }}</p>

@endsection
```

Для этого элемента коллекции автором будет Автор блога по умолчанию, значение из `posts`массив в `config.php`.

> about-us.blade.php

```yaml
---
extends: _layouts.about
title: About our company
---
@section ('content')

<h1>{{ $page->title }}</h1>
<p>By {{ $page->author }}</p>

@endsection
```

Для этой обычной (не коллекционной) страницы автором будет *Автор сайта по умолчанию*, значение из верхнего уровня
`config.php`.

## Вспомогательные функции

Вспомогательные функции могут быть включены в массив настроек коллекции в `config.php`, и будет доступен для всего этого
предметы коллекции. Те же каскадные правила, которые применяются к переменным, применимы и к функциям, т.е. функциям, определенным для
Коллекция будет переопределять функцию с тем же именем, определенную на верхнем уровне. Например:

> config.php

```php 
<?php

return [
    'excerpt' => function ($page, $characters = 100) {
        return substr($page->getContent(), 0, $characters);
    },
    'collections' => [
        'posts' => [
            'excerpt' => function ($page, $characters = 50) {
                return substr(strip_tags($page->getContent()), 0, $characters);
            },
        ],
    ],
];
```
