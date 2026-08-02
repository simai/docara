# G1C.1 — portable ABI and exact-host blocker

Status: `BLOCKED_EXACT_SF5_HOST_CONTRACT`
Docara runtime commit: `2d779107add39155edc26537929323aebe066984`
Documentation checkpoint: `5124959521b6ae51c7e5fa925b3c3230a65a54ef`
Executable context-probe commit: `c82dba1859a9dab5f4318c37f2bd7b7dfdab239e`
Pinned SF5 source: `d6f90bba6a9a2f30ac41075d62cf51f1014b7e78`

## Executable full-context probe

`Sf5CrossHostSmartCompatibilityTest` verifies every tracked upstream blob with
`git show <pin>:<path>`, exports that exact revision, and renders one unchanged
`fixture.notice` artifact through Docara and exact SF5. The tracked template
uses title/text plus the selected view and preset. Both hosts preserve the
visible title/text and preset without warnings, but the selected view differs:

```html
Docara: <aside data-fixture-notice data-view="default" data-preset="compact" data-slot="content"><strong>Portable title</strong><p>Portable text</p></aside>
SF5:    <aside data-fixture-notice data-view="" data-preset="compact" data-slot=""><strong>Portable title</strong><p>Portable text</p></aside>
```

- exit codes: 0 / 0;
- stderr and warnings: empty / empty;
- Docara HTML SHA-256: `7133c5dcd44aa85f351a85c61c280aa883abd5cdb3c91206168ad63ada497b38`;
- SF5 HTML SHA-256: `9e0cfeb4f6332d54d457cecb9e06a774f9e465d7933c89e70881fc5cf75ce6fe`;
- `html_byte_equal=false`, `full_context_compatible=false`;
- selected view: Docara `default`, exact SF5 `null`;
- selected preset: Docara/SF5 `compact`;
- slot through the public render shortcut: Docara `content`, exact SF5 `null`;
- render strategy: `server-static` / `server-static`;
- artifact tree SHA-256: `eb6fd28f295c360fa80375beb21aba634c14d2466699ca55509b64ac39f2d058`;
- report SHA-256: `c2ff49b0234188aecb1521eeb329e143a5da512d2912994ddfdd3918b8db81e0`;
- report contains only repository-relative artifact paths.

Reproduction:

```bash
DOCARA_SF5_SOURCE_REPO=/Users/rim/Documents/GitHub/bx-simai.main \
DOCARA_SF5_CROSS_HOST_REPORT=/tmp/cross-host.json \
vendor/bin/phpunit --filter Sf5CrossHostSmartCompatibilityTest
```

## Mandatory context blocker

The executable mismatch and read-only inspection of the same immutable source
prove two contradictions:

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

## Disposable remediation proof

`sf5-smart-v1-host-context.patch` is a three-change proposal against the exact
pin: reserve/forward `slot` and stop overwriting the resolved view record. It
applies cleanly with `git apply --check`; patch SHA-256 is
`1baa8a37d6ddebeb0378593ff7d2706f61cc12f45e0cd39821aca09d2536faf8`.

The second integration test applies those changes only inside a disposable
`git archive` export and reruns the same tracked artifact. Result: Docara and
patched SF5 HTML are byte-identical, slot hydration is `content`, warnings and
stderr are empty. This proves the blocker has a bounded correction; it does not
create or authorize a new Framework revision and is not Goal 1 acceptance.
