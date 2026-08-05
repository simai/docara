---
title: Путь разработчика и AI-агента
description: Один безопасный цикл discover, plan, preview, dry-run, hash-bound apply и validate.
tags: [development, sdk, mcp, ai, safety]
---

# Путь разработчика и AI-агента

Все шаги ниже делегируют одним application services. Human CLI, `--json` и optional MCP не имеют отдельных validation/rendering rules.

## 1. Discover

```bash
docara doctor --json
docara list smart --json
docara atlas --json
docara inspect smart project.install-builder --json
docara schema smart --json
```

Read operations по умолчанию ничего не меняют. Result содержит stable operation/subject/code/exit, owner, safe source/location, provenance и suggestion.

## 2. Plan и preview

```bash
PLAN_SHA256="$(docara scaffold smart project.notice-card --dry-run --json | php -r '$r=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo $r["data"]["plan_id"];')"
docara preview smart --page=/ru/project-demos/ --selector=ui.dropdown --json
docara test smart ui.dropdown --page=/ru/project-demos/ --json
```

Dry-run возвращает exact target paths/content, input hashes и `plan_id`. Preview вызывает production `PreviewKernel/PageBuilder`, публикуется изолированно и не проходит production verifier.

## 3. Hash-bound apply

```bash
docara scaffold --apply="$PLAN_SHA256" --json
docara validate smart project.notice-card --json
```

Apply разрешён только после явного dry-run и повторной проверки всех hashes. Stale plan, changed input, existing/case-colliding target, traversal, symlink/hardlink, engine/lock/generated/external root завершаются non-zero без partial/outside mutation.

## CLI, JSON и MCP

| Действие | Human/JSON operation | Optional MCP tool | Один service |
| --- | --- | --- | --- |
| Atlas | `docara atlas [--json]` | `docara_atlas` | `DesignAtlasService` |
| Inspect | `docara inspect … [--json]` | inspect tool | discovery/SDK service |
| Schema | `docara schema … [--json]` | schema tool | schema service |
| Scaffold plan/apply | `docara scaffold …` | plan/apply tool | `ScaffoldService` |
| Validate/test/QA | `docara validate|test|qa …` | matching tools | accepted validators/PreviewKernel |

MCP stdio adapter запускается отдельно и read-only. `--allow-writes` лишь открывает тот же hash-bound apply; project-root guard и diagnostics не обходятся. Node/browser нужны только optional visual QA, не `init/build/verify-static`.

## Rejected operation и recovery

```text
SDK_WRITE_PATH_UNSAFE
source: .docara-preview
pointer: /operations/preview/output
suggestion: remove the unsafe symlink and repeat dry-run
```

Не правьте plan/report/receipt. Устраните причину в project-owned source, повторите discover/validate/dry-run и получите новый content-addressed plan.
