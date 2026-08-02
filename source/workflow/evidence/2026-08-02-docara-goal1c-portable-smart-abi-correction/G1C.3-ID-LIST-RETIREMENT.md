# G1C.3 — central component-ID retirement

Status: `PASS`
Implementation: `2d779107add39155edc26537929323aebe066984`

The accepted Goal 1 scope no longer contains `ui.alert`, `ui.button` or another
component-specific dispatch/list in:

- `PortableSearchTextExtractor` — generic visible rendered text plus semantic
  attributes;
- `DocumentParser` — provider registry keys, not a fixed pair;
- `FrameworkConsumerPolicy` — exact-lock records;
- `SmartRenderer` — adapter/strategy/template ABI registries only.

The structural scan reports PASS for all four files. The only remaining
Alert/Button allowlist is `RegionCompositionResolver.php:240`; it owns shell
region composition and is explicitly deferred to Goal 2 Design Registry. Goal
1 does not claim it removed or replaced that boundary.

No second parser, renderer, Gateway or PageBuilder was added.
