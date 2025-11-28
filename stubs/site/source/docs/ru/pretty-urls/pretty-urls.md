---
extends: _core._layouts.documentation
section: content
title: 'Красивые URL-адреса'
description: 'Красивые URL-адреса'
---

# Красивые URL-адреса

По умолчанию все файлы Blade не имеют имен `index.blade.php` отображаются как `index.html` во вложенной папке, названной в честь
Исходный файл.

Например, если у вас есть файл с именем `about-us.blade.php` в вашем `/source` каталог:

<div class="files">
    <div class="folder folder--open">source
        <div class="folder">_assets</div>
        <div class="folder">_layouts</div>
        <div class="folder">assets</div>
        <div class="file focus">about-us.blade.php</div>
        <div class="file">blog.blade.php</div>
        <div class="file">index.blade.php</div>
    </div>
    <div class="ellipsis">...</div>
</div>

… Он будет отображаться как `index.html` В `/build/about-us` каталог:

<div class="files">
    <div class="folder folder--open">build_local
        <div class="folder folder--open focus">about-us
            <div class="file">index.html</div>
        </div>
        <div class="folder folder--open">blog
            <div class="file">index.html</div>
        </div>
        <div class="file">index.html</div>
    </div>
    <div class="ellipsis">...</div>
</div>

Это означает, что ваша страница «О нас» будет доступна по адресу `http://example.com/about-us/` Вместо
`http://example.com/about-us.html`.

## Отключение красивых URL-адресов

Чтобы отключить это поведение, установите параметр `pretty` Возможность `false` В вашем файле конфигурации:

```php 
return [
'pretty' => false,
];
```
