---
extends: _core._layouts.documentation
section: content
title: Pretty URLs
description: Pretty URLs
---

# Pretty URLs

By default, any Blade files not named `index.blade.php` are rendered as `index.html` in a subfolder named after the
original file.

For example, if you have a file named `about-us.blade.php` in your `/source` directory:



!folders
- source
    - _assets
    - _layout
    - assets
    -- about-us.blade.php (*)
    -- blog.blade.php
    -- index.blade.php
!endfolders

It will be rendered as `index.html` in the `/build/about-us` directory:


!folders
- build_*
  - about-us (*)
    -- index.html
  - blog
    -- index.html
  -- index.html
!endfolders



This means your "About us" page will be available at `http://example.com/about-us/` instead of
`http://example.com/about-us.html`.

## Disabling Pretty URLs

To disable this behavior, set the `pretty` option to `false` in your config file:

```php 
return [
'pretty' => false,
];
```

> Note: the current Docara stubs and navigation logic assume `pretty` is `true`. Switching it off can break links and menu generation unless you adjust paths accordingly.
