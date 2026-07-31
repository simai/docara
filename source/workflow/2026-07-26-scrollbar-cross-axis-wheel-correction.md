# Scrollbar and code presentation corrections

Date: 2026-07-26
Status: verified locally and published to `https://docara.test/`

## Outcome

Vertical wheel input over a horizontally scrollable code block now continues to
scroll the Docara page. Horizontal wheel input remains owned by the code block.
Vertical navigation scrollbars continue to contain vertical overscroll. Code
blocks no longer show line numbers by default; language, copy, syntax
highlighting and local horizontal scrolling remain intact.

## Root cause

The Framework viewport used the physical declaration
`overscroll-behavior: contain` for every axis. A horizontal code viewport
therefore contained vertical wheel input even though it had no vertical
overflow.

The correction uses logical axes:

- base/vertical viewport: block `contain`, inline `auto`;
- horizontal viewport: block `auto`, inline `contain`.

No Docara wheel handler or product-owned scrollbar workaround was added.

## Exact Framework tuple

- source `simai/ui-loader`: `f67bcaa022da88be8d70d33f958c18871d0afed9`
- builder `simai/ui-builder`: `f9aa00ab2c4646262a85b7f61629e17af1f78ba7`
- generated Core/UI: `27f8af312841af1184944e34f5e12a2092730552`
- generated UI archive SHA-256: `9befa80b783607e3a1ba15defe157d391948cb397a0a26722af38ee56f6aa159`
- generated UI file count: `7144`
- Smart remains: `ab896dc7` (unchanged)
- Docara compatibility id: `sf-v5.3.2-27f8af31-ab896dc7`

The builder reproducibility checks passed twice for both corrections with
byte-identical Core, Component, Utility and Smart outputs. Only exact generated
scrollbar CSS and highlight CSS/JavaScript artifacts were projected into the
`ui` candidates.

## Highlight spacing ownership follow-up

The highlight wrapper no longer duplicates the code viewport padding. Its
computed padding is `0px`; the Docara `pre.p-2` remains the only owner at
`20px` in both light and dark themes. Framework applies its `space-1` fallback
only when the viewport has no explicit padding utility, so product utilities
remain authoritative.

## Code line-number follow-up

The Framework highlight component no longer calls `hljs.lineNumbersBlock`
unconditionally. The low-level plug-in remains available for a future explicit
opt-in contract, but ordinary `native.code` blocks no longer pay the visual and
DOM cost of line-number tables. Docara copy in both language packs and the
Markdown guide was updated to describe the actual default behavior.

## Verification

- `ui-loader`: scrollbar tests `10/10`, full tests `37/37`, diff-check PASS;
- `ui-loader`: highlight presentation contract confirms there is no automatic
  `hljs.lineNumbersBlock` call;
- Docara focused PHPUnit: `146 tests, 2226 assertions`, PASS;
- Docara full PHPUnit: `331 tests, 5128 assertions`, PASS;
- static build: `198` HTML pages and `14236` local references, zero broken links;
- exact generated assets are available from immutable
  `ui@27f8af312841af1184944e34f5e12a2092730552` URLs;
- browser desktop: vertical wheel over normal content `+420px`;
- browser desktop: vertical wheel over horizontal code viewport `+420px`;
- browser desktop: horizontal wheel over code viewport `0 -> 136px`, page
  vertical position unchanged;
- browser mobile `390px`: vertical wheel over code viewport `+497px`;
- highlight wrapper padding: `0px` in both themes;
- Docara code `pre.p-2` padding: `20px` in both themes;
- browser console: zero errors and zero warnings.
- browser `native.code`: language label, copy icon and syntax colors remain;
  line-number column is absent;
- browser Markdown guide: fenced code remains highlighted and copyable without
  line-number columns;

Computed contract on the code viewport is block `auto`, inline `contain`.
Computed contract on vertical navigation viewports is block `contain`, inline
`auto`.

## Publication and rollback

The verified build was copied through a same-filesystem staging directory and
published to `/Users/rim/Sites/docara.test/build_production` after a successful
action gate.

Rollback copies are retained at:

- `/Users/rim/Sites/docara.test/.docara-backups/20260726-1219-scroll-axis/build_production.previous`
- `/Users/rim/Sites/docara.test/.docara-backups/20260726-1219-scroll-axis/build_production.retired`
- `/Users/rim/Sites/docara.test/.docara-backups/20260726-1700-no-line-numbers-final/build_production.previous`

## Boundaries

The source and generated revisions are published feature candidates. This
evidence does not merge them into default branches, create a Framework release,
or claim production readiness. The unrelated LTR/RTL work remains untouched.
