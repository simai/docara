# Спецификация Docara

Этот каталог — человекочитаемая проекция канонического проектного графа
Docara. Он заменяет необходимость восстанавливать замысел по длинной истории
чатов, временным прототипам и текущему коду.

Текущее состояние: ограниченная implementation-задача по общим примерам и
контролю переводов завершена и проверена. Активной implementation-задачи нет.
Следующий переводческий batch, commit, push, tag, публикация пакета и deploy
требуют отдельного явного решения.

## Порядок чтения

1. [Техническое задание](DOCARA-TZ.md) — продукт, границы и итоговый результат.
2. [Архитектура](architecture/UNIFIED-ARCHITECTURE.md) — единственный конвейер
   данных и физическая структура репозитория.
3. [Контракт оболочки](architecture/SHELL-CONTRACT.md) — безопасные bindings,
   shell capabilities и project-owned contributions.
4. [Design Atlas](DESIGN-ATLAS-CONTRACT.md) — детерминированная проекция
   принятых registries и bounded child contracts.
5. [Контент и компоненты](authoring/AUTHORING-CONTRACT.md) — Markdown,
   front matter, локали и синтаксис Smart-компонентов.
6. [План реализации](implementation/ROADMAP.md) — безопасный порядок перехода.
7. [Приёмка](ACCEPTANCE.md) — проверяемые критерии завершения.

Машинный источник состояния находится в [`graph/specs`](../../graph/specs).
Если этот текст и объект графа расходятся, изменение сначала оформляется как
решение в графе, после чего синхронизируется человекочитаемая документация.

## Неподвижные решения

- одна публичная страница одной локали принадлежит ровно одному
  `content/<locale>/<route>.md`;
- `docara.json`, `section.json` и `<page>.page.json` описывают композицию, а не
  редакторский текст;
- локали имеют отдельные деревья `content/<locale>`;
- единственные общие видимые переводы локали находятся в
  `content/<locale>/lang.json`; публичного `resources/i18n` в целевой
  архитектуре нет;
- внутренний Document IR существует только в памяти; сериализация допустима
  лишь как удаляемый cache, поисковый индекс, `--dump-ir` или test evidence;
- все узлы документа рендерятся через один registry;
- все `ui.*` и `docara.*` Smart-компоненты вызываются через один gateway;
- shell bindings выбираются только из typed provider-owned registry, а project
  config не задаёт executable callbacks/classes/templates/paths;
- полная и частичная сборка используют один `PageBuilder`;
- внутренние сообщения CLI/сборщика являются package-owned системным контуром
  и не участвуют в сборке публичных страниц;
- старые Jigsaw/Mix/projector/trusted-HTML пути не являются целевой
  архитектурой и удаляются после доказанной замены;
- обратная совместимость с неопубликованной экспериментальной архитектурой не
  требуется.

## Что не является источником истины

- история текущего или прежних Codex-тредов;
- HTML в `build/`;
- cache Document IR, search index и другие generated-файлы;
- `resources/i18n`, `resources/language-packs/*` и `site.json` как источники
  видимого пользователю текста;
- component catalog или PHP-projector с редакторской прозой;
- визуальные прототипы без принятого решения в спецификации.

## Как начинать новую задачу

Сначала откройте [`source/workflow/ACTIVE.md`](../../source/workflow/ACTIVE.md)
и проверьте фактический Git state. Актуальный onboarding-пакет указан внутри
активного workflow. Старый
`source/handoff/docara-unified-architecture/` является архивной историей и не
задаёт следующий batch. Если пользователь не сформулировал отдельное
`explicit_user_decision`, implementation, version, tag, release, publication и
deploy не начинаются.
