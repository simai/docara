# Дорожная карта упрощения Docara

Текущий статус: выполняется bounded batch профилей страниц и page SDK по
`source/workflow/2026-08-26-page-authoring-sdk.md`. Он добавляет один
необязательный `docara.authoring.json`, шесть встроенных профилей и тип `page`
в существующие SDK-операции. Отдельный knowledge subsystem, `ui-doc`, commit,
push, release и deploy исключены.

Предыдущий roadmap и post-roadmap implementation завершены. Goal 1-3,
Goals A-C, Surface/Hero Goals S1-S3, SF5 typography и local Framework runtime
имеют принятую evidence chain. `ui-doc` мигрирован в content-only consumer,
standalone Docara v2 сведена на `main`, а legacy repositories закрыты отдельным
verified retirement workflow.

Терминальное состояние и критерии нового входа находятся в
`source/workflow/ACTIVE.md`. Исторические revision и baseline ниже остаются
evidence прошлого closeout и не являются версией, tag или release
authorization.

Исторический R2 `PASS_DISPOSABLE_CORRECTED` и unpublished `2.0.0-rc.3`
остаются parked evidence, а не текущим кандидатом или действием. Единственный
вход в новый lifecycle-контур — отдельный `explicit_user_decision`; до него
version, tag, release, publication и deploy не разрешены.

Переход выполняется вертикальными срезами. Цель — не переписать весь код за
один раз, а доказать новый единственный конвейер на одной реальной странице,
после чего последовательно удалить старые пути.

## Goal S1. Full-bleed Geometry & Shared Surface Runtime

Статус: `independently_accepted`. Exact product candidate `ac53ea4…`.
Один typed `docara.surface` использует существующий Markdown -> typed IR ->
renderer registry -> Smart Gateway -> LayoutComposer -> PageBuilder path.
Landing full-bleed и docs main-bounded geometry доказаны; Hero HTML не изменён.
Глубина container contract считается относительно каждого container root;
Surface -> Grid -> Card проходит точные границы 3/3 и 2/2.
Fresh full/full/single и clean-clone evidence сходятся на path-sorted canonical
digest `650a678c…`; прежний `90bf6378…` отвергнут как unreproducible evidence.
Independent verdict: `PASS`; Goal S2 entry gate открыт.

## Goal S2. Hero Background Media

Статус: `independently_accepted_pass_with_notes`. Закрытый
`media=auto|side|background|none` контракт существующего Hero реализован на
`7eeba4a…`. Default byte-identical, background делегирует geometry/media/overlay
принятому shared Surface presentation, а unsafe image diagnostics указывают на
реальную строку Markdown image. Full/full/single digest `108cba01…`, verified
ZIP `40d86ea6…`, fresh consumer и proportional browser regression зелёные.
Rejected candidate `794fac0…` остаётся historical. Независимый verdict —
`PASS_WITH_NOTES`; exact package tag parameter — `v2.0.0-alpha1-s2c1`.

## Goal S3. Shared Adoption, Public Documentation & Integrated Acceptance

Статус: `complete_ready_for_user_decision`. Surface, Hero, Showcase и Promo
используют один `SurfacePresentation`, сохраняя собственную семантику и frozen
default bytes. Публичные authoring/component/design guides описывают Surface vs
Hero, запрет double wrapping, safe local background и failure boundaries.
Fresh full/full/single digest — `99ab56df…`, verified ZIP — `4c5496b3…`;
fresh consumer и 30 browser scenarios зелёные. S4/Goal D не существует, release
и deploy не авторизованы.

## Нулевая точка

- исходная revision: `a3ba9a4d04429f1f2046b8415764fe7bc89962c7`;
- ветка спецификации: `codex/docara-unified-architecture`;
- текущий старый runtime остаётся временным read-only baseline;
- никакая готовность к релизу или production не заявляется;
- несовместимые изменения допустимы: Docara 2 ещё не опубликована.

## M0. Зафиксировать контракт и карту реализации

Результат:

- утверждены ТЗ, authoring contract, архитектура и acceptance matrix;
- каждый целевой модуль сопоставлен текущим классам, тестам и deletion gates;
- сняты воспроизводимые baseline-артефакты для `components/badge`;
- запрещено добавлять новый контент в language packs и projectors;
- принят отдельный `badge_source_ready` gate: он разрешает M2 после закрытия
  badge-среза и zero-growth границ, не выдавая ложный PASS глобальной миграции.

На этом этапе продуктовый runtime не переписывается.

## M1. Закрыть границы источников

Статус: реализован ограниченными checkpoint M1A и M1B; переход к M2 разрешён
только scoped gate `docara.gate.badge_source_ready`, не глобальным
`source_ownership`.

Результат:

- публичная страница требует физический Markdown-файл;
- config schemas отклоняют prose, HTML и CSS;
- `content/<locale>/lang.json` является единственным public i18n source;
- public `resources/i18n` и `site.json` исключены без compatibility layer;
- package-owned CLI/build messages отделены от public build inputs;
- component manifest не может владеть текстом своей документационной страницы;
- generated/cache каталоги явно объявлены disposable.

Ключевые тесты:

- route collision;
- route без Markdown;
- forbidden content in config/language pack/manifest;
- удаление cache + полное воспроизведение результата;
- отсутствие обязательных page IR JSON/JSONL.

## Goal 1. Portable Smart Runtime

Статус: независимо принят `PASS_WITH_NOTES`. Exact runtime candidate
`44acc1ff…`, SF5 adapter `b3cdff87…`; cross-host fixture byte-identical.

## Goal 2. Project Design Registry and Preview

Статус: независимо принят `PASS_WITH_NOTES`. Один
DesignRegistry владеет package/project design artifacts, composition не знает
конкретных IDs, а Smart/region/layout/page preview извлекается из normal
PortableSiteBuilder output. Первый candidate `33a3777…` отклонён независимым
аудитом: production verifier принимал preview receipt, isolated output не имел
assets, watch closure была широкой, а schema `oneOf` не исполнялась. Goal 2-C
исправил эти четыре outcomes; exact accepted product/docs candidate —
`39f1e3f…`, handoff — `adb27f1…`.

## Goal 3. Developer/AI SDK, structured QA and optional MCP

Статус: независимо принят `PASS`. Один application
result обслуживает human и JSON CLI. Discovery читает существующие
Smart/Design registries. Scaffold
работает через hash-bound dry-run/apply только в project-owned roots.
Validate/test/QA делегируют принятые validators и PreviewKernel. Optional MCP —
отдельный PHP stdio adapter над теми же services, read-only по умолчанию и без
normal consumer dependency. Exact package/consumer, full/single/static и
isolated Smart/region/layout browser/QA evidence зелёные. Все generated write
roots проверяются до первой filesystem mutation; независимая приёмка ещё не
заявлена. Третий integrity correction запрещает nullable file coordinates,
пересчитывает canonical QA identities, связывает их с exact preview bytes и
проверяет candidate/reference PNG в PHP verifier независимо от report counters.
Bounded UI extension сохраняет тот же pipeline: Framework-owned
`--sf-radius--ui` становится общей базой малых controls, reader settings
выбирают только default/medium/large, а transient search/settings modals по
умолчанию используют `backdrop-blur-none`. Goal 3D correction дополнительно
связывает публичный `schema smart` с реальным portable scaffold manifest и
нормализует inspect provenance всех providers на neutral
`sf.smart_artifact_abi`; Goal 3E заменяет локальный dialect byte-exact owner
schema с SHA-256 `9d65a9b…`, а Docara cross-field admission остаётся отдельной
policy. Legacy storage alias и host template adapter остаются отдельными
provenance facts. Exact accepted product candidate — `1e571b6…`.

## Goal A. Shell Contract & Safe Configuration

Статус: независимо принят `PASS`. Goal A заменяет закрытый
список shell bindings одним типизированным provider-owned BindingRegistry.
Project config сможет выбирать только зарегистрированные IDs и данные, без
callback, class, PHP/template или filesystem paths. Первый вертикальный срез —
один `docara.navigation` с presentations `header`, `tree`, `compact`; default
output должен остаться byte-identical. Project-owned shell contribution
доказывается через разрешённую capability без изменения engine `src/`.

Recovery source:
`source/workflow/2026-08-04-docara-goal-a-shell-contract.md`.

Goal B и Goal C независимо приняты. Product track завершён. Release review не
авторизован без отдельного явного решения пользователя.

## Goal B. Full Interface Library & Useful Extension Demos

Статус: независимо принят `PASS_WITH_NOTES`; B0-B6 implementation и
интеграционная матрица завершены. Design Atlas является детерминированной
проекцией принятых registries, а не вторым реестром. Search, breadcrumbs и
pager переведены в зарегистрированные Smart leaves с точной default HTML
parity. Starter содержит безопасные project-owned install builder, product
configurator и footer links на том же production/preview path. Exact owner
packet для `ui.input`, `ui.dropdown`, `ui.checkbox` и отдельный exact owner
artifact `ui.list-item` приняты. Полезный dropdown использует только admitted
`type=text` children; локальные `items`, raw markup и Docara-owned подмена
запрещены. Candidate `ccb076a…` остаётся pre-wave historical baseline. Exact
product candidate `c3b91eee71ab906cd79ae7a119c6961664f03528` прошёл fresh
full/package/two-consumer/browser B6 и независимый reverse-outcome audit.
Каждый отображаемый demo control влияет на allowlisted локальную команду или
итог. Goal C также независимо принят на последующем frozen candidate.

Recovery source:
`source/workflow/2026-08-04-docara-goal-b-interface-library.md`.

## M2. Вертикальный срез `components/badge`

Статус: принят ограниченный вертикальный срез. Typed in-memory IR, общий
registry, существующий Smart gateway и единый PageBuilder доказаны на Badge;
глобальная миграция и удаление legacy не заявлены.

Страница `content/ru/components/badge.md` проходит полный целевой путь после
локального gate `badge_source_ready`:

```text
PageSourceLocator -> PortableConfigurationLoader -> MarkdownCompiler -> Document IR
-> DocumentRendererRegistry -> SmartComponentGateway -> layout composition
-> PageBuilderResult
```

Результат:

- физический Markdown владеет всей прозой и примерами страницы;
- все native и component nodes имеют source location;
- alias `badge` разрешается registry, а не условием в parser;
- assets возвращаются через `RenderArtifact`;
- full и single-page build byte-identical для выбранной страницы;
- оба режима используют один PageBuilder pipeline и различаются только route
  selection;
- новый путь не принимает `trustedMainHtml`.

Приёмка: PHP/unit tests, exact HTML/assets parity, светлая/тёмная тема,
desktop/mobile, LTR и broken links 0. RTL остаётся locale-wide проверкой M3/M5,
поскольку M2 ограничен русской страницей.

## M3. Перенести публичные страницы

Статус: русский component-раздел завершён — 32/32 route имеют физический
Markdown-owner, производные представления используют результаты PageBuilder,
русские prose projections удалены после parity/zero-reference, интеграционная
приёмка PASS. Этот checkpoint не заявляет миграцию других локалей.

Результат:

- у каждого route есть Markdown в своей locale;
- страницы компонентов и examples больше не создаются projectors;
- каталог строится из manifest + metadata Markdown и вставляется вызовом
  одного продуктового компонента;
- локали мигрируются независимо, без silent fallback редакторского текста;
- single-page build не генерирует полный каталог и все примеры перед фильтром.

Миграция выполняется небольшими пакетами страниц. После каждого пакета
сохраняется parity evidence и обновляется implementation mapping.

## M4. Удалить параллельные пути

Статус: завершён для всех 103 текущих русских публичных route; projectors,
generated owners/allowlist и trusted-main bypass удалены после parity и
zero-reference evidence.

Удаление разрешено только после закрытия соответствующего gate:

1. публичная page projection в component catalog — после физического Markdown
   и parity всех component routes;
2. declarative example projector — после переноса примеров в Markdown и
   универсальные Smart-компоненты;
3. `buildGenerated()` и `trustedMainHtml` удалены в M4 после перехода всех 103
   русских страниц на один typed `PageBuilder` artifact;
4. ранние/параллельные Smart renderers — после единого IR component node и
   gateway;
5. public `resources/i18n`, `site.json` compatibility и поле `components` в
   language-pack — после физического контента всех локалей;
6. coarse raw Markdown node — после типизированного block/inline IR и тестов.

Временный compatibility layer не становится публичным API и удаляется в том же
миграционном треке.

## M5. Стабилизировать публичный продукт

Статус: implementation runtime и bounded product-candidate acceptance
завершены. R1 audit позже переоткрыл semantic docs/artifact acceptance; release
approval отсутствует.

Результат:

- один `build` для сайта и тот же `PageBuilder` для `--page`;
- чистый `init` создаёт понятный переносимый проект;
- update отделяет engine-owned файлы от project-owned content/config/assets;
- runtime-команды и ownership lifecycle доказаны; semantic public docs требуют
  R1-C correction;
- diagnostics ссылаются на source location;
- immutable M5 evidence сохраняется, но не подменяет R1-C release evidence.

После M5 выполняется R1: детерминированная упаковка, versioned update/rollback,
fresh-consumer и release verification. Даже зелёный R1 лишь готовит отдельное
release review; merge, tag, публикация и deploy не выполняются автоматически.

## R1. Подготовить локальный release candidate

Статус: `correction_pending` после independent audit. Прежние reproducibility,
consumer и update evidence остаются историческими, но local release readiness
отозвана из-за obsolete public language-pack contract и broken packaged links.

Доказано: два clean clone создают byte-identical ZIP/manifest/checksums; два
fresh dist consumer проходят init/build/static; текущий публичный сайт даёт
103/103 full/single parity; реальный predecessor/current update атомарно
применяется и точно откатывается с сохранением project-owned файлов; artifact
policy и security tests проходят. Эти положительные результаты сохраняются как
история, но следующий шаг — R1-C correction и новый independent artifact retest.

## R1-C. Устранить semantic drift перед новым candidate

Статус: independently accepted `PASS_WITH_NOTES`. Public `language_pack`
удалён, public authoring docs и negative gates переписаны, front
matter/missing-page contracts работают, ссылки внутри ZIP проверяются, новый
immutable artifact независимо воспроизведён. Evidence source:
`source/workflow/2026-08-02-docara-r1c-semantic-correction-goal.md`.

## R2. Подготовить production-readiness dossier

Статус: corrected disposable dossier PASS. Новый gate закрыт двумя независимыми
dist consumer с различными filesystem mtimes и byte-identical полными
деревьями. Package, compatibility, security, HTTP/browser, delta и
cutover/rollback матрицы повторены для exact rc.3. Live deployment остаётся
отдельным закрытым gate до явного решения пользователя.

## Порядок продолжения

Текущий router — `source/workflow/ACTIVE.md`; terminal handoff —
`source/handoff/2026-08-09-docara-current-main-onboarding/START.md`. M0-M5,
R1-C/R2, Goals 1-3, Goals A-C и Surface/Hero Goals S1-S3 не переигрываются.

Автоматического следующего implementation batch нет. Следующее действие —
`explicit_user_decision`. Пользователь должен отдельно определить, нужен ли
version/release action, exact revision/artifact, version/channel и scope
tag/publication/deploy. До этого release gates закрыты.

## Запрещённые сокращения

- не генерировать Markdown-страницы из language pack;
- не передавать готовый page HTML в основной renderer;
- не создавать второй parser/renderer/build path ради компонента;
- не считать generated catalog источником редакторского текста;
- не переносить в Docara Laravel или базу данных;
- не переписывать generated `ui`/`ui-smart` вручную;
- не удалять baseline до parity evidence.
