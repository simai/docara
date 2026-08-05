# Собственные project-компоненты

Проект может владеть content Smart и shell contribution как данными в разрешённых roots. Для этого не нужно менять engine `src/` и нельзя указывать callback, class или произвольный template path из Markdown/config.

## Project-owned entries

:::atlas_index {support=project}
:::

## Content и shell

- content-компонент вызывается из Markdown и возвращает типизированный Smart result;
- shell-компонент подключается через admitted binding capability и LayoutComposer;
- namespace принадлежит проекту и не может неявно заменить package/Framework ID.

## Безопасный путь

```bash
docara inspect smart project.install-builder --json
docara scaffold smart project.example --dry-run --json
docara scaffold smart project.example --apply-plan=<plan-id> --json
docara validate smart project.example --json
docara preview smart project.example --page=/ru/examples/ --json
docara test smart project.example --page=/ru/examples/ --json
```

Dry-run фиксирует diff и input hashes; apply повторно проверяет exact plan и project root. Traversal, symlink/hardlink, duplicate namespace, stale plan и запись в engine/generated/lock/external roots отклоняются.

Полный путь разработчика описан в [Developer/AI SDK](/ru/development/developer-sdk/), а принятые полезные сценарии — в [примерах](/ru/examples/).
