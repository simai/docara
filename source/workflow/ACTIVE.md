# Workflow: language-independent Docara

Date: 2026-07-21
Status: complete
Workflow ID: `2026-07-21-language-independent-docara`
Process model: `full_qa`
Current state: `accepted`
Target state: `review_ready`

## Goal

Make the new declarative Docara language-independent: external language packs,
arbitrary BCP 47 locale registry and fallbacks, separate content trees, one
multi-locale build, language switching and LTR/RTL support.

## Source Of Truth

- workflow:
  `source/workflow/2026-07-21-language-independent-docara.md`;
- evidence:
  `source/workflow/evidence/2026-07-21-language-independent-docara/`.

## Result

Language-neutral manifests, BCP 47 registry/fallbacks, language packs,
five-locale publication, localized catalogue, switcher, alternates and RTL are
implemented and accepted. Full regression, deterministic build, local
publication, rollback and responsive browser checks pass. No push, release or
production claim was made.
