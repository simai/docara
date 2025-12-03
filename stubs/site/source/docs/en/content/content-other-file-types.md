---
extends: _core._layouts.documentation
section: content
title: Other File Types
description: Other File Types
---

# Other File Types

## Blade/Markdown hybrids

Markdown files can use Blade syntax (data, control structures) by using the `.blade.md` extension. Files with `.blade.md` are rendered by Blade first, then parsed as Markdown.

Regular `.blade.php` templates can also include Markdown partials via `@include`; those partials are parsed by the Markdown engine first.

## Non-HTML files

Text-type files can also be rendered through Blade, letting you generate non-HTML outputs with variables and control structures. Supported extensions include: `.blade.js`, `.blade.json`, `.blade.yml`, `.blade.yaml`, `.blade.xml`, `.blade.rss`, `.blade.atom`, `.blade.txt`, `.blade.text`. After Blade renders them, the output keeps its original extension in the URL (e.g., `some-file.blade.xml` → `.../some-file.xml`).

## Other files
Any other files, such as plain `.html`, are copied directly to your build folders without modification by Docara.
