---
extends: _core._layouts.documentation
section: content
title: Register Custom Tags
description: Register Custom Tags
---

# Registering Custom Tags

This page walks you through adding a new custom Markdown tag to the project and making it available to the Docara build.

---

## Prerequisites
- Your tag class must extend `App\Helpers\CommonMark\BaseTag` (or implement `CustomTagInterface`).
- Composer autoload maps the `App\` namespace to your `source/_core` tree.
- The Jigsaw build uses our custom `Parser` with the `CustomTagExtension` installed (see **Jigsaw wiring** below).

---

## Step 1 — Create the tag class
Place the class under `App\Helpers\CustomTags` and return a unique `type()`.

```php
<?php

namespace App\Helpers\CustomTags;

use App\Helpers\CommonMark\BaseTag;

final class ExampleTag extends BaseTag
{
    public function type(): string { return 'example'; }

    public function baseAttrs(): array
    {
        return ['class' => 'example overflow-hidden radius-1/2 overflow-x-auto'];
    }
}
```

**Notes**
- `type()` is the marker used in Markdown (`!example` … `!endexample`).
- `baseAttrs()` provides default attributes; author-supplied attributes are merged (classes are concatenated and de‑duplicated).

---

## Step 2 — Declare the tag in `config.php`
List the **short** class names (no namespace) under the `tags` array. Each short name is resolved to `App\\Helpers\\CustomTags\\<ShortName>`.

```php
<?php

return [
    'tags' => [
        'ExampleTag',
        // 'CalloutTag', 'VideoTag', ...
    ],
];
```

---

## Step 3 — Jigsaw wiring (bootstrap.php)
Our `bootstrap.php` binds a tag registry using the `tags` from config and swaps Jigsaw’s front matter parser with our custom `Parser`.

```php
<?php

/** @var $container \Illuminate\Container\Container */
/** @var $events \TightenCo\Jigsaw\Events\EventBus */

use App\Helpers\CommonMark\CustomTagRegistry;
use App\Helpers\Interface\CustomTagInterface;
use App\Helpers\Parser;
use App\Helpers\Tags\TagRegistry;
use TightenCo\Jigsaw\Parsers\FrontMatterParser;

try {
    $container->bind(CustomTagRegistry::class, function ($c) {
        $namespace = 'App\\Helpers\\CustomTags\\';
        $shorts = (array) $c['config']->get('tags', []);
        $instances = [];
        foreach ($shorts as $short) {
            $class = $namespace . $short;
            if (class_exists($class)) {
                $obj = new $class(); // If you need DI, see the tip below
                if ($obj instanceof CustomTagInterface) $instances[] = $obj;
            }
        }
        return TagRegistry::register($instances);
    });
} catch (\ReflectionException $e) {
    // optionally log
}

try {
    $container->bind(FrontMatterParser::class, Parser::class);
} catch (\ReflectionException $e) {
    // optionally log
}
```

**Tip (optional DI)**: If a tag needs constructor dependencies, replace `new $class()` with `$c->make($class)` to let the container resolve them.

---

## Step 4 — Rebuild
Regenerate autoloads and build the site:

```bash
composer dump-autoload
vendor/bin/jigsaw build
```

If using `serve`, restart it after adding new classes.

---

## Step 5 — Verify with a fixture
Create a quick Markdown snippet and confirm the output:

```md
!example class:"mb-4 border" data-x=42 .demo #hello
**Inside** the example tag.
!endexample
```

Expected (simplified):

```html
<div id="hello" class="example overflow-hidden radius-1/2 overflow-x-auto mb-4 border demo" data-x="42">
  <p><strong>Inside</strong> the example tag.</p>
</div>
```

!example class:"mb-4 border" data-x=42 .demo #hello
**Inside** the example tag.
!endexample

---

## How registration works under the hood
1. **Config**: `config('tags')` lists tag short names.
2. **Registry binding**: `bootstrap.php` instantiates these classes and registers them via `TagRegistry::register(...)`.
3. **Parser binding**: Jigsaw’s `FrontMatterParser` is aliased to our `Parser`, which installs `CustomTagExtension` into the CommonMark environment.
4. **Parsing**: `UniversalBlockParser` matches `openRegex()`/`closeRegex()` for each registered tag and builds `CustomTagNode` ASTs.
5. **Rendering**: `CustomTagRender` merges attributes, applies `attrsFilter()`, and either calls `renderer()` or emits the default wrapper (`htmlTag()`).

---

## Enabling/disabling by environment (optional)
You can branch on environment to include experimental tags only in `dev`:

```php
$container->bind(CustomTagRegistry::class, function ($c) {
    $namespace = 'App\\Helpers\\CustomTags\\';
    $shorts = (array) $c['config']->get('tags', []);

    // Example: filter based on an env flag in config
    $env = $c['config']->get('env'); // adapt to how you expose environment
    if ($env !== 'production') {
        $shorts[] = 'ExperimentalTag';
    }

    // ...instantiate as shown above
});
```

(Adjust to your project’s way of exposing environments.)

---

## Common pitfalls
- **Class not found**: Run `composer dump-autoload`, verify namespace and file path under `source/_core/helpers`.
- **Not registered**: The short name in `config('tags')` must exactly match the class basename.
- **Duplicate `type()`**: Ensure every tag’s `type()` is unique; otherwise the first one wins.
- **Wrong HTML**: Check `htmlTag()`/`renderer()` and confirm `attrsFilter()` isn’t stripping values.
- **Attributes not parsed**: Make sure your attribute string uses normal quotes/spaces; our parser normalizes Unicode spaces/quotes, but confirm the input is on the **open line**.

---

## Unregistering a tag
- Remove its short name from `config('tags')`.
- Rebuild the site. The tag will no longer be recognized during parsing.

---

## Quick checklist
- [ ] Class in `App/Helpers/CustomTags` extending `BaseTag`
- [ ] Unique `type()`
- [ ] Added to `config.php => tags`
- [ ] Composer autoload updated (`composer dump-autoload`)
- [ ] `bootstrap.php` binds registry and custom `Parser`
- [ ] Jigsaw build/serve restarted (`vendor/bin/jigsaw build` or restart `serve`)
- [ ] Attributes parse correctly (quoted/unquoted, `.class`, `#id`)
- [ ] Optional: `attrsFilter()` added for normalization/whitelisting
- [ ] Optional: `renderer()` implemented for custom HTML
- [ ] Optional: verify `allowNestingSame()` behavior
- [ ] Fixture page renders as expected


