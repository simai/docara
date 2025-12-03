---
extends: _core._layouts.documentation
section: content
title: Mapping
description: Mapping
---

# Mapping

You can map over your collection items by adding a `map` key to the collection's array in `config.php`, and specifying a
callback that accepts the collection item. Each item is an instance of the `Simai\Docara\Collection\CollectionItem`
class, from which you can instantiate your own custom class using the static `fromItem()` method. Your custom class can
include helper methods that might be too complex for storing in your config.php array.

> config.php

```php
<?php

return [
    'collections' => [
        'posts' => [
            'map' => function ($post) {
                return Post::fromItem($post);
            }
        ],
    ],
];
```

Your custom `Post` class should extend `Simai\Docara\Collection\CollectionItem`, and could include helper functions,
reference and/or modify page variables, etc.:

```php
<?php

use Simai\Docara\Collection\CollectionItem;

class Post extends CollectionItem
{
    public function getAuthorNames()
    {
        return implode(', ', $this->author);
    }
}
```
