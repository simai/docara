# Собственные project-компоненты

Проект может владеть content Smart и shell contribution как данными в разрешённых roots. Для этого не нужно менять engine `src/` и нельзя указывать callback, class или произвольный template path из Markdown/config.

## Project-owned entries

:::atlas_index {origin=project}
:::

## Content и shell

- content-компонент вызывается из Markdown и возвращает типизированный Smart result;
- shell-компонент подключается через admitted binding capability и LayoutComposer;
- namespace принадлежит проекту и не может неявно заменить package/Framework ID.

## Безопасный путь

```bash
docara inspect smart project.install-builder --json
PLAN_SHA256="$(docara scaffold smart project.example --dry-run --json | php -r '$r=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); echo $r["data"]["plan_id"];')"
docara scaffold --apply="$PLAN_SHA256" --json
docara validate smart project.example --json
docara preview smart --page=/ru/project-demos/ --selector=ui.dropdown --json
docara test smart ui.dropdown --page=/ru/project-demos/ --json
```

Dry-run фиксирует diff и input hashes; apply повторно проверяет exact plan и project root. Preview и test выше используют уже подключённый к странице компонент: новый scaffold сначала нужно явно добавить в Markdown-владельца страницы. Traversal, symlink/hardlink, duplicate namespace, stale plan и запись в engine/generated/lock/external roots отклоняются.

Полный путь разработчика описан в [Developer/AI SDK](/ru/development/developer-sdk/), а принятые полезные сценарии — в [примерах](/ru/examples/).
