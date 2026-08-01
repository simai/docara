# Приёмка единой архитектуры Docara

Статус: обязательная матрица; текущая реализация ещё не соответствует всем
критериям

PASS ставится только по воспроизводимому evidence exact revision. Activity,
скриншот без source binding или зелёный unit test одного модуля не заменяют
приёмку результата.

## A. Источники истины

- [ ] Каждый публичный route каждой локали имеет ровно один
      `content/<locale>/<route>.md`.
- [ ] Один Markdown не создаёт несколько скрытых публичных страниц.
- [ ] Единственный источник общих видимых переводов локали —
      `content/<locale>/lang.json`.
- [ ] В target отсутствуют публичный `resources/i18n`, prose-bearing language
      packs и compatibility path для `site.json`.
- [ ] Package-owned CLI/build messages не входят в public page inputs.
- [ ] Component manifests не содержат редакторскую прозу своих страниц.
- [ ] Config содержит только композицию и настройки, без HTML/CSS/статей.
- [ ] Удаление `build` и `var/cache` не удаляет исходные данные.
- [ ] Generated artifacts воспроизводятся из Markdown, config, components и
      engine revision.

## B. Единственный конвейер

- [ ] Full build передаёт полный набор route в один `PageBuilder` pipeline.
- [ ] Single-page build передаёт набор из одного route в тот же pipeline.
- [ ] Для одной revision full/single дают одинаковые HTML, assets и metadata.
- [ ] Нет второго пути `trustedMainHtml`/`buildGenerated` для публичных страниц.
- [ ] Markdown один раз компилируется в typed Document IR только в памяти.
- [ ] Page-level IR JSON/JSONL не обязателен; cache, search index, `--dump-ir`
      и test evidence удаляемы и не становятся source of truth.
- [ ] Все IR nodes рендерятся через один `NodeRendererRegistry`.
- [ ] Неизвестный node fail-closed с понятной source location.

## C. Smart-компоненты

- [ ] Inline и block syntax создают один тип IR node `component`.
- [ ] Все aliases разрешает один registry.
- [ ] `ui.*` и `docara.*` проходят один Smart Component Gateway.
- [ ] Props и slots валидируются до template rendering.
- [ ] Gateway возвращает HTML, assets, diagnostics и provenance.
- [ ] Новый компонент не требует ветки в центральном parser или renderer.
- [ ] Компонент не получает недокументированный произвольный HTML.

## D. Композиция и локали

- [ ] Приоритет `defaults -> docara.json -> section.json -> page.json`
      детерминирован и покрыт тестами.
- [ ] Front matter не переопределяет layout/regions.
- [ ] Любое число well-formed locale поддерживается одним контрактом.
- [ ] Каждая locale имеет отдельное дерево Markdown.
- [ ] Отсутствующий перевод не подменяется редакторским текстом другой locale
      молча.
- [ ] Root redirect на default locale не показывает промежуточную страницу.
- [ ] LTR/RTL проходят одну и ту же функциональную матрицу.

## E. Сборка и обновление

- [ ] Две чистые полные сборки exact input byte-identical.
- [ ] Single-page build не строит все component pages/examples перед фильтром.
- [ ] Частичная запись атомарна и не повреждает предыдущий результат.
- [ ] `init` из пустого каталога работает без Node.js у потребителя.
- [ ] Engine/Framework revisions зафиксированы immutable lock.
- [ ] `init --update` сохраняет project-owned content/config/assets.
- [ ] Есть dry-run/diff и rollback для обновления.
- [ ] Moving branches/latest не используются как runtime input.

## F. Пользовательский результат

- [ ] Многоуровневое меню, поиск, breadcrumbs, outline и previous/next работают.
- [ ] Docs и landing presets используют один engine и authoring contract.
- [ ] Светлая/тёмная тема не создаёт flash неправильной темы.
- [ ] Desktop 1440/1920 и mobile 390 не имеют page-level overflow.
- [ ] Keyboard navigation, Esc, focus trap/return и reduced motion проверены.
- [ ] Broken links, missing assets и duplicate route равны 0.
- [ ] Страница компонента написана для человека: назначение, параметры,
      варианты и короткие работающие примеры без служебного мусора.

## G. Диагностика и безопасность

- [ ] Ошибка содержит locale, route, file, line, column и actionable message.
- [ ] Path traversal и выход из project/build root fail-closed.
- [ ] Raw HTML policy явна; небезопасный HTML не проходит случайно.
- [ ] External embed имеет allowlist/sandbox policy.
- [ ] Secrets и локальные absolute paths отсутствуют в build/evidence.
- [ ] Exact source/config/Framework inputs записаны в build receipt.

## H. Удаление legacy

- [ ] Component page projector удалён после route parity.
- [ ] Declarative example projector удалён после example parity.
- [ ] `trustedMainHtml` и generated page bypass удалены.
- [ ] Параллельные Smart renderers сведены к gateway и удалены.
- [ ] Публичный `resources/i18n`, `site.json` compatibility и component prose
      удалены из public schemas/data/models.
- [ ] Raw coarse Markdown fallback заменён typed IR там, где он обходил registry.
- [ ] Zero-reference scan подтверждает отсутствие runtime references на
      удалённые пути.

## I. Качество репозитория

- [ ] PHPUnit, formatter/static checks и `git diff --check` проходят.
- [ ] Полная документационная сборка и static verifier проходят.
- [ ] README, CLI help и публичная документация совпадают с runtime.
- [ ] Architecture graph валиден и mappings отражают exact code/tests/evidence.
- [ ] Worktree чист после фиксации candidate.
- [ ] Независимый tester проверил exact archive, а не mutable worktree.

## Итоговый release gate

Завершение этого списка означает готовность архитектурного кандидата к
отдельному release review. Оно не создаёт автоматически tag, release,
default-branch merge или production deployment.
