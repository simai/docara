# Active workflow: Docara single content pipeline

Date: 2026-07-30
Status: active
Workflow ID: `2026-07-30-docara-single-pipeline`
Track: `docara-consolidation`

## Current Goal

Replace the parallel authored/generated rendering paths with one pipeline:

`Markdown -> typed Document IR -> Node Renderer Registry -> Smart Component Gateway -> Layout Composer -> HTML`.

Every public page must have one physical Markdown source. Configuration JSON
controls composition only; language packs contain UI messages only.

## Current Batch

`B1-badge-single-pipeline`: провести физическую страницу
`content/ru/components/badge.md` через новый IR, registry, Smart gateway и
общий PageBuilder в полном и частичном режимах.

## Stages

- [x] `contract` — границы source of truth и baseline зафиксированы.
- [ ] `vertical-slice` — одна физическая Markdown-страница проходит единый
  pipeline.
- [ ] `content-migration` — публичные страницы и локали перенесены в Markdown.
- [ ] `legacy-retirement` — параллельные generated/trusted-HTML пути удалены
  после parity evidence.
- [ ] `acceptance` — полная матрица сборки, ссылок, локалей и браузеров пройдена.

## Batches

- [x] `B0-contract-baseline` — inventory, ADR и воспроизводимый baseline.
- [ ] `B1-badge-single-pipeline` — typed IR, registry, Smart gateway и общий
  PageBuilder для страницы бейджа.
- [ ] `B2-component-content-migration` — остальные component detail pages.
- [ ] `B3-language-pack-boundary` — language packs содержат только UI copy.
- [ ] `B4-public-content-migration` — остальные public routes и locale trees.
- [ ] `B5-legacy-retirement` — удаление projector/trusted HTML после parity.
- [ ] `B6-acceptance` — документация и итоговая verification matrix.

## Done When

- every public document route maps to physical Markdown;
- full and partial builds use one PageBuilder and produce identical page HTML;
- language packs contain no documentation prose;
- one Smart gateway handles Framework and Docara components;
- legacy generated and trusted-HTML paths are removed after parity evidence;
- tests, deterministic build, static verification and browser acceptance pass.

## Evidence

- workflow: `source/workflow/2026-07-30-docara-single-pipeline.md`;
- launch: `source/workflow/2026-07-30-docara-single-pipeline.launch.yaml`;
- evidence root:
  `source/workflow/evidence/2026-07-30-docara-single-pipeline/`.

## Boundary

No destructive cleanup before parity. No merge, tag, release, public deploy or
production-readiness claim without a separate gate.
