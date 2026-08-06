# Приёмка единой архитектуры Docara

Текущий статус: Goal 1, Goal 2, Goal 3 и Goals A-C независимо приняты. Новый
отдельно авторизованный post-roadmap track Surface & Hero Media находится на
Goal S1: implementation complete на exact product candidate
`45276f63422e8b8465b33e415d3fc302dfeac570`, независимый audit ожидается. S2
не начат и не авторизован этим контуром. Exact accepted Goal A
product/runtime candidate —
`8c04160ab50549b060fb933cf80f86193cd92113`. Goal B Full Interface Library &
Useful Extension Demos принят на exact candidate
`c3b91eee71ab906cd79ae7a119c6961664f03528`. Goal C C1 устранил найденные
semantic/evidence defects на exact candidate
`eb35f5c6f18e5eb9be69e91887b09486f5703136`; независимый reverse-outcome
audit принял его с `PASS`, а product track завершён. Частичный
candidate `ccb076a89535954022ca89eb70b84d6c81d80de3` остаётся historical
baseline.
Source `1dee6d19…` остаётся только historical/superseded radius baseline.
Finalized QA reference целиком content-addressed и привязан к immutable plan;
совместная подмена reference/candidate/report fail-closed.
Исторический R2 `PASS_DISPOSABLE_CORRECTED` и unpublished release artifact
остаются parked baseline, а не текущим архитектурным кандидатом. Tag, release,
live cutover и production acceptance требуют отдельного решения и не заявлены.

M5 implementation candidate подтверждает текущий русский публичный сайт и
минимальные EN LTR/AR RTL product fixtures через единый runtime. Чекбоксы
отмечены только по воспроизводимому implementation evidence. Полный перевод
других locales, release и production readiness не заявлены.

PASS ставится только по воспроизводимому evidence exact revision. Activity,
скриншот без source binding или зелёный unit test одного модуля не заменяют
приёмку результата.

## Goal S1. Full-bleed Geometry & Shared Surface Runtime

- [x] Landing direct-child full-bleed достигает viewport, а внутренний
      контейнер сохраняет общий контентный ритм.
- [x] Публичный `docara.surface` зарегистрирован как один typed container с
      `content/full` outer width, `container/full` inner width и закрытыми
      presentation tokens.
- [x] Декоративный media layer принимает только project-local admitted asset;
      traversal, protocol, symlink, hardlink и case mismatch fail-closed.
- [x] Child/slot/count/order/depth contract принадлежит registry capabilities,
      а не component-ID веткам или длине fence.
- [x] Full/full/single, preview/production, static, browser, package и fresh
      consumer executor evidence зелёные на exact candidate `45276f6…`.
- [x] Existing Hero section byte-identical baseline; background mode и
      homepage art direction не входят в S1.
- [ ] Независимый reverse-outcome audit Goal S1.

## Goal A. Shell Contract & Safe Configuration

- [x] A0 фиксирует baseline, namespace ownership, shell capabilities, rollback
      и запрет project executable paths.
- [x] Built-in bindings разрешаются одним typed provider-owned
      `BindingRegistry` с owner/provenance/capabilities/schema.
- [x] `docara.navigation` variants `header`, `tree`, `compact` проходят один
      Gateway/composer path, а default output остаётся byte-identical.
- [x] Project-owned shell contribution подключается artifact/config-only без
      изменения engine `src`.
- [x] Duplicate/namespace/capability/prop/path/symlink/case conflicts
      fail-closed до render.
- [x] Full/single, two-build determinism и preview/production parity доказаны.
- [x] Goal A имеет independent-ready evidence и чистый tracked worktree.
- [x] Независимый reverse-outcome audit Goal A завершён с `PASS`.

## Goal B. Full Interface Library & Useful Extension Demos

- [x] Design Atlas детерминированно строится только из accepted registries.
- [x] Ownership и `authoring_kind` независимы; container child contracts
      ограничивают slots/count/order/depth до render.
- [x] Search, breadcrumbs, pager, navigation, TOC и preferences проходят один
      production Gateway/composition path.
- [x] Project install builder, product configurator и footer устанавливаются
      data-only без engine source edits и backend side effects; каждый
      отображаемый Framework-контрол меняет свой allowlisted локальный итог.
- [x] Framework input/dropdown/checkbox и обязательный text-only list-item
      exact-pinned, independently accepted и cross-host proven до support
      claim; dropdown options проходят только admitted child contract.
- [x] Full/single/determinism/security/default/package/consumer matrix зелёная
      на exact product candidate `c3b91eee…`.
- [x] Полная Goal B browser/a11y и cross-host matrix повторена на том же exact
      independent-ready candidate.

## Goal C. Public Documentation, Settings Reference & Agent Journey

- [x] `/components/` публикует шесть Markdown-owned входов из одной admitted
      Atlas projection; namespace, owner, origin, provider, authoring kind и
      support независимы, а real-project tests проверяют rendered groups.
- [x] `/design/` объясняет реальную Layout -> region -> Section -> slot ->
      Block -> Smart -> View цепочку и границу application-owned page/head.
- [x] Все 13 `/settings/` task guides используют одну exhaustive schema-derived
      проекцию с `$defs`/refs, compositions/conditionals, exact JSON pointers,
      scope/default/validation/provenance.
- [x] CLI/JSON/MCP journey документирует и в disposable project исполняет один
      discover -> plan -> preview -> dry-run -> hash-bound apply -> validate
      service path.
- [x] Все 132 route имеют один физический Markdown owner; redirects не нужны и
      duplicate prose owners отсутствуют.
- [x] Atlas/schema receipts hash-bound к production build receipt и
      fail-closed проверяются static verifier.
- [x] Full/full/single, static, package/consumer, browser/SEO и cross-host
      executor evidence зелёные на exact candidate `eb35f5c6…`; complete-tree
      digest `44d827a6…`, package ZIP `50a0f7cf…`.
- [x] Независимый reverse-outcome audit Goal C завершён с `PASS` на exact
      product candidate `eb35f5c6…`.

## A. Источники истины

- [x] Каждый публичный route каждой локали имеет ровно один
      `content/<locale>/<route>.md`.
- [x] Один Markdown не создаёт несколько скрытых публичных страниц.
- [x] Единственный источник общих видимых переводов локали —
      `content/<locale>/lang.json`.
- [x] В target отсутствуют публичный `resources/i18n`, prose-bearing language
      packs и compatibility path для `site.json`.
- [x] Package-owned CLI/build messages не входят в public page inputs.
- [x] Component manifests не содержат редакторскую прозу своих страниц.
- [x] Config содержит только композицию и настройки, без HTML/CSS/статей.
- [x] Удаление `build` и `var/cache` не удаляет исходные данные.
- [x] Generated artifacts воспроизводятся из Markdown, config, components и
      engine revision.

## B. Единственный конвейер

- [x] Full build передаёт полный набор route в один `PageBuilder` pipeline.
- [x] Single-page build передаёт набор из одного route в тот же pipeline.
- [x] Для одной revision full/single дают одинаковые HTML, assets и metadata.
- [x] Нет второго пути `trustedMainHtml`/`buildGenerated` для публичных страниц.
- [x] Markdown один раз компилируется в typed Document IR только в памяти.
- [x] Page-level IR JSON/JSONL не обязателен; cache, search index, `--dump-ir`
      и test evidence удаляемы и не становятся source of truth.
- [x] Все IR nodes рендерятся через один `DocumentRendererRegistry`.
- [x] Неизвестный node fail-closed с понятной source location.

## C. Smart-компоненты

- [x] Inline и block syntax создают один общий IR component contract.
- [x] Все aliases разрешает один registry.
- [x] `ui.*` и `docara.*` проходят один Smart Component Gateway.
- [x] Props и slots валидируются до template rendering.
- [x] Gateway возвращает HTML, assets, diagnostics и provenance.
- [x] Новый Smart-компонент не требует component-ID ветки в центральном
      compiler, parser, renderer, ViewModel factory, semantic validator или
      contribution list. Goal 1-D удалил `docara.brand` view branch из
      `DeclarativePageCompiler`: explicit view имеет приоритет, иначе view
      выбирает зарегистрированный preset, затем применяется `default`.
      Goal 2 удалил прежний shell-region allowlist: design compatibility теперь
      определяется artifacts и одним DesignRegistry.
- [x] Project Smart root фиксирован как `smart/`; namespace принадлежит одному
      provider, а paths/symlinks/templates/assets fail-closed.
- [x] Portable Smart manifest соответствует source-pinned SF5 artifact v1;
      host-bound adapters отделены от переносимого manifest/view/preset слоя.
      Exact pin `b3cdff87…` сохраняет view, preset, slot и hydration; один
      неизменённый fixture даёт byte-identical HTML под Docara и SF5. Goal 1
      независимо принят с `PASS_WITH_NOTES`.
- [x] Компонент не получает недокументированный произвольный HTML.

## D. Композиция и локали

- [x] Приоритет `defaults -> docara.json -> section.json -> page.json`
      детерминирован и покрыт тестами.
- [x] Front matter не переопределяет layout/regions.
- [x] Любое число well-formed locale поддерживается одним контрактом.
- [x] Каждая locale имеет отдельное дерево Markdown.
- [x] Отсутствующий перевод не подменяется редакторским текстом другой locale
      молча.
- [x] Root redirect на default locale не показывает промежуточную страницу.
- [x] LTR/RTL проходят одну и ту же функциональную матрицу.
- [x] Built-in/package/project Layout, Section, Block и View Tree разрешаются
      одним deterministic DesignRegistry без implicit shadowing.
- [x] Region resolver/compiler не содержат конкретных design/Smart IDs.
- [x] Project-local design подключается только artifacts в `design/`, без
      engine source edit и без executable/template path из project JSON.
- [x] Smart/region/layout/page preview использует production PageBuilder path;
      receipt имеет typed preview purpose, production verifier отвергает его,
      а self-contained HTTP output не содержит production receipt.
- [x] PHP-only watch отслеживает dependency closure выбранного route и не
      компилирует другие страницы; unrelated project artifact не инвалидирует
      target, relevant project/package edit/create/delete инвалидирует один раз.
- [x] View Tree `oneOf`, safe tag/attribute/utility и descriptor-owned
      region/slot contracts проверяются до регистрации; dynamic region не
      требует engine enum.

## E. Сборка и обновление

- [x] Две чистые полные сборки exact input byte-identical.
- [x] Single-page build не строит все component pages/examples перед фильтром.
- [x] Частичная запись атомарна и не повреждает предыдущий результат.
- [x] `init` из пустого каталога работает без Node.js у потребителя.
- [x] Engine/Framework revisions зафиксированы immutable lock.
- [x] Явный `update` сохраняет project-owned content/config/assets.
- [x] Есть verify, dry-run/diff, explicit apply и rollback для обновления.
- [x] Moving branches/latest не используются как runtime input.

## F. Пользовательский результат

- [x] Многоуровневое меню, поиск, breadcrumbs, outline и previous/next работают.
- [x] Docs и landing presets используют один engine и authoring contract.
- [x] Светлая/тёмная тема не создаёт flash неправильной темы.
- [x] Desktop 1440/1920 и mobile 390 не имеют page-level overflow.
- [x] Keyboard navigation, Esc, focus trap/return и reduced motion проверены.
- [x] Broken links, missing assets и duplicate route равны 0.
- [x] Страница компонента написана для человека: назначение, параметры,
      варианты и короткие работающие примеры без служебного мусора.

## G. Диагностика и безопасность

- [x] Ошибка содержит locale, route, file, line, column и actionable message.
- [x] Path traversal и выход из project/build root fail-closed.
- [x] Raw HTML policy явна; небезопасный HTML не проходит случайно.
- [x] External embed имеет allowlist/sandbox policy.
- [x] Secrets и локальные absolute paths отсутствуют в build/evidence.
- [x] Exact source/config/Framework inputs записаны в build receipt.

## H. Удаление legacy

- [x] Component page projector удалён после route parity.
- [x] Declarative example projector удалён после example parity.
- [x] `trustedMainHtml` и generated page bypass удалены.
- [x] Публичные component adapters сведены к одному gateway и registry.
- [x] Публичный `resources/i18n`, `site.json` compatibility и component prose
      удалены из public schemas/data/models.
- [x] Raw coarse Markdown fallback заменён typed IR там, где он обходил registry.
- [x] Zero-reference scan подтверждает отсутствие runtime references на
      удалённые пути.

## I. Качество репозитория

- [x] PHPUnit, formatter/static checks и `git diff --check` проходят.
- [x] Полная документационная сборка и static verifier проходят.
- [x] README, CLI help и публичная документация совпадают с runtime.
- [x] Architecture graph валиден и mappings отражают exact code/tests/evidence.
- [x] Worktree чист после фиксации candidate.
- [ ] Независимый tester повторно проверил новый deterministic exact archive,
      включая два независимых dist consumer и полный public tree.

## I.1 Developer/AI SDK (Goal 3 implementation gate)

- [x] Human и JSON CLI формируются из одного `OperationResult`.
- [x] Doctor/list/inspect/schema читают существующие Smart/Design registries,
      provider ownership, provenance и реальные schemas без component-ID list.
- [x] Smart/design scaffold требует hash-bound dry-run plan и повторно
      проверяет inputs/targets/content перед explicit apply.
- [x] Traversal, symlink, hardlink, duplicate namespace/target, stale/tampered
      plan и forbidden roots fail-closed; source writes ограничены `smart/` и
      `design/`.
- [x] `.docara-preview`, `.docara-qa` и scaffold/MCP writes используют одну
      pre-mutation containment policy; error path не меняет external roots.
- [x] Validate/test используют production validators и PreviewKernel, а scaffold
      output проходит registry reload и готов к preview.
- [x] QA plan привязан к isolated production-path preview и exact browser
      matrix; report требует screenshots и нулевые a11y/console/overflow/
      visual-diff defects.
- [x] Smart, region и layout прошли 24 exact browser scenarios, а CLI human,
      JSON и MCP failures имеют один полный diagnostic/provenance contract.
- [x] File-backed failures содержат relative path, pointer, line и column;
      visual QA сравнивает target с hash-bound production reference, а не с
      повторным текущим frame.
- [x] File-backed JSON/Markdown diagnostics не принимают missing/null line или
      column; QA verifier пересчитывает canonical plan/reference identities,
      сверяет immutable preview bytes и самостоятельно сравнивает
      candidate/reference PNG.
- [x] Finalized visual reference привязан к immutable plan через полный
      content-addressed manifest: ordered scenario IDs, screenshot paths и
      каждый screenshot SHA-256; согласованная подмена reference/candidate/
      report под старым plan ID fail-closed.
- [x] Optional MCP делегирует те же services, не принимает внешний root/path и
      требует отдельную process capability плюс exact plan для apply.
- [x] Goal 3 exact candidate прошёл executor-owned полную regression/package/
      consumer/browser матрицу.
- [x] `schema smart` публикует byte-exact Framework-owned portable manifest
      schema; Smart scaffold проходит её без преобразования, затем отдельную
      Docara admission policy; inspect package/Framework/project
      definitions сообщает одну `sf.smart_artifact_abi` identity, отделяя
      storage alias, provider adapter и template ABI.
- [x] Current cumulative candidate использует Framework-owned
      `--sf-radius--ui`, ограничивает reader choice значениями default/medium/
      large и оставляет component-native radius overrides авторитетными.
- [x] Search/settings modals по умолчанию не размывают фон; пользовательская
      настройка blur остаётся доступна как явный выбор.
- [x] Независимый reverse-outcome audit принял exact Goal 3 candidate
      `1e571b6e16ebc4520121aff0ae868de3b986dff3`.

## J. R2 production dossier

- [x] Новый deterministic ZIP повторно проверен и установлен как dist без package `.git`.
- [x] Два независимых fresh consumer создают byte-identical 305 outputs, включая page metadata.
- [x] Fresh consumers проходят macOS/PHP 8.4, macOS/PHP 8.3 и Linux/PHP 8.3.
- [x] Production-like HTTP smoke проходит 103/103 canonical routes.
- [x] Exact current/new-candidate tree digests и полный path delta записаны.
- [x] Same-filesystem cutover и exact rollback доказаны для нового candidate.
- [x] Caddy root, TLS health, retention, smoke и stop thresholds перепроверены.
- [ ] Пользователь явно разрешил live cutover `docara.test`.
- [ ] Live preflight повторно подтвердил current/candidate digests и окно.
- [ ] Live cutover, post-cutover smoke и production acceptance выполнены.

## Итоговый release gate

Локальный unpublished release candidate: planned `2.0.0-rc.3`, source
`be0ba2db5254e468c7c014016ade02e8b4f3f16c`, ZIP
`630d971e94a1222624304a3a5c2a7791586c0b7866ede5b8f3506c93bdebadc0`,
tree `425da363fc51d33d2c5b42577980f4ca4603b83814440dbfb06fe419b4cade46`.
Tag не создан; release, merge и production deployment требуют отдельных
решений.
