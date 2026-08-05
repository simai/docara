# B6 useful-demo semantic correction

Date: 2026-08-05
Status: `ready_for_independent_audit`
Exact product candidate: `c3b91eee71ab906cd79ae7a119c6961664f03528`
Rejected semantic candidate: `e06ff0c945dafd4e9678794773d8bde83c8de535`
Correction parent/handoff: `b66e2c24e71fe9b93efbdf88ed6089d6a047c053`

## Independent RED preserved

- `project.install-builder` rendered an immutable readonly Framework input and
  a checkbox, but its behavior listened only to a duplicate native select.
  Package/version and checkbox state did not affect the command, and no OS
  selector existed.
- `project.product-configurator` rendered a populated admitted dropdown, but
  only feature checkboxes affected the total. Selecting `Командный` changed
  the label while the base remained `2500 ₽`.
- the previous unit/browser evidence asserted presence, assets and absence of
  side effects, not the effect of every displayed control.

The rejected candidate remains immutable history. Exact Framework packets,
locks, providers, registries, Gateway, renderer, LayoutComposer and PageBuilder
were not changed by this correction.

## GREEN implementation

- install builder uses admitted text-only dropdowns for OS and method,
  admitted inputs for package/version, and admitted checkboxes for `--dev` and
  `--prefer-dist`; the duplicate native select and readonly input are absent;
- every control changes one allowlisted local command state. Unsafe package or
  version input sets `data-state=invalid` and disables copy. The implementation
  only writes text to the clipboard and contains no network or command API;
- product dropdown resolves only `Стартовый=2500`, `Командный=4500` and
  `Бизнес=8000`; admitted checkbox options add their allowlisted local prices;
- `ui.dropdown -> ui.list-item(type=text)`, `ui.input` and `ui.checkbox` stay
  unchanged owner artifacts and travel through the accepted provider/registry/
  Gateway/renderer path; raw `items`, raw markup and related icon/avatar/tag
  surfaces remain unsupported;
- pure project-side contracts are exposed only for deterministic tests and
  immediately consumed by the same browser handlers. Invalid tariff, option,
  OS, method, package or version fails closed.

## Focused and full regression

Commands used PHP 8.4.20 from ServBay.

```text
php vendor/bin/phpunit tests/Unit/ProjectExtensionDemoTest.php \
  tests/Unit/FrameworkPortableWaveTest.php \
  tests/Unit/Sf5CrossHostSmartCompatibilityTest.php
PASS: 12 tests, 179 assertions

php vendor/bin/phpunit
PASS: 468 tests, 8,725 assertions

vendor/bin/pint --test
PASS
```

Permanent demo regressions assert exact commands for default, all-options and
Windows PowerShell cases; exact totals for starter, team plus analytics and
business plus all options; invalid state rejection; the absence of backend,
network, payment, order and command execution APIs. Goal B provider, Atlas,
container, security and exact cross-host regressions remain in the full suite.

## Exact browser semantics

Target: disposable initialized project, exact candidate, HTTP
`/ru/project-demos/`. Final clean reload matrix:

- 8/8 scenarios: desktop 1440 and mobile 390 × light/dark × LTR/RTL;
- every scenario produced exact command
  `# Linux\nphp composer.phar require 'acme/docs:~3.1' --dev --prefer-dist`;
- package mutation to `bad;rm` produced `invalid` and disabled copy;
- dropdown `Командный` plus checkbox `Расширенная аналитика` produced
  `5 700 ₽` and `Командный — Расширенная аналитика`;
- dropdown keyboard open focused an option; Escape returned focus;
- clipboard feedback: `Команда скопирована.`;
- horizontal overflow=false; console errors/warnings=[]; request failures=[].

Screenshot SHA-256 values:

| Scenario | SHA-256 |
| --- | --- |
| desktop-light-ltr | `93aacc3c0b901a914b3b3b8a3ed1ff26d65f8ebbbce9894ff59a82c453da0734` |
| desktop-light-rtl | `cb418781c28d91b3e36349ad2b85e81331bdd114a9464c21f696118a9632414b` |
| desktop-dark-ltr | `23d9a14d636304cdfaf34eea3552d0c072af6a089bde40c846efe5db286d993f` |
| desktop-dark-rtl | `19dbd941aedd215d620d02aab48597cf61099cc719ff0c5d37d21406fe754b4d` |
| mobile-light-ltr | `41b0d74a4345536f3b1533e510b5928893defa10068c71d7521bf21b10239bf4` |
| mobile-light-rtl | `3daf4b2f4bdcb10ef934bd450550ff85d32dbfbcb6e3247965f0fc68453e90fa` |
| mobile-dark-ltr | `bf65c7cb4a0525adc208e2cb61ce04788bce145865ac7d0fc41da0e93aff9672` |
| mobile-dark-rtl | `aeee79b829c686988013e0da49b9c8bb7813e9e9c504c39c1be3751465450ab6` |

The screenshots are disposable evidence, not source of truth. The machine
assertions above are the acceptance contract.

## Public build determinism

- clean full A: 104 routes / 307 files / 208 HTML;
- clean full B: 104 routes / 307 files / 208 HTML;
- representative single `/ru/components/alert/`: one rebuilt page and the
  complete accepted tree preserved;
- full A, full B and post-single complete tree SHA-256:
  `1fc8625032cca56da7256b7eaac4981ddae11a3dd8263178337fc55666772274`;
- static verification on both full roots: 208 HTML, 21,844 local references,
  broken=0.

Compared with the exact rejected-candidate public baseline, all 306 public HTML,
asset, search and metadata files are byte-identical. The only changed file is
the internal `.docara/resolved-page-plans.json` provenance receipt, whose engine
revision truthfully binds the new project-demo source. Thus default public
output is byte-identical while the initialized project fixture gains the
intentional demo behavior; no second build path exists.

## Deterministic package and consumers

Two no-local clean clones at exact product candidate produced byte-identical
release artifacts:

| Artifact | SHA-256 / count |
| --- | --- |
| ZIP | `7dc6d43537abdb58a503808ba7fef4dd33d8e7a19b1e82ebe07befcfa109b205` |
| release manifest file | `30fe65461038bc8d45478b84f7781036da07a1b754aec5b1fda040d6ae072b22` |
| checksum file | `4c3cc2eb2d13f74d5cd877f47ffb5c209a602d2112edf3ffb8d129b64b68b75e` |
| package files | `809` |

`scripts/verify-release-package.php` independently passed both copies.

Two fresh Composer dist consumers installed the same ZIP from one immutable
lock (`23f1d6a4af6e62f1c99dbfab1ad9d4317a15da60f4e4909fd431095d26fafe3f`),
initialized without package `.git` or Node, and both produced:

- doctor: success, zero diagnostics;
- full: 39 routes / 196 files / 78 HTML;
- static: 78 HTML / 3,931 references / broken=0;
- selected `/ru/project-demos/`: one page and complete tree preserved;
- identical complete tree SHA-256:
  `8a624eba4a7653313090a3391f01b0cc3fc0b9d70bd052dcef5dbceb1a903ea1`.

Composer 8.4 emitted tool-owned deprecation notices; package installation and
all acceptance commands exited successfully. They are not runtime diagnostics.

## Integrity and rollback

- exact accepted form packet and list-item hashes remain unchanged;
- `docara atlas --json`: success, 44 entries, deterministic Atlas SHA-256
  `7ee7d11ba60c1ec0b1c86198868beed5bfe851211e33bf290bf5854dbf51dea8`;
- `inspect smart ui.dropdown --json` reports exact provider revision
  `7e0b871…`, neutral ABI, accepted form packet `83551f97…` and dependency
  `ui.dropdown -> ui.list-item`;
- `inspect smart ui.list-item --json` reports exact provider revision
  `639d7b67…`, packet `7dbcb161…`, admitted parent `ui.dropdown`, admitted
  `type=text`, and explicit nonclaims `icons`, `avatars`, `tags`,
  `standalone_form_control`;
- no component-ID/namespace dispatch, second parser/renderer/registry/Gateway/
  LayoutComposer/PageBuilder or project executable path was introduced;
- rollback: revert commit `c3b91eee71ab906cd79ae7a119c6961664f03528`
  to correction parent `b66e2c2…`. No legacy surface was deleted;
- Goal C, external writes, merge, push, tag, release and deploy remain
  unauthorized.

Final repository gates after governance synchronization:

- focused Atlas/documentation/provider/demo/binding/composition/structural/
  cross-host matrix: 52 tests / 2,435 assertions;
- Composer validate strict and audit: PASS (no advisories); PHP lint 387;
  tracked JSON 525; YAML 36; Pint PASS;
- project context generate/check: PASS, issues=[];
- project graph: 1 goal / 13 stages / 16 batches / 4 metrics / 8 mappings,
  warnings=0, blockers=0;
- current and candidate-range `git diff --check`: PASS.

This is executor evidence only. Goal B remains
`goal_b_ready_for_independent_audit` and is not self-accepted.
