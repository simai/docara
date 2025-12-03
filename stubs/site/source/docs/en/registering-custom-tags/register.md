---
extends: _core._layouts.documentation
section: content
title: Register Custom Tags
description: Register Custom Tags
---

# Registering Custom Tags

This page walks you through adding a new custom Markdown tag and making it available to the Docara build.

---

## Prerequisites
- Your tag class must extend `Simai\Docara\CustomTags\BaseTag` (or implement `CustomTagInterface`).
- Composer autoload maps the app namespace `App\\` to your project source (stubs ship with `App\\ => source/`), where custom tags live (e.g., `source/helpers/CustomTags`).
- Docara's `Parser` already installs `CustomTagsExtension`; you only need to provide your tag classes and list them in config.

---

## Step 1 — Create the tag class
Place the class under your project namespace, e.g. `App\Helpers\CustomTags` (stubs include `source/helpers/CustomTags`), and return a unique `type()`.

```php
<?php

namespace App\Helpers\CustomTags;

use Simai\Docara\CustomTags\BaseTag;

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
- `type()` is the marker used in Markdown (`!example` ↔ `!endexample`).
- `baseAttrs()` provides defaults; author-supplied attributes are merged (classes concatenated and deduplicated).

---

## Step 2 — Declare the tag in `config.php`
List the **short** class names (no namespace) under the `tags` array. Each short name is resolved to your tag namespace, e.g. `App\\Helpers\\CustomTags\\<ShortName>`.

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

## Step 3 — Wiring (provided by core)
`CustomTagServiceProvider` already builds the tag registry from `config('tags')` and binds it into the container. It also swaps `FrontMatterParser` with our `Simai\Docara\Parser`, so Custom Tags are active everywhere Markdown is rendered.

```php
<?php

/** @var $app \Illuminate\Container\Container */

use Simai\Docara\CustomTags\CustomTagRegistry; // core registry
use Simai\Docara\CustomTags\TagRegistry;       // core helper to register specs
use Simai\Docara\Interface\CustomTagInterface;
use Simai\Docara\Parser;                       // core parser with CustomTagsExtension
use TightenCo\Jigsaw\Parsers\FrontMatterParser;

$app->bind(FrontMatterParser::class, Parser::class);

$app->bind(CustomTagRegistry::class, function ($c) {
    $namespace = 'App\\Helpers\\CustomTags\\';
    $shorts = (array) $c['config']->get('tags', []);
    $instances = [];
    foreach ($shorts as $short) {
        $class = $namespace . $short;
        if (class_exists($class)) {
            $obj = new $class; // core provider does this
            if ($obj instanceof CustomTagInterface) {
                $instances[] = $obj;
            }
        }
    }
    return TagRegistry::register($instances);
});
```

**Tip (optional DI)**: If a tag needs constructor dependencies, override the binding and use `$c->make($class)` instead of `new $class()`.

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
2. **Registry binding**: provider/boot logic instantiates these classes and registers them via `TagRegistry::register(...)`.
3. **Parser binding**: `FrontMatterParser` is aliased to `Simai\Docara\Parser`, which installs `CustomTagsExtension` into the CommonMark environment.
4. **Parsing**: `UniversalBlockParser` matches `openRegex()`/`closeRegex()` for each registered tag and builds `CustomTagNode` ASTs.
5. **Rendering**: `CustomTagRenderer` merges attributes, applies `attrsFilter()`, and either calls `renderer()` or emits the default wrapper (`htmlTag()`).

---

## Enabling/disabling by environment (optional)
You can branch on environment to include experimental tags only in `dev`:

```php
$app->bind(CustomTagRegistry::class, function ($c) {
    $namespace = 'App\\Helpers\\CustomTags\\';
    $shorts = (array) $c['config']->get('tags', []);

    $env = $c['config']->get('env');
    if ($env !== 'production') {
        $shorts[] = 'ExperimentalTag';
    }

    // ...instantiate as shown above
});
```

---

## Common pitfalls
- **Class not found**: Run `composer dump-autoload`, verify namespace and file path under `source/helpers/CustomTags`.
- **Not registered**: The short name in `config('tags')` must exactly match the class basename.
- **Duplicate `type()`**: Ensure every tag’s `type()` is unique; otherwise the first one wins.
- **Wrong HTML**: Check `htmlTag()`/`renderer()` and confirm `attrsFilter()` isn’t stripping values.
- **Attributes not parsed**: Make sure the attribute string uses normal quotes/spaces; the parser normalizes Unicode spaces/quotes, but input must be on the **open line**.

---

## Unregistering a tag
- Remove its short name from `config('tags')`.
- Rebuild the site. The tag will no longer be recognized during parsing.

---

## Quick checklist
- [ ] Class in `source/helpers/CustomTags` (or your mapped path) extending `BaseTag`
- [ ] Unique `type()`
- [ ] Added to `config.php => tags`
- [ ] Composer autoload updated (`composer dump-autoload`)
- [ ] `bootstrap.php` binds registry and custom `Parser`
- [ ] Build/serve restarted (`vendor/bin/jigsaw build` or restart `serve`)
- [ ] Attributes parse correctly (quoted/unquoted, `.class`, `#id`)
- [ ] Optional: `attrsFilter()` added for normalization/whitelisting
- [ ] Optional: `renderer()` implemented for custom HTML
- [ ] Optional: verify `allowNestingSame()` behavior
- [ ] Fixture page renders as expected
