# G1C.1 — portable ABI and exact-host blocker

Status: `BLOCKED_EXACT_SF5_HOST_CONTRACT`
Docara runtime commit: `2d779107add39155edc26537929323aebe066984`
Documentation checkpoint: `5124959521b6ae51c7e5fa925b3c3230a65a54ef`
Pinned SF5 source: `d6f90bba6a9a2f30ac41075d62cf51f1014b7e78`

## Executable full-context probe

`Sf5CrossHostSmartCompatibilityTest` verifies every tracked upstream blob with
`git show <pin>:<path>`, exports that exact revision, and renders one unchanged
`fixture.notice` artifact through Docara and exact SF5. The tracked template
uses title/text plus the selected view and preset. Both hosts preserve the
visible title/text and preset without warnings, but the selected view differs:

```html
Docara: <aside data-fixture-notice data-view="default" data-preset="compact"><strong>Portable title</strong><p>Portable text</p></aside>
SF5:    <aside data-fixture-notice data-view="" data-preset="compact"><strong>Portable title</strong><p>Portable text</p></aside>
```

- exit codes: 0 / 0;
- stderr and warnings: empty / empty;
- Docara HTML SHA-256: `3d49ef1993486c7204de4e44aca78c73339a81951c4051c721eee94ddb84ef34`;
- SF5 HTML SHA-256: `49df999c4ffe1a983bca2f2e16636b731fb608d140be70e538a6d28f87fe2996`;
- `html_byte_equal=false`, `full_context_compatible=false`;
- selected view: Docara `default`, exact SF5 `null`;
- selected preset: Docara/SF5 `compact`;
- render strategy: `server-static` / `server-static`;
- artifact tree SHA-256: `4809847abe679fb4ad5912970356aaf99f9454b0e5668bfc74c4e7f8b8297cab`;
- report SHA-256: `7537ac88feaa2542bf6ecabcc6d57bb2b7c39c4504ecf62ab34293506fa7053e`;
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
