---
extends: _core._layouts.documentation
section: content
title: Parser (Docara integration)
description: Parser (Docara integration)
---

# Parser (Docara integration)

Docara swaps Jigsaw's default front-matter/Markdown parser for this class so we can install Custom Tags and a tuned CommonMark environment.

---

## Location & purpose
- **Class:** `Simai\Docara\Parser`
- **Extends:** `Simai\Docara\Parsers\FrontMatterParser`
- **Goal:** Convert Markdown to HTML with our Custom Tags extension, permissive HTML settings, and a few doc-specific tweaks.

---

## Constructor wiring (key parts)
```php
public function __construct(FrontYamlParser $frontYaml, CustomTagRegistry $registry)
{
    parent::__construct($frontYaml);

    $config = [
        'html_input' => 'allow',
        'allow_unsafe_links' => true,
        'disallowed_raw_html' => ['disallowed_tags' => [
            'title','textarea','style','xmp','noembed','noframes','script','plaintext',
        ]],
    ];

    $env = new Environment($config);
    $env->addExtension(new CustomTagsExtension($registry));
    $env->addExtension(new CommonMarkCoreExtension);
    $env->addExtension(new FrontMatterExtension);
    $env->addExtension(new AttributesExtension);
    $env->addExtension(new GithubFlavoredMarkdownExtension);

    // Copies header cell classes to each column cell in a table
    $env->addEventListener(DocumentParsedEvent::class, $this->propagateTableHeaderClasses(...), -100);

    $this->md = new MarkdownConverter($env);
}
```

### CommonMark config
- Allows inline HTML (`html_input => allow`), keeps unsafe links enabled.
- Blocks only a small set of raw tags (`script`, `style`, `textarea`, etc.).

### Installed extensions
- **`CustomTagsExtension`** - registers `UniversalBlockParser` + `CustomTagRenderer` with the runtime registry.
- **`CommonMarkCoreExtension`** - standard CommonMark syntax.
- **`FrontMatterExtension`** - recognizes fenced front matter blocks.
- **`AttributesExtension`** - supports `{.class #id key=val}` attributes in Markdown.
- **`GithubFlavoredMarkdownExtension`** - autolinks, strikethrough, tables, etc.

> Elsewhere we say **CustomTagExtension** (singular); in code it's `CustomTagsExtension` (plural). Both mean the same extension; stick to the class name used in code.

### Table column classes
During `DocumentParsedEvent`, header cell classes (incl. nested nodes) are read and applied to all cells in the same column. This lets you style columns by putting classes only in the `<thead>` row.

---

## Markdown conversion API
```php
/**
 * @throws CommonMarkException
 */
public function parseMarkdownWithoutFrontMatter($content): string
{
    return (string) $this->md->convert($content);
}
```
- Parent class strips front matter; this converts the body using the configured environment.
- `CommonMarkException` bubbles up so build failures stay visible.

---

## Wiring inside Docara
- `CustomTagServiceProvider` binds `FrontMatterParser::class` to `Simai\Docara\Parser`.
- Collection/view handlers resolve `FrontMatterParser` from the container, so Custom Tags and table-class propagation apply everywhere Markdown is rendered.

---

## Quick checks / troubleshooting
- **Custom tags missing:** ensure providers are loaded so the binding to `Simai\Docara\Parser` happens and the registry is built.
- **Column styles ignored:** put classes on `<th>` (or nested nodes) in the header row; they propagate to body cells after parsing.
- **HTML stripped:** only the disallowed tag list is blocked; other HTML should render. If not, check input for invalid nesting.
- **Crashes:** inspect the thrown `CommonMarkException` and temporarily remove custom extensions to isolate the cause.

---

## Minimal fixtures
- **Custom tag sanity check**
  ```md
  !example class:"mb-2"
  Hello **world**
  !endexample
  ```
- **Table column classes**
  ```md
  | Col A {.w-25} | Col B {.text-right} |
  |---------------|---------------------|
  | a1            | b1                  |
  | a2            | b2                  |
  ```
  Header classes should be copied to each column's cells in the output HTML.
