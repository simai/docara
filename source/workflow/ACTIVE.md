# Active workflow: Framework-native Docara UI

Date: 2026-07-22
Status: review ready
Workflow ID: `2026-07-22-docara-framework-native-ui`
Process model: raw-owner `docara + ux + sf5 + tester + ops`
Current state: `review_ready`
Target state: `accepted`

## Current Goal

Remove Docara presentation overrides that prevent native tags, Framework
utilities, components and Smart components from owning the interface.

## Result

Private control floors, prose typography, Smart-component height overrides and
duplicated button presentation were removed from the canonical publisher.
Static guards, 621 PHPUnit tests, an exact production build, 20,512 static
references and desktop/mobile browser interaction checks passed. The verified
build is published locally with rollback evidence.

The pinned Framework itself still produces a 14 px mobile root and adds outline
button borders outside the nominal height formula. These are upstream producer
gaps, not a reason to add compensating Docara classes. Docara remains pinned to
the immutable revision until a separately tested Framework release exists.

## Completion Guard

Acceptance evidence is stored under
`source/workflow/evidence/2026-07-22-docara-framework-native-ui/`. The reviewer
must distinguish the completed Docara simplification from the separately
required Framework producer correction.

Public push, merge, tag, package release and production readiness remain
excluded.
