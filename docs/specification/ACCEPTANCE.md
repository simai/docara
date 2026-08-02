# Приёмка единой архитектуры Docara

Статус: `CORRECTION_PENDING`. Независимый R1 audit обнаружил semantic drift
между runtime, schemas, public docs, tests и exact ZIP. Старый R1 artifact
superseded; release и production gates закрыты.

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
- [x] Новый компонент не требует ветки в центральном parser или renderer.
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
- [ ] Публичный `resources/i18n`, `site.json` compatibility и component prose
      удалены из public schemas/data/models.
- [x] Raw coarse Markdown fallback заменён typed IR там, где он обходил registry.
- [x] Zero-reference scan подтверждает отсутствие runtime references на
      удалённые пути.

## I. Качество репозитория

- [x] PHPUnit, formatter/static checks и `git diff --check` проходят.
- [x] Полная документационная сборка и static verifier проходят.
- [ ] README, CLI help и публичная документация совпадают с runtime.
- [x] Architecture graph валиден и mappings отражают exact code/tests/evidence.
- [x] Worktree чист после фиксации candidate.
- [ ] Независимый tester проверил новый исправленный exact archive, а не
      mutable worktree или superseded artifact.

## Итоговый release gate

Завершение этого списка означает готовность архитектурного кандидата к
отдельному release review. Оно не создаёт автоматически tag, release,
default-branch merge или production deployment.
