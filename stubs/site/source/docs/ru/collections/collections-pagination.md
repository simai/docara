---
extends: _core._layouts.documentation
section: content
title: 'Нумерация страниц'
description: 'Нумерация страниц'
---

# Нумерация страниц

Вы можете создать шаблон Blade, который отображает элементы коллекции в формате с разбивкой на страницы, добавив в него `pagination`
в YAML-лице шаблона. Заголовок пагинации должен содержать файл `collection` name и `perPage`
считать:

> posts.blade.php

```yaml 
---
pagination:
collection: posts
perPage: 5
---
  @extends('_layouts.master')
  ...
```

> Если вы не предоставите `perPage` В вашем шаблоне можно установить значение по умолчанию для конкретной коллекции, добавив
> a
`perPage` ключ к настройкам коллекции в `config.php`или глобально, добавив метод `perPage` Ключ к верхнему уровню
`config.php`. В противном случае значение по умолчанию будет равно 10.

Как только `pagination` был определен в шапке, шаблон будет иметь доступ к специальному `$pagination` переменная
который имеет несколько признаков:
!links

- `$pagination->items` содержит массив элементов коллекции для текущей страницы
- `$pagination->currentPage` содержит номер текущей страницы
- `$pagination->totalPages` содержит общее количество страниц
- `$pagination->pages` содержит массив путей к каждой странице
  
!endlinks

> Обратите внимание, что метод `pages` индексируются по номеру страницы, т.е. они основаны на 1. Таким образом, вы можете ссылаться на пути к странице
> около
> номер страницы, т.е. `$pagination->page[1]` вернет путь на первую страницу.

!links

- `$pagination->first` содержит путь к первой странице (такой же, как $pagination->path[1])
- `$pagination->last` содержит путь к последней странице
- `$pagination->next` содержит путь к следующей странице
- `$pagination->previous` содержит путь к предыдущей странице

!endlinks

Использование этих `$pagination` Вы можете создать набор кнопок пагинации и ссылок:

```blade
@if ($previous = $pagination->previous)
<a href="{{ $page->baseUrl }}{{ $pagination->first }}">&lt;&lt;</a>
<a href="{{ $page->baseUrl }}{{ $previous }}">&lt;</a>
@else
&lt;&lt; &lt;
@endif

@foreach ($pagination->pages as $pageNumber => $path)
<a href="{{ $page->baseUrl }}{{ $path }}"
class="{{ $pagination->currentPage == $pageNumber ? 'selected' : '' }}">
{{ $pageNumber }}
</a>
@endforeach

@if ($next = $pagination->next)
<a href="{{ $page->baseUrl }}{{ $next }}">&gt;</a>
<a href="{{ $page->baseUrl }}{{ $pagination->last }}">&gt;&gt;</a>
@else
&gt; &gt;&gt;
@endif
```

Чтобы отобразить элементы на каждой странице, выполните итерацию по параметру `$pagination->items` коллекция:

```blade
@foreach ($pagination->items as $post)
<h3><a href="{{ $post->getUrl() }}">{{ $post->title }}</a></h3>
<p class="text-sm">by {{ $post->author }} • {{ date('F j, Y', $post->date) }}</p>
<div>{!! $post->getContent() !!}</div>
@endforeach
```
