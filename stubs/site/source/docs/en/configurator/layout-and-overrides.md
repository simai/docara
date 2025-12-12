# Layout & Overrides

This project supports a modular layout with switchable areas and per-path overrides.

## Base layout

-   Defined in `stubs/site/config.php` under `$layoutConfiguration['base']` (header, asideLeft, main, asideRight, footer, floating, etc.).
-   Use helpers in Blade:
-   `layout_enabled($page, 'asideLeft')` — returns `true/false` (defaults to `true` if `enabled` is not set).
-   `layout_section($page, 'main.outerContent')` — returns the section config array/object.

Example:

```blade
@includeWhen(layout_enabled($page, 'asideLeft'), '_core._components.aside.aside-left', [
    'section' => layout_section($page, 'asideLeft'),
])
@includeWhen(layout_enabled($page, 'footer'), '_core._layouts.footer')
```

## Per-page/section overrides (.settings.php)

Add a `layoutOverrides` block in the nearest `.settings.php`. It is merged from parent folders down to the page path.

Structure:

```php
'layoutOverrides' => [
    'default' => [
        'config' => ['asideRight' => ['enabled' => false]],
        'recursive' => false, // apply to current path only; true => all children
        'category' => false,  // same as recursive but explicit
    ],
    'matches' => [
        [
            'pattern' => 'collections',   // exact, glob (*.md), or regex (/^collections/)
            'config' => ['asideRight.enabled' => true],
            'recursive' => false,         // match only this path segment
            'category' => true,           // treat pattern as category: match full path and children
        ],
    ],
];
```

Notes:

-   Path normalization strips `DOCS_DIR`, locale, duplicate trailing slug, and file extensions (`index`, `.html`, etc.).
-   `category`/`recursive` in `matches` lets a parent rule cover all descendants; a child `.settings.php` can override by adding its own rule for the same pattern.
-   Override config supports dot-notation keys (e.g., `asideRight.enabled`). Merges use deep merge logic from `Layout::deepMerge`.

## Collection items

-   Collection pages resolve layout the same way; the resolved layout is stored on `$page->layoutResolved` and consumed by the helpers above.

## Caching and .settings changes

-   Build cache includes a hash of relevant `.settings.php` files up the tree; changing a `.settings.php` forces rebuild of affected pages.
