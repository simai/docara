# Docara Framework scrollbar consumer acceptance

Date: 2026-07-25
Verdict: PASS

## Exact immutable input

- `ui-loader@e6dd3cb8a0cc89169ea5c3ede807f8749b088b94`
- `ui-builder@f9aa00ab2c4646262a85b7f61629e17af1f78ba7`
- `ui@f0b41eb526a8f1daf24a34484143bdfabf7802a4`
- `ui-smart@ab896dc7cd33f151377e3992ffb286769beee7f7`
- compatibility pair:
  `sf-v5.3.2-f0b41eb5-ab896dc7`

## Consumer result

Docara exposes one site-level setting:

```json
{
  "layout": {
    "scrollbar": {
      "preset": "overlay"
    }
  }
}
```

Accepted values are `overlay`, `persistent`, `standard`, and `hidden`.
Omission resolves to `overlay`. The left documentation navigation and the
right page outline use the public Framework composition:

```html
<div class="sf-scrollbar">
  <div class="sf-scrollbar__viewport" tabindex="0">...</div>
</div>
```

For the default preset the redundant `data-sf-scrollbar="overlay"` attribute
is intentionally omitted. Docara owns sticky positioning and content padding;
track, thumb, auto-hide, interaction, theme adaptation and accessibility
remain Framework-owned.

## Automated verification

- `git diff --check`: PASS;
- focused `PortableSiteBuilderTest`: `35 tests, 838 assertions`, PASS;
- full PHPUnit: `331 tests, 5131 assertions`, PASS;
- production build: `90` source pages, PASS;
- static verifier: `198` HTML pages, `11452` local references, `broken: []`;
- generated-versus-served sorted content manifest:
  `7f454e43102b06e36c23a89faa381c496cf18e003994520d3ee7256c345bb77a`,
  identical.

## Live browser verification

URL:
`https://docara.test/ru/authoring/layout-and-navigation/`

- two `.sf-scrollbar` roots are present;
- both roots initialize with `data-sf-scrollbar-ready="true"`;
- left rail: viewport `664px`, content `1245px`, overflow detected;
- right rail: viewport and content `648px`, no overflow;
- the overflowing rail transitions to active on scroll and returns to idle;
- visible Framework thumb is `2px` at rest and is flush with the rail edge;
- the Docara sidebar wrapper has no inline-end padding between the Framework
  track and its `1px` divider;
- light theme uses a dark translucent thumb;
- dark theme uses a light translucent thumb;
- no modal remains open and no stale modal scroll lock is required for the
  scrollbar integration.

## Publication and rollback

- local ServBay publication: `https://docara.test/ru/`, HTTP `200`;
- rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/framework-scrollbar-20260725-204515/build_production.previous`.

## Flush-to-divider correction

The first local publication exposed a `1px` product-shell gap:
`.docara-sidebar` still had `padding-inline-end: var(--sf-px)` between the
Framework root and the divider. The Framework track itself was correctly
positioned at the inline edge.

The redundant Docara padding was removed and protected by the documentation
site and portable site-builder tests.

- focused regression suite: `36 tests, 904 assertions`, PASS;
- rebuilt static verification: `198` HTML pages, `11452` local references,
  `broken: []`;
- live computed geometry: Framework root right edge `392px`, divider inner edge
  `392px`, wrapper padding `0px`;
- corrected local publication: HTTP `200`;
- correction rollback snapshot:
  `/Users/rim/Sites/docara.test/.docara-backups/scrollbar-flush-20260725-212803/build_production.previous`.

## Nonclaims

This acceptance covers the local Docara consumer integration only. It does not
claim a new Framework release, production deployment, completion of the
separate LTR/RTL work, or readiness beyond this bounded scrollbar surface.
