# Срез экосистемы: что уже существует

Дата проверки: 8 сентября 2026 года. Метод: чтение исходников, README, документации и Git refs доступных локальных репозиториев. Это архитектурная инвентаризация, не полный runtime-аудит и не подтверждение состояния remote HEAD.

## Источники и зрелость

| Репозиторий | Проверенная опора | Вывод для дорожной карты |
| --- | --- | --- |
| ui-loader | Source checkout; отдельная utility release line | Канонические исходники CSS/Loader/компонентов уже есть. Не создавать новый source owner |
| ui-builder | README и текущий HEAD | Канонический сборщик уже есть. Проверять воспроизводимость и совместимую поставку |
| ui | Сгенерированная release line 5.6.4 | Поставка, не место ручного исправления CSS |
| ui-smart | Git HEAD и роль дистрибутива | Не превращать generated output в независимый source |
| ui-components | Main checkout без коммитов | Не назначать владельцем исходников только по имени папки |
| ui-studio | `docs/developer/mcp-server.md` | Уже есть stdio/HTTP MCP, resources, describe/validate/test/scaffold/apply. Проверять полноту и свежесть, не строить заново |
| ui-vscode | README, генерация autocomplete | Уже есть расширение и hash-checked registry, включая sizing. Полнота всего текущего API не доказана этим фактом |
| ui-play | README, Git HEAD | Существующая площадка примеров/проверки; использовать при conformance-сценариях |
| ui-admin | Git HEAD, структура | Существующий потребитель. Наличие репозитория не доказывает зрелость всех административных сценариев |
| ui-doc | Главная и актуальная изолированная документационная линия | Есть подробный справочник; ценность динамической поставки и AI-пути стоит раскрыть отдельно |
| ui-control | `control/CURRENT.yaml`, `control/REPOSITORIES.yaml`, README, AGENTS | Есть dispatcher и compatibility ledger. CURRENT датирован июлем; нужен reconciliation, не новый control plane |
| Docara | `src/Application/McpAdapter.php`, `tools/mcp-docara/server.php`, developer SDK docs | Есть локальный authoring MCP. В проверенной стабильной линии не найден автоматический публичный knowledge export |
| larena-specs | README; DNA standard; AI-first contract | Есть canonical DNA и 43 specified package identities; AI-first contract — proposed |
| larena-workspace | README, текущий HEAD | Оркестрация workspace существует; наличие пакета не равно приёмке |
| larena | README, текущий HEAD | Реальный Laravel-потребитель/приложение; не проверялся как полный production-профиль в этой работе |

### Важное различие Larena

Текущий README `larena-specs` сообщает: 43 specified identities, 42 workspace-present, 25 root-required/locked; `larena/telephony` — specification-only. Там же разделены `specified`, `semantic_reviewed`, `graph_valid`, `implementation_plannable`, `workspace_present`, `runnable`, `independently_accepted`, `production_eligible`.

Эти числа — сведения проверенного источника, а не пересчёт всех запущенных пакетов или независимая приёмка. Они не дают основания писать «43 готовых модуля».

## Точные локальные опоры

| Источник | Revision | Замечание |
| --- | --- | --- |
| ui-control main | `fad9bd2e11ef983cb99f7ecb6946748c56821e20` | CURRENT от июля |
| ui-studio main | `7b8167a093220b0524c008a0d09f4df3cd21b97d` | MCP описан в developer docs |
| ui-vscode main | `a8fd4a8dcd75ce53d2af0c07186fb0ef3b907058` | Не новый отсутствующий инструмент |
| ui-builder main | `96b56d2a4e5bd4e3be3f839ffebf205ba7fa77c2` | Source baseline |
| ui-loader main | `5365ecc48bcaf4dabf4484812a9b0b81ba2d44d5` | Есть незавершённые изменения |
| utility source release line | `19db6d2d786a06e2718c3bb848d616a987e81a44` | Новее проверенного main по utility-работе |
| ui release 5.6.4 | `833b9ab11ed59c8ee3443ce365faf00e2d9fcaea` | Не считать автоматически принятым всеми потребителями |
| Docara stable 2.8.3 | `8c82a0f9aedde98a1cc093fba44b2900bc8fecfb` | Проверенная изолированная линия |
| ui-doc utility line | `1210825f7f9ff574e4c617dc7ab542b528c7bc68` | Отдельно от грязного main |
| larena-specs | `8aee02616effe7aae700b8f50c7fc6cd354b63e9` | Действующая DNA не заменяется этим аудитом |
| larena-workspace | `c84477fc8b3fec60f0c1e73f48c45fab9c25d5dd` | Workspace source |
| larena | `c26f10f911166941e380c413b1bfac00f8764347` | Application source |

Точные хеши позволяют воспроизвести чтение источников. После изменения исходников этот документ остаётся историческим срезом, не бесконечно актуальным dashboard.

## Подтверждённые различия AI-интерфейсов

### UI Studio

В документации объявлены resources `smart://registry`, schemas, manifests, fixtures, scenarios, AI packs и source; инструменты `smart.search`, `smart.describe`, `smart.validate`, `smart.test`, `smart.scaffold`, `smart.apply`. Описаны digest/revision/readiness и проверка изменений перед apply.

Приведённый адрес `https://ui-studio.test/mcp` относится к локальной установке. Доступность через интернет и возможность подключения облачного клиента из наличия этого адреса не следуют. В этой работе HTTP runtime не принят отдельным сетевым smoke.

### Docara

`McpAdapter` предоставляет инструменты SDK для inspection, validation, scaffold, QA и documentation tracking. Запись отдельно включается для процесса. Это средство работы с проектом, а не сервис чтения любого опубликованного сайта.

Отсутствие найденного public export в стабильном `src` означает конкретный gap этой проверенной линии. Оно не доказывает отсутствие экспериментальной реализации во всех ветках, графах и частных репозиториях.

## Что исправлено относительно прежнего сравнения

1. Основной критерий — пригодность для динамически настраиваемых тиражных решений и сквозной разработки, а не конкуренция с Tailwind.
2. UI Studio/MCP и VS Code extension уже существуют.
3. Larena уже имеет ДНК и AI-first proposal; общий документ не начинает backend-архитектуру с нуля.
4. Отсутствие capability, неполнота её покрытия и отсутствие подтверждённой публикации — разные задачи.
5. Docara и UI-документация являются практическим кейсом AI-assisted разработки по свидетельству владельца. Количественные преимущества пока не измерялись в этом аудите.

## Внешние первичные источники

- [Tailwind: обнаружение классов в исходниках](https://tailwindcss.com/docs/detecting-classes-in-source-files) — build-time модель, не генерация на каждый запрос.
- [MCP: транспорт](https://modelcontextprotocol.io/specification/2025-11-25/basic/transports) — stdio и Streamable HTTP; статический JSON не становится MCP endpoint.
- [Предложение llms.txt](https://llmstxt.org/) — discovery-формат, не гарантия поддержки каждым AI-клиентом.

## Следующий reconciliation ui-control

### Дополнительная сверка сквозной композиции, 8 сентября

- `larena-specs/docs/implementation-planning/canonical-page-composition-contract.md`: accepted implementation contract `larena.layout.page_composition`; неисполняемый JSON структуры отдельно от Content; logical assets; presentation model → Smart → asset graph → итоговый HTML. Документ не равен runtime-приёмке.
- `larena-specs/docs/implementation-planning/frontend/layout-page-builder-reference.md`: reference/draft; полезен для терминологии, но не заменяет accepted контракт.
- Docara `docs/site/content/ru/development/composition-extensions.md`: `Layout → Region → Section → Slot → Block → Smart / безопасный element`, DesignRegistry, зарегистрированные bindings. `settings/levels-and-inheritance.md`: разрешение настроек site → section → page.
- `bx-simai.main`, HEAD `d6f90bba6a9a2f30ac41075d62cf51f1014b7e78`: документы `block-and-smart.md`, `runtime-bridge-facades.md`, `settings-facade.md`; в `local/modules/simai.main/lib/ui/smart.php` присутствуют `component`, `button`, `render`. Документация ряда фасадов отмечена partial; новая live-приёмка Bitrix не выполнялась.

Чтение выполнялось по локальным файлам: HEAD — ориентир репозитория, не утверждение о чистоте всего рабочего дерева. На основании этой сверки расширена общая ДНК; продуктовые контракты и runtime не переписаны.

### Действие координации

Сохранить исторический CURRENT/evidence; добавить датированный указатель на этот срез; проверить remote refs, source-to-distribution provenance и consumer locks отдельно. Только после этих проверок обновить operational current tuple. Backend refs учитывать как зависимые источники информации, не как владение dispatcher.

## Проверка подготовленной документации

После добавления новых страниц полная локальная сборка ui-doc создала 914 страниц. В report mode выданы 64 замечания authoring review, 696 translation tracking и 335 documentation tracking. Это диагностический backlog проверенной рабочей сборки, а не доказательство 335 сломанных API или 696 новых ошибок этого изменения. Поэлементная классификация в этот обзор не входила.

Практическое следствие для roadmap: сначала разобрать существующую очередь tracking и связать записи с владельцами, а не создавать ещё один механизм контроля актуальности.
