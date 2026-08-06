# S2 integrated acceptance evidence

Date: 2026-08-06
Status: `ready_for_independent_audit`
Exact product candidate: `794fac076be86ed4d03167120800ab0e91715aff`

## Tests and hygiene

- focused Hero/Surface/static contour: 34 tests / 406 assertions;
- full PHPUnit: 501 tests / 10,594 assertions;
- Pint: PASS;
- default Hero absent-media versus explicit-auto: byte-identical;
- existing Goal 1 exact SF5 cross-host contract remains unchanged.

## Exact public builds

Fresh roots:

- `docs/site/build_s2-final-a`;
- `docs/site/build_s2-final-b`;
- `docs/site/build_s2-final-single`, rebuilt only for
  `/ru/components/hero/` after copying full A.

All three contain 393 files and are byte-identical. The canonical algorithm is
SHA-256 over records sorted by relative path, each record formatted as
`<file-sha256><two spaces><relative-path><newline>`. The exact digest is:

`7a8c5645b0bd4c27f85943bc71a86b38e3aed0b7534c71a6b171eb150c9c7554`

Both full roots pass static verification with 266 HTML files, 35,583 local
references and `broken=[]`. Pre-final candidate digests `170ce6…` and
`8d528e3…` are historical implementation checkpoints, not current evidence.

## Deterministic package and fresh consumer

Two independent `git clone --no-local` roots at exact candidate produce the
same verified 869-file package:

- ZIP SHA-256: `94bfda5afac29a5a3cbe65783457b39997116c6f535138e66788dd98bc19f3e0`;
- release manifest file SHA-256:
  `950e360eaadd4ff68d9cc10de24cbf8d8e390aa344f4fe77487e3f519fe7e7b4`;
- checksum file SHA-256:
  `ab52af18aba85a861f986511095a6d4be1fad2d662b0f5bb8cfa37e8cc636049`.

A fresh Composer dist consumer has no package `.git` or `node_modules`, records
engine revision `794fac0`, initializes 78 starter files, passes doctor, full
build, selected Alert build and static verification. Full/single both contain
198 files at digest
`9548afa1488c52c856d4b52fb872dedac2cfe5a48743347f9c3cedcbe3d399ff`;
static checks 78 HTML / 3,931 references / `broken=[]`.

## Exact browser matrix

The isolated production-path Hero demo passed 20/20 scenarios across widths
320/390/768/1024/1440, light/dark, RU LTR and AR RTL. Every scenario proves one
Hero, one decorative image, empty alt, `aria-hidden=true`, no pointer capture,
dark/medium overlay, one H1, keyboard focus to a link, reduced-motion handling,
HTTP 200 assets, no horizontal overflow and zero console/page/request/HTTP
errors.

Representative screenshot hashes:

- RU 1440: `fad91e2fce0d12ed8963583cc242e76bc02a6174e51bb44bb25b778eebd376b6`;
- AR 390: `7e93fa37703b3f0083cb9f4839820aec1585ba163f32a1dd41f41edeb467f4e8`.

The background Hero is isolated evidence only. Existing homepage authoring and
art direction are unchanged.

## Audit boundary

These executor results bind one exact product candidate but do not constitute
independent acceptance. S2 stops at `goal_s2_ready_for_independent_audit`; S3
remains unstarted and unauthorized.
