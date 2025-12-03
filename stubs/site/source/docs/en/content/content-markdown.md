---
extends: _core._layouts.documentation
section: content
title: Markdown
description: Markdown
---

# Markdown

Prefer writing in Markdown instead of Blade? Docara supports `.md` and `.markdown` files with YAML front matter.

For example, with this layout:

```blade
<html>
    <head><!-- ... --></head>
    <body>
        @yield('content')
    </body>
</html>
```

If that layout is named `master` in the `_layouts` folder, you can create a Markdown page that uses it like so:

```yaml
---
extends: _layouts.master
section: content
---
# My awesome heading!

My awesome content!
```

Which generates:

```blade
<html>
    <head><!-- ... --></head>
    <body>
        <h1>My awesome heading!</h1>
        <p>My awesome content!</p>
    </body>
</html>
```

## Custom front matter variables

Suppose you have `post.blade.php` in `_layouts`:

> \_layouts/post.blade.php

```blade
@extends('_layouts.master')

@section('content')
<h1>{{ $page->title }}</h1>
<h2>by {{ $page->author }}</h2>

    @yield('postContent')
@endsection
```

Populate variables via front matter:

> my-post.md

```yaml
---
extends: _layouts.post
section: postContent
title: "Docara is awesome!"
author: "Simai Docara"
---
Docara is one of the greatest static site generators of all time.
```

Which generates:

```html
<html>
    <head>
        <!-- ... -->
    </head>
    <body>
        <h1>Docara is awesome!</h1>
        <h2>by Simai Docara</h2>

        <p>Docara is one of the greatest static site generators of all time.</p>
    </body>
</html>
```

## Formatting dates

YAML front matter dates are converted to integer timestamps. In Blade, format them with PHP's `date()`:

> my-post.md

```blade
---
extends: _layouts.post
section: postContent
date: 2018-02-16
---
```

> \_layouts/post.blade.php

```blade
<p>The formatted date is {{ date('F j, Y', $page->date) }}</p>
```

## Specifying a permalink

Set `permalink` in front matter to override the output path (e.g., a custom 404 page at `404.html` instead of `404/index.html`):

> source/404.md

```yaml
---
extends: _layouts.master
section: content
permalink: 404.html
---
### Sorry, that page does not exist.
```
