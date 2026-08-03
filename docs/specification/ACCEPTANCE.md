# Приёмка единой архитектуры Docara

Текущий статус: Goal 1 и Goal 2 независимо приняты. Goal 3 implementation
завершена на exact source `8cd695f…` и ожидает независимого audit.
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
- [x] Validate/test используют production validators и PreviewKernel, а scaffold
      output проходит registry reload и готов к preview.
- [x] QA plan привязан к isolated production-path preview и exact browser
      matrix; report требует screenshots и нулевые a11y/console/overflow/
      visual-diff defects.
- [x] Optional MCP делегирует те же services, не принимает внешний root/path и
      требует отдельную process capability плюс exact plan для apply.
- [x] Goal 3 exact candidate прошёл executor-owned полную regression/package/
      consumer/browser матрицу.
- [ ] Независимый reverse-outcome audit принял exact Goal 3 candidate.

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
