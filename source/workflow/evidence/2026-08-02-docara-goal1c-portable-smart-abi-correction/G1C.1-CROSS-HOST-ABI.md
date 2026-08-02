# G1C.1 — portable ABI and exact-host blocker

Status: `BLOCKED_EXACT_SF5_HOST_CONTRACT`
Docara runtime commit: `2d779107add39155edc26537929323aebe066984`
Documentation checkpoint: `5124959521b6ae51c7e5fa925b3c3230a65a54ef`
Pinned SF5 source: `d6f90bba6a9a2f30ac41075d62cf51f1014b7e78`

## Proven subset

`Sf5CrossHostSmartCompatibilityTest` verifies every tracked upstream blob with
`git show <pin>:<path>`, exports that exact revision, and renders one unchanged
`fixture.notice` artifact through Docara and exact SF5. The props-only template
produces under both hosts:

```html
<aside data-fixture-notice><strong>Portable title</strong><p>Portable text</p></aside>
```

- exit codes: 0 / 0;
- stderr and warnings: empty / empty;
- HTML SHA-256: `0273ded8d5be7cf89f89289323659322a87a2df5468b3f39314b15417e99ca65`;
- render strategy: `server-static` / `server-static`;
- report SHA-256: `f67abd0bb9ba3c7d61cd9c5dfddd792951daf6352c80c597f32844cd8339c473`;
- report contains only repository-relative artifact paths.

Reproduction:

```bash
DOCARA_SF5_SOURCE_REPO=/Users/rim/Documents/GitHub/bx-simai.main \
DOCARA_SF5_CROSS_HOST_REPORT=/tmp/cross-host.json \
vendor/bin/phpunit --filter Sf5CrossHostSmartCompatibilityTest
```

## Mandatory context blocker

The props-only equality is not sufficient for Goal 1 acceptance. Read-only
inspection of the same immutable source proves two contradictions:

1. `Smart::renderTreeNode()` resolves `$view` as an artifact record, then line
   350 assigns `$view = (string)($node['view'] ?? '')`. At the template call,
   `is_array($view['data'] ?? null)` is therefore false, so the selected view
   data is replaced by `[]`.
2. `Smart::nodeFromRenderOptions()` reserves and forwards `view`, `preset` and
   `children`, but not `slot`; `Smart::render(..., ['slot' => ...])` therefore
   moves slot into props and the template `$slot` remains empty.

Reproduction:

```bash
git -C /Users/rim/Documents/GitHub/bx-simai.main show \
  d6f90bba6a9a2f30ac41075d62cf51f1014b7e78:local/modules/simai.main/lib/UI/Smart.php \
  | sed -n '265,290p;330,375p'
```

The pinned documentation promises normalized `manifest`, `view`, `preset`,
`props`, `childrenHtml`, `slot`, so this is a host implementation defect, not
permission to create a Docara dialect. A new exact SF5 revision or a separately
approved contract reduction is required.
