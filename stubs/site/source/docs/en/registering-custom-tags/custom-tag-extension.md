---
extends: _core._layouts.documentation
section: content
title: CustomTagsExtension & Registries
description: CustomTagsExtension & Registries
---

# CustomTagsExtension & Registries

How custom tags are wired into League CommonMark in Docara core, and how registries provide specs to the parsing/rendering pipeline.

---

## Components overview
- **CustomTagsExtension** - CommonMark extension that installs our parsers and renderer.
- **CustomTagRegistry** - Runtime registry of `CustomTagSpec` objects (one per tag type), used by parsers/renderers.
- **TagRegistry** - Factory that accepts tag class instances and produces a `CustomTagRegistry` (via the adapter).
- **CustomTagSpec** - Immutable data object describing a tag: regexes, wrapper, defaults, hooks.

---

## CustomTagsExtension
**Location:** `Simai\Docara\CustomTags\CustomTagsExtension`

Registers the block parser and renderer with the CommonMark environment.

```php
final class CustomTagsExtension implements ExtensionInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function register(EnvironmentBuilderInterface $env): void
    {
        $env->addBlockStartParser(new UniversalBlockParser($this->registry), 0);
        $env->addRenderer(CustomTagNode::class, new CustomTagRenderer($this->registry));

        // Inline parser hook is reserved:
        // $env->addInlineParser(new UniversalInlineParser($this->registry));
    }
}
```

**Notes**
- Priority is `0` here; adjust if you introduce other block parsers that might conflict.
- Installed by `Simai\Docara\Parser` during environment setup.

---

## CustomTagRegistry
**Location:** `Simai\Docara\CustomTags\CustomTagRegistry`

Provides fast lookup of specs by type and enumerates specs for scanning.

```php
final class CustomTagRegistry
{
    /** @var array<string,CustomTagSpec> */
    private array $byType = [];

    /** @return CustomTagSpec[] */
    public function getSpecs(): array { return array_values($this->byType); }

    public function get(string $type): ?CustomTagSpec { return $this->byType[$type] ?? null; }

    public function register(CustomTagSpec $s): void { $this->byType[$s->type] = $s; }
}
```

---

## TagRegistry (factory)
**Location:** `Simai\Docara\CustomTags\TagRegistry`

Converts tag classes (extending `BaseTag`) into a runtime registry of specs, validating types and preventing duplicates.

```php
final class TagRegistry
{
    /**
     * @param  CustomTagInterface[]  $tags
     */
    public static function register(array $tags): CustomTagRegistry
    {
        $registry = new CustomTagRegistry;
        $seen = [];

        foreach ($tags as $tag) {
            if (! $tag instanceof CustomTagInterface) {
                throw new \InvalidArgumentException('All items must implement CustomTagInterface');
            }

            $type = $tag->type();
            if (isset($seen[$type])) {
                throw new \RuntimeException(\"Duplicate custom tag type '{$type}'\");
            }
            $seen[$type] = true;

            $registry->register(CustomTagAdapter::toSpec($tag));
        }

        return $registry;
    }
}
```

---

## CustomTagSpec (data contract)
**Location:** `Simai\Docara\CustomTags\CustomTagSpec`

Immutable description of a tag used by the parser and renderer.

- `string $type` - Tag identity used in `!type` / `!endtype`.
- `string $openRegex` - Anchored regex for the opening line; should expose a named capture `(?<attrs>...)` if inline attributes are supported.
- `?string $closeRegex` - Anchored regex for the closing line; `null` means single-line tag.
- `string $htmlTag` - Default wrapper element (e.g., `div`, `section`).
- `array $baseAttrs` - Default attributes merged with inline ones; class values concatenate/deduplicate.
- `bool $allowNestingSame` - Whether the same tag type can be nested.
- `?callable $attrsFilter` - Signature `fn(array $attrs, array $meta): array`; runs early to normalize/whitelist.
- `?callable $renderer` - Signature `fn(CustomTagNode $node, ChildNodeRendererInterface $children): mixed`.

Created by `CustomTagAdapter::toSpec($tag)`.

---

## End-to-end wiring
1. **Config**: `config('tags')` lists tag class short names.
2. **Provider**: `CustomTagServiceProvider` instantiates those tags and calls `TagRegistry::register(...)`, binding `CustomTagRegistry`.
3. **Parser**: `Simai\Docara\Parser` builds the CommonMark environment and installs `CustomTagsExtension` with the bound registry.
4. **Parsing**: `UniversalBlockParser` uses `getSpecs()` to try opens/close per line; on match it creates a `CustomTagNode` and applies early `attrsFilter`.
5. **Rendering**: `CustomTagRenderer` renders nodes with either the per-tag `renderer` or the default wrapper.

---

## Troubleshooting
- **Extension not applied**: ensure `Parser` installs `CustomTagsExtension` and DI provides `CustomTagRegistry`.
- **Tags not recognized**: confirm `TagRegistry::register()` receives instances of your tag classes and that `openRegex()` is not empty (adapter will throw otherwise).
- **Per-tag renderer not called**: ensure the tag's `renderer()` returns a closure and that the registry used by the renderer is the same one used by the block parser.

---

## Testing checklist
- Environment contains our block start parser and node renderer.
- `CustomTagRegistry::getSpecs()` returns the expected set of types.
- Spec lookups by type work during rendering (`CustomTagRenderer` path).
- Single-line tags behave correctly when `closeRegex` is `null`.
- Same-type nesting rule enforced by the block parser using `allowNestingSame`.
