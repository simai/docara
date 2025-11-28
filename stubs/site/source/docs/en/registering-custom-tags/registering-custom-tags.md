---
extends: _core._layouts.documentation
section: content
title: Parser (Docara integration)
description: Parser (Docara integration)
---

# Parser (Docara integration)

This component replaces Jigsaw’s default front‑matter/Markdown parser and installs our **Custom Tags** extension into League CommonMark.

---

## Location & purpose
- **Class:** `App\Helpers\Parser`
- **Extends:** `TightenCo\Jigsaw\Parsers\FrontMatterParser`
- **Goal:** Build a CommonMark environment with our custom extension and use it to convert Markdown to HTML during the Docara build.

---

## Constructor wiring
```php
public function __construct(FrontYamlParser $frontYaml, CustomTagRegistry $registry)
{
    parent::__construct($frontYaml);

    $env = new Environment();
    $env->addExtension(new CustomTagsExtension($registry));
    $env->addExtension(new CommonMarkCoreExtension());
    $env->addExtension(new FrontMatterExtension());
    $this->md = new MarkdownConverter($env);
}
```

### Dependencies
- **`FrontYamlParser`** — Jigsaw’s Front Matter YAML parser (used by the parent class).
- **`CustomTagRegistry`** — Runtime registry of our `CustomTagSpec`s, injected into the custom extension.

### Installed extensions
- **`CustomTagsExtension`** — Our extension which registers `UniversalBlockParser` and `CustomTagRenderer`.
- **`CommonMarkCoreExtension`** — Standard CommonMark block/inline features.
- **`FrontMatterExtension`** — Allows fenced front matter blocks to be recognized by the converter pipeline when needed.

> **Naming note:** elsewhere in docs we use the term **CustomTagExtension** (singular). In code this project uses `CustomTagsExtension` (plural). Both refer to the same extension role; prefer the class name used in your codebase.

---

## Markdown conversion
```php
/**
 * @throws CommonMarkException
 */
public function parseMarkdownWithoutFrontMatter($content): string
{
    return (string) $this->md->convert($content);
}
```
- Docara handles front matter extraction in the parent class; this method converts the **body** Markdown into HTML using our environment.
- Throws `CommonMarkException` if conversion fails (bubble up for build failure visibility).

---

## Lifecycle in a Docara build
1. **Bootstrap binding** maps `TightenCo\Jigsaw\Parsers\FrontMatterParser` ➜ `App\Helpers\Parser`.
2. Docara calls the parser to process each Markdown file:
    - Parent class parses **front matter** with `FrontYamlParser`.
    - `parseMarkdownWithoutFrontMatter()` converts the remaining Markdown using our CommonMark `Environment`.
3. Inside the environment, **CustomTagsExtension** installs:
    - `UniversalBlockParser` — detects `!type` / `!endtype` blocks and builds `CustomTagNode`s.
    - `CustomTagRenderer` — renders tag nodes via per‑tag renderers or default wrappers.

---

## Customization & options
- **CommonMark configuration:** you can pass an array of options to `Environment` if needed:
  ```php
  $env = new Environment(['renderer' => ['inner_separator' => "\n"]]);
  ```
- **Additional extensions:** add other official extensions (tables, autolinks, etc.) by calling `$env->addExtension(new ...)` before creating `MarkdownConverter`.
- **Event hooks:** the class imports `DocumentParsedEvent`; you can register listeners on the environment if post‑processing of the AST is required (e.g., slug generation, TOC). Example:
  ```php
  $env->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $e) {
      // mutate $e->getDocument() as needed
  });
  ```

---

## Troubleshooting
- **Custom tags not recognized:** ensure `bootstrap.php` binds `FrontMatterParser::class` to `App\Helpers\Parser::class` and that `CustomTagsExtension` is added.
- **Per‑tag renderer not invoked:** verify the registry contains specs with `renderer` closures; the same registry instance must be passed to the extension and renderer.
- **Front matter leaks into HTML:** confirm Docara is stripping it (parent class handles this) and that your Markdown file has correct front matter delimiters.
- **Build fails with `CommonMarkException`:** inspect the content for invalid HTML/Markdown, or temporarily remove custom extensions to isolate the cause.

---

## Minimal test
Create a fixture document:
```md
---
title: Parser test
---

!example class:"mb-2"
Hello **world**
!endexample
```
Expected output includes a wrapper element with merged classes and the bold text rendered.
