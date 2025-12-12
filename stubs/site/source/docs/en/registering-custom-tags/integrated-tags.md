---
extends: _core._layouts.documentation
section: content
title: Built-in Tags
description: Tags shipped in the scaffold and how to use them
---

# Built-in tags (scaffold)

Docara ships a few ready-to-use tags under `source/helpers/CustomTags`. Register them in `config.php` under the `tags` array (ExampleTag and ListWrap are there by default; add `Folders` if you need it).

```php
return [
    // ...
    'tags' => [
        'ExampleTag',
        'ListWrap',
        'Folders', // enable folder tree tag
    ],
];
```

## `!example … !endexample`

- Type: `example` (`ExampleTag`).
- Adds classes `example overflow-hidden radius-1/2 overflow-x-auto` to the wrapper.
- Use for fenced examples/demos:

```markdown
!example
Your demo content or markup here.
!endexample
```

## `!links … !endlinks`

- Type: `links` (`ListWrap`).
- Wraps inner content with `class="links"`; handy for grouped link lists.

```markdown
!links
- [Docara](https://github.com/simai/docara)
- [Jigsaw](https://jigsaw.tighten.com/)
!endlinks
```

## `!folders … !endfolders`

- Type: `folders` (`Folders`).
- Renders a toggleable folder/file tree with icons; loads `helpers/js/folders.js` once per page.
- Syntax: leading `-` for folders, `--` for files; indentation (spaces/tabs) sets nesting. Optional `(focus)` or `(*)` highlights the current item.

!folders
- src (focus)
  -- Docara.php
  -- Console
    - Commands
      -- BuildCommand.php
- stubs
  -- site
!endfolders

```markdown
!folders
- src (focus | *)
 -- Docara.php
 -- Console
  - Commands
   -- BuildCommand.php
- stubs
 -- site
!endfolders
```

Notes:
- The tag is non-container: only raw lines are parsed; no nested tag body inside.
- `isContainer` is false, so inner Markdown/inline tags are not parsed—keep plain lines.
- Focus can be set on folders or files; children inherit the highlight.

