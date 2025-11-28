---
extends: _core._layouts.documentation
section: content
title: Отображение
description: Отображение
---

# Отображение

Вы можете нанести на карту элементы своей коллекции, добавив `map` ключ к массиву коллекции в `config.php`и указав параметр
Обратный вызов, который принимает элемент коллекции. Каждый элемент является экземпляром элемента `TightenCo\Jigsaw\Collection\CollectionItem`
class, из которого вы можете создать свой собственный пользовательский класс с помощью статического `fromItem()` метод. Ваш пользовательский класс может
Включите вспомогательные методы, которые могут быть слишком сложными для хранения в массиве config.php.

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

Ваш кастомный `Post` должен расширяться `TightenCo\Jigsaw\Collection\CollectionItem`, и может включать вспомогательные функции,
ссылаться и/или изменять переменные страницы и т.д.:

```php 
<?php

use TightenCo\Jigsaw\Collection\CollectionItem;

class Post extends CollectionItem
{
    public function getAuthorNames()
    {
        return implode(', ', $this->author);
    }
}
```
