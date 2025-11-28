---
extends: _core._layouts.documentation
section: content
title: CustomTagExtension & Registries
description: CustomTagExtension & Registries
---

# CustomTagExtension & Registries

This page explains how custom tags are **wired into League CommonMark** and how the **registries** provide specs to the parsing/rendering pipeline.

---

## Components overview
- **CustomTagExtension** — The CommonMark extension that installs our parsers and renderers.
- **CustomTagRegistry** — Runtime registry of `CustomTagSpec` objects (one per tag type), used by parsers/renderers.
- **TagRegistry** — Factory/bridge that accepts tag class instances and produces a `CustomTagRegistry` (via the adapter).
- **CustomTagSpec** — Immutable data object describing a tag: regexes, wrapper, defaults, hooks.

---

## CustomTagExtension
**Role:** Register our block start parser and node renderer with the CommonMark environment.

**Typical shape:**
```php
namespace App\Helpers\CommonMark;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

final class CustomTagExtension implements ExtensionInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function register(EnvironmentBuilderInterface $env): void
    {
        // Block start parser which recognizes !type / !endtype
        $env->addBlockStartParser(new UniversalBlockParser($this->registry), 100);

        // Renderer for the AST node
        $env->addRenderer(CustomTagNode::class, new CustomTagRenderer($this->registry), 0);

        // (Optional) Inline parser if/when implemented
        // $env->addInlineParser(new UniversalInlineParser($this->registry));
    }
}
```

**Notes**
- The **priority** (`100`) ensures our block start parser runs early enough before conflicting parsers.
- The extension is usually installed by your project `Parser` during environment setup.

---

## CustomTagRegistry
**Role:** Provide fast lookup of specs **by type** and enumerate all specs for scanning.

**Expected API (illustrative):**
```php
final class CustomTagRegistry
{
    /** @var array<string, CustomTagSpec> */
    private array $byType;

    /** @param iterable<CustomTagSpec> $specs */
    public function __construct(iterable $specs)
    {
        $map = [];
        foreach ($specs as $spec) $map[$spec->type] = $spec;
        $this->byType = $map;
    }

    /** @return iterable<CustomTagSpec> */
    public function getSpecs(): iterable { return $this->byType; }

    public function get(string $type): ?CustomTagSpec { return $this->byType[$type] ?? null; }
}
```

**Where it’s constructed:** In `bootstrap.php`, via `TagRegistry::register([...$instances])`, then bound in the container and injected into the `CustomTagExtension`/`Parser`.

---

## TagRegistry (factory)
**Role:** Convert tag **classes** (extending `BaseTag`) into a **runtime registry** of specs.

**Typical shape:**
```php
final class TagRegistry
{
    /**
     * @param CustomTagInterface[] $tags
     */
    public static function register(array $tags): CustomTagRegistry
    {
        $registry = new CustomTagRegistry();
        $seen = [];

        foreach ($tags as $tag) {
            if (!$tag instanceof CustomTagInterface) {
                throw new \InvalidArgumentException('All items must implement CustomTagInterface');
            }

            $type = $tag->type();
            if (isset($seen[$type])) {
                throw new \RuntimeException("Duplicate custom tag type '{$type}'");
            }
            $seen[$type] = true;

            $registry->register(CustomTagAdapter::toSpec($tag));
        }

        return $registry;
    }
}
```

**Why a factory?** Centralizes conversion (`tag` → `spec`), validates input types, **prevents duplicate `type()` collisions**, and populates the runtime registry incrementally.

---

## CustomTagSpec (data contract)
**Role:** Immutable description of a tag used by the parser and renderer.

**Fields (as used across the codebase):**
- `string $type` — Tag identity used in `!type` / `!endtype`.
- `string $openRegex` — Anchored regex for the opening line; must expose a named capture `(?<attrs>...)` if inline attributes are supported.
- `?string $closeRegex` — Anchored regex for the closing line; `null` means **single‑line** tag (closes immediately).
- `string $htmlTag` — Default wrapper element (e.g., `div`, `section`).
- `array $baseAttrs` — Default attributes merged with inline ones; `class` values concatenate/deduplicate.
- `bool $allowNestingSame` — Whether the same tag type can be nested.
- `?callable $attrsFilter` — Signature `fn(array $attrs, array $meta): array`; runs early to normalize/whitelist.
- `?callable $renderer` — Signature `fn(CustomTagNode $node, ChildNodeRendererInterface $children): mixed`.

**Created by:** `CustomTagAdapter::toSpec($tag)`.

---

## End‑to‑end wiring
1. **Config** lists tag class short names under `tags`.
2. **bootstrap.php** materializes tag instances, calls `TagRegistry::register($instances)`, and binds `CustomTagRegistry`.
3. **Parser** builds the CommonMark environment and installs **CustomTagExtension** with the bound registry.
4. **UniversalBlockParser** uses `getSpecs()` to try opens/close per line; on match it creates a `CustomTagNode` and applies early `attrsFilter`.
5. **CustomTagRenderer** renders nodes with either the per‑tag `renderer` or the default wrapper.

---

## Troubleshooting
- **Extension not applied**: verify your project `Parser` installs `CustomTagExtension` and that DI provides `CustomTagRegistry`.
- **Tags not recognized**: ensure `TagRegistry::register()` receives instances of your tag classes and that `openRegex()` is not empty (adapter will throw otherwise).
- **Per‑tag renderer not called**: confirm `renderer` is set on the **spec** (i.e., returned from the tag), and that the registry used by the renderer is the same one used by the block parser.

---

## Testing checklist
- Environment contains our block start parser and node renderer.
- `CustomTagRegistry::getSpecs()` returns the expected set of types.
- Spec lookups by type work during rendering (`CustomTagRenderer` path).
- Single‑line tags behave correctly when `closeRegex` is `null`.
- Same‑type nesting rule enforced by the block parser using `allowNestingSame`.

