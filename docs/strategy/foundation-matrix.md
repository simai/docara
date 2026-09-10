# Фундамент: проверенный срез и очередь решений

Дата: 10 сентября 2026 года. Исполнение [генерального плана](foundation-plan.md). Пользователь принял пакет фундаментальных решений и автономное выполнение. Это проверяемый рабочий срез, а не приёмка всего Framework до release-gate.

## Актуальный остаток приёмки

Статус продолжения: **активное автономное выполнение**, не завершение фундамента. Ранее зафиксированное ожидание решения владельца исторически снято его явным согласием. Контракты имён/aliases, UI Heights, token-only размеры, Utilities↔Loader и принятые современные семейства реализованы в изолированном кандидате. Размерный strict gate:4381 файл,604 классифицированных записи, unresolved0. Полный suite после последнего Smart lifecycle изменения:261tests/258PASS/3FAIL; все три — один manifest provenance drift административного меню, который должен закрываться от точной ревизии, а не ручным хешем.

[Решение по JS-основе](foundation-smart-runtime-decision.md): сохранить HTMLElement+Lit и извлекать только доказанно повторяемую механику. Воспроизводимый source-аудит baseline4b55b4d2→pilot:7728→7815 поддерживаемых строк, gzip52094→53382 байта, native lifecycle overrides7→2, ручная child factory таблицы1→0; новая обязательная зависимость и миграция markup отсутствуют. Focused23/23PASS. Production bytes, запросы и median init/update ещё не измерены на immutable сборке; бюджет записан заранее и остаётся release-gate.

Следующая критическая цепочка: scoped source commit под action gate → точная чистая source/builder provenance → две полные одинаковые сборки → production JS benchmark и полный Loader audit → штатное обновление Smart manifests/registry → зелёный suite → точные consumer locks → backup/rollback и публикация docara.test/ui-doc.test. Старые working-input reproducibility отчёты не заменяют эту цепочку.

Последний Smart candidate после table/modal исправлений повторно собран: [621/621 файлов идентичны](foundation-smart-modal-attribute-repro.json), digest657cfb80fb419b7ef93a5e2869771b8f66fe3bc2c26fa43b6dea7aec5af7b0dd, diagnostics[]. Это working-input reproducibility, не immutable release proof. [Подготовка поставки](foundation-release-preparation.md) фиксирует последовательность code refs → два generated builds → runtime refs → derived metadata → full gate → consumer locks/publication. Refresh script не обнаруживает новые source_artifacts автоматически; полноту inventory нового table helper необходимо проверить. Коммитов, metadata rewrite, locks или публикации не выполнено. Архитектурный пакет по-прежнему ожидает явного ответа.

Консолидация source: [snapshot](foundation-source-snapshot.json) содержит44 изменённых/новых source/script/test файла относительно isolated HEAD19db6d2; node_modules и чужая tests/admin-menu-navigation.contract.test.mjs исключены, не изменены. Snapshot не является разрешением на commit или immutable provenance. Повторный полный unit suite с чистым локальным clone ui-doc@1dc8e0537fc3d4cde3b68b3bbf92a51f5ab9b567 (`UI_DOC_ROOT=/private/tmp/sf5-foundation-pinned-docs.GOReU9`): **195tests,192PASS,3FAIL**, все3 — manifest_source_artifact_drift table/index.js. Лог `/private/tmp/sf5-foundation-consolidated-pinned-unit.log`. Предыдущие5FAIL включали два ошибочных отсутствующих UI_DOC_ROOT и не выдаются за продуктовые дефекты. Проверка public-doc content spacing в том же pinned clone exit0; это узкий content-spacing контракт, не весь каталог документации.

Переход к публичному расширению требует ответа на [единый пакет решений](foundation-decision-packet.md). До принятия нельзя объявлять query-container/cq-*, display-table или новые конечные семейства стабильным API. Source/release metadata должны обновляться от точных ревизий штатным инструментом, а не подменой хешей. Runtime публикации и locks не менялись.

Удаление таблицы с открытым modal: [исходная проверка](foundation-table-modal-detach.json) подтвердила снятие scroll lock/stack/registry при remove, но remount сам открывал окно из оставшегося state.modalOpen. Сброс transient modalOpen исправлен в table.onDisconnected. [Промежуточная проверка](foundation-table-modal-reset-chromium.json) всё ещё FAIL: stale render оставлял overlay после снятия open. В SfModal.attributeChangedCallback удаление open теперь обязательно вызывает requestComponentUpdate, даже когда close() уже no-op. Focused14/14PASS, обе регрессии воспроизводились до соответствующих правок.

Smart `/private/tmp/sf5-foundation-smart-modal-attribute-product` exit0/diagnostics[]. [Chromium](foundation-table-modal-attribute-chromium.json), [Firefox](foundation-table-modal-attribute-firefox.json), [WebKit](foundation-table-modal-attribute-webkit.json) прошли единый сценарий keyboard modal, remove/reconnect while open, повторное явное открытие/Escape, text filter cancel/apply, sibling portal isolation и два external-view cleanup. Errors/missing assets[]. Изменён также modal source, не только table; metadata/release проверку нужно выполнять для всей затронутой связки. Старые reproducibility outputs не объявляются доказательством этих новых входов. Следующий этап — консолидация source/регрессионной матрицы и подготовка точных release inputs; публичный API-пакет, provenance, настоящий Safari, backend ownership и публикационные gates остаются открытыми.

Table lifecycle локально исправлен. [До](foundation-table-lifecycle-before.json): после remove mounted=true и1 sf-context-menu оставался в portal. Table теперь использует onDisconnected базового lifecycle, а не переопределяет disconnectedCallback; очищает собственную portal-instance, pending settings и активный context state, сохраняя applied data/filter. Общий якорь #sf-table-portal сохраняется, активные контролы удаляются; закрытая таблица не создаёт portal и не очищает область другой таблицы. Focused tests12/12PASS; Smart `/private/tmp/sf5-foundation-smart-lifecycle-product` exit0/diagnostics[].

[Chromium](foundation-table-lifecycle-chromium.json), [Firefox](foundation-table-lifecycle-firefox.json), [WebKit](foundation-table-lifecycle-webkit.json) через Loader: sibling table render/remove не закрывают исходное меню; remove даёт mounted=false/contextBound=false/portalControls=0; remount сохраняет применённый фильтр и выдаёт ровно одно следующее событие. Затем настоящий внешний table template дважды удаляется с remount между ними: moduleReleased=true, resources=0, destroy ровно1 за удаление. Errors/missing assets[]. WebKit использует подтверждённый native Option+Tab. Это ещё не удаление при открытом modal, полный каталог Smart/типов фильтра, настоящий Safari, свежая повторная immutable-сборка или выпуск; старые reproducibility hashes относятся к старому source. Metadata drift gate остаётся открытым.

Текстовый фильтр таблицы: [до исправления](foundation-table-filter-cancel-before.json) закрытие снаружи не меняло applied state, но повторно открывало отменённый текст. В isolated source `contextEvent` очищает draft только при outside-dismiss filter-settings/tag-settings; общий closeContextMenu не изменён, чтобы не ломать внутренние переходы. Focused tests10/10PASS (новые2negative cases до правки FAIL). Новый Smart product `/private/tmp/sf5-foundation-smart-filter-product` exit0/diagnostics[]. [Chromium](foundation-table-filter-after.json), [WebKit](foundation-table-filter-webkit.json), [Firefox](foundation-table-filter-firefox-control.json) подтвердили ввод без применения, сброс, применение, отмену снаружи, повторное открытие с applied value и второе применение: ровно два события с разными актуальными values. Это text control, не все типы фильтров/серверные запросы/полный lifecycle.

Первый Firefox filter run не прошёл из-за клика по `sf-tag` host: [геометрия](foundation-table-filter-firefox-geometry.json) показывает display:contents и0×0. После выбора настоящего дочернего role=button сценарий прошёл без force click или dispatchEvent. Ошибка селектора не приписывается runtime. Повторная сборка из прошлого table-identity среза не доказывает reproducibility нового filter source; metadata/release gates остаются открыты. При чтении source также обнаружен SfTable.disconnectedCallback без вызова base cleanup; требуется отдельное воспроизведение unmount/remount, а не перенос PASS дочернего directive на всю таблицу.

Текущая Smart-сборка после сохранения идентичности table child controls повторена: [621/621 файлов побайтно идентичны](foundation-smart-table-repro.json), digest `b4d87894b62650e85f1e552b0ce2476b035e0dc71c52654ca5d673cfd94f40a5`; обе сборки exit0/diagnostics[]. Это reproducibility текущих working inputs, не immutable Git source/builder release. Следующий composite-срез — реальные filter settings: отмена/сброс не должны менять applied state, применение должно выдавать актуальный payload без повторных listeners. Guide отделяет поля и templates; серверная фильтрация принадлежит приложению, её нельзя имитировать как встроенную функцию таблицы.

Уточнение WebKit: [контроль без Framework](foundation-webkit-native-tab.json) показал, что обычный Tab пропускает нативные button/link, Option+Tab достигает обоих. [Таблица через Option+Tab](foundation-table-keyboard-webkit-option.json) **PASS**: Enter/Escape + два повторных цикла, тот же исходный BUTTON, errors/missing assets[]. Ни focus(), ни изменение системной настройки не использовались. Предыдущий FAIL Tab сохраняется как диагностика выбранного режима браузера, а не считается исправленным изменением tabindex. Настоящий Safari и прочие составные сценарии по-прежнему не приняты.

Следующий локальный кандидат таблицы сохраняет вложенный Smart Node через Lit child-part directive (`src/smart/table/js/smart-element.js`). Props/listeners обновляются, удалённые значения очищаются, refs и listeners освобождаются при disconnect и восстанавливаются при reconnect. Focused identity/lifecycle tests: **7/7 PASS**. Smart product `/private/tmp/sf5-foundation-smart-table-stable-product`: exit0, diagnostics[]. [Chromium keyboard](foundation-table-keyboard-chromium.json) и [Firefox keyboard](foundation-table-keyboard-firefox.json): Tab→Enter→Escape и ещё два Enter/Escape цикла возвращают фокус на ту же исходную кнопку; missing assets/errors пусты. Это локальный кандидат, не выпуск.

WebKit пока **не принят**: [mouse scenario](foundation-table-stable-webkit.json) сохраняет Node, но mouse click не фокусирует кнопку и modal запоминает BODY; [keyboard scenario](foundation-table-keyboard-webkit.json) не достигает кнопки за80 Tab. Нельзя подменять реальную клавиатурную доступность вызовом focus(). Нужно отдельно установить причину недостижимости, сравнив с нативной контрольной кнопкой/настройками тестового движка. Registry test сейчас FAIL `manifest_source_artifact_drift:src/smart/table/index.js`; метаданные намеренно не переписаны под незакоммиченные bytes. Браузерный успех двух движков не снимает этот release gate и остальные условия композиции.

Уточнение table focus после пересборки: [повторная браузерная проверка](foundation-table-focus-after.json) **FAIL** — удаление случайного ключа недостаточно. `SfTable.renderSmartElement` в `src/smart/table/index.js` при каждом вызове выполняет `document.createElement(tagName)` и возвращает новый Node; стабильный ключ строки/ячейки не сохраняет идентичность этого вложенного Node. Исходная кнопка уже отключена от DOM при открытии modal, фокус остаётся BODY. Следующее исправление должно сохранять идентичность вложенного Smart-элемента и корректно обновлять props/listeners, включая удаление устаревших обработчиков; нельзя маскировать дефект принудительным фокусом на новой кнопке. Полная приёмка таблицы остаётся открытой.

Метаданные table candidate: `refresh-smart-studio-provenance.mjs` читает artifact bytes из точных source/runtime/UI Git revisions и обновляет весь каталог. `generate-smart-studio-artifacts --refresh-metadata` пропускает v1 table manifest. Ни один из этих режимов не является безопасной точечной фиксацией незакоммиченного table template. Manifest/registry оставлены неизменными; `manifest_source_artifact_drift` остаётся честным блокером полной source/release проверки до фиксации ревизий и штатного обновления provenance. Функциональные локальные тесты могут продолжаться, но не отменяют этот отказ.

Продолжение table focus: [диагностика](foundation-table-focus-diagnosis.json) показала удалённую исходную кнопку уже при открытии диалога; sf-modal сохраняет BODY вместо неё, после закрытия фокус остаётся на BODY. В шаблоне таблицы найден `props[':key'] = item_${Math.random()}` при каждом renderComponent. В isolated source эта строка удалена, явные props/keys сохраняются. Unit-регрессия воспроизводила random key; браузерная приёмка после пересборки ещё требуется. Не считать предыдущий успешный Escape достаточным доказательством доступности.

Первый составной срез: [реальная sf-table через Loader](foundation-table-product.json) в Chromium принимает setColumns/setRows, открывает свой sf-modal кнопкой «Посмотреть» и закрывает по Escape. Guide явно оставляет серверную фильтрацию приложению; тест не подменяет это обещанием встроенной загрузки данных. Возврат фокуса, применение/отмена фильтра, повторная инициализация без повторных запросов и остальные браузеры ещё не приняты этим первым запуском.

Базовый form-срез на текущих продуктах через Loader: [Chromium](foundation-form-product.json), [Firefox](foundation-form-product-firefox.json), [WebKit](foundation-form-product-webkit.json) — PASS. Проверены native required/checkValidity, FormData, disabled exclusion, readonly inclusion, текст ошибки/ARIA, тот же input node/value/focus/selection после обновления ошибки, один input event после remount. Это штатный sf-input со встроенным видом: не покрывает альтернативный шаблон формы, loading/async validation, серверную отправку, IME, составную таблицу/диалог и настоящий Safari. Эти условия остаются открытыми.

Последнее изменение source: защита от запоздавшего external-template import, описанная в [Smart-контракте](smart-view-contract.md). Source suite173/173; продуктовый сценарий через Loader проходит Chromium/Firefox/WebKit. Текущие [Core59files](foundation-core-race-repro.json) и [Smart621files](foundation-smart-race-repro.json) повторно собраны с совпадающими bytes. Старые хеши относятся к прежним входам; новые ещё не являются доказательством immutable-commit release. Гонка импорта локально закрыта, остальные условия ниже сохраняются.

Эта таблица отделяет открытые условия всего плана от исторических результатов ниже. Локальные PASS не означают завершение фундамента.

| Условие генерального плана | Что ещё нужно доказать или сделать |
| --- | --- |
| Публичные границы и расширение | Принять контейнерную грамматику из [предложения](container-conditions-proposal.md); завершить решения по токенам, профилям условий, области действия шаблонов и совместимости. Автоматическое продолжение цели не считается согласием с новым API. |
| Современное покрытие | Реализовать согласованный контейнерный профиль с точным Loader и измерением размера; завершить решения/сценарии subgrid, text-wrap, динамических размеров и accessibility. Не объявлять локальный CSS-прототип публичной утилитой. |
| Поведение и композиция | Помимо выбранного list-item, проверить необходимые focus/state/error и composite сценарии; сквозной backend page test сейчас не проходит из-за несовместимости dirty Admin/UI контрактов. Не изменять чужие файлы без согласованного разграничения работы. |
| Авторский путь Docara | Новый scaffold→draft→preview исправлен и проверен; полная регрессия PreviewKernel/PortableSiteBuilder прошла:53 tests/1081 assertions. Остаётся включить исправление в согласованную поставку. |
| Совместимость и браузеры | Новый набор Core/Component/Smart проверен в Chromium/Firefox/WebKit на выбранном сценарии; нужны оставшиеся сценарии, самостоятельный старый потребитель и приёмка Safari/Edge. |
| Воспроизводимость поставки | Все четыре product дерева имеют отдельные совпадающие working-input сборки. Нужны точные зафиксированные source/builder ревизии, единый release gate и доказательство именно этой версии у потребителей. |
| Документация и публикация | Согласовать docs/API с исполняемым контрактом, обновить точные Framework/Docara locks, пересобрать сайты, пройти backup/rollback/release gates и проверить опубликованные сетевые ресурсы. Ничего из этого не заменяется локальным preview. |

Следующий исполняемый шаг: продолжать незакрытые архитектурные условия и подготовку согласованной поставки; не повторять доказанные сборки без изменения входов. Регрессия Docara завершена, активного тестового процесса нет.

### Последняя сверка продолжения

Повторная Utility-сборка с исправленным приоритетом gap завершена: [сравнение](foundation-utility-gap-repro.json) подтверждает совпадение 3842 файлов. Это воспроизводимость рабочих входов, не выпуск из зафиксированных коммитов.

В изолированном ui-doc обновлены страницы `gap` и `column-gap`: осевые значения уточняют общий промежуток в рамках одинакового условия. Обе страницы прошли `vendor/bin/docara validate page … --json`: exit 0, `PAGE_MARKDOWN_VALID`, errors/not_declared/review_required — 0. Установленный engine revision: `8c82a0f9aedde98a1cc093fba44b2900bc8fecfb`.

Предыдущий `FRAMEWORK_RUNTIME_MANIFEST_MISSING` возник при неверном выборе executable из рабочего репозитория Docara, ресурсы которого не соответствовали project lock. В установленном пакете ui-doc нужный manifest есть. Исправлен только способ запуска: lock, vendor и проверки целостности не изменены. Это валидация Markdown, не браузерная приёмка новых утилит в документации и не публикация.

## Основной результат

Не требуется переписывать основу целиком. Существующий utility release согласован по CSS и реестру Loader в проверяемом корпусе, но дополнительные отрицательные проверки обнаружили неполную границу распознавания класса. Также воспроизведён дефект объявления порядка CSS-слоёв. Современные container queries и subgrid не обнаружены в проверенных исходниках и release CSS; это добавочные направления, а не замена всей архитектуры.

## Как получен результат

- [Инвентаризация](foundation-inventory.json) и [сканер](scan-foundation.mjs): три корпуса — main source, utility release source, release distribution; 18 групп CSS-механизмов. Считаются SCSS/CSS без `.min.css`, удаляются CSS-комментарии. Наличие строки не равно публичной утилите или браузерной приёмке. Количество файлов не является процентом покрытия.
- [Штатная проверка](foundation-loader-audit.json): 640 модулей, 28 058 записей классов по модулям, нет missing/uncovered/position/prefix failures. Это не число глобально уникальных классов. Использован существующий `audit-utility-loader-contract.mjs --strict` из release source.
- Сопоставление 640 source rules со сгенерированным `distr/rule/rule.json`: несовпадений нет.
- [Дополнительная проверка границы](foundation-loader-boundary.json) и [воспроизведение](check-loader-boundary.mjs): настоящий `doesClassRuleMatchTokens` из исходника, семь случаев для gap/default и gap/md; три нарушения ожидаемой границы. Это проверка функции, не сетевых запросов в браузере.
- [Браузерная проверка каскада](foundation-cascade.json) и [воспроизведение](check-cascade.mjs): Chromium 151.0.7922.34, точный core.css release, изолированный документ; сетевые запросы заблокированы. Не тест опубликованного сайта и не Firefox/Safari matrix.

Все исходные репозитории читались без изменения. В Docara добавлены только документы, диагностические скрипты и их отчёты. System Node не запускался из-за отсутствующей dylib; применён готовый bundled Node 24.19.0 без переустановки окружения. Browser launch и локальный HTTP read выполнялись с разрешённым выходом из sandbox.

## Версии и сохранность незавершённых работ

| Контур | Проверенная ссылка/состояние | Вывод |
| --- | --- | --- |
| ui-loader main | `5365ecc48bcaf4dabf4484812a9b0b81ba2d44d5`, много незавершённых изменений | Не использовать как чистую release-базу и не захватывать все изменения общим коммитом |
| Utility release source | `19db6d2d786a06e2718c3bb848d616a987e81a44`; untracked `node_modules`, tracked source без изменений | Основание проверки уже выполненного utility-контракта |
| Utility distribution | `833b9ab11ed59c8ee3443ce365faf00e2d9fcaea`, чистая 5.6.4 | Сопоставляется с этим source, не со случайным ui checkout |
| ui-builder | `96b56d2a4e5bd4e3be3f839ffebf205ba7fa77c2`, 4 dirty entries в момент чтения | Точный чистый builder для следующей сборки требуется выбрать отдельно |
| ui-doc main lock | 5.6.2 | Не описывает нынешнюю публикацию |
| ui-doc utility worktree lock | 5.6.4 | Соответствует наблюдаемым ссылкам опубликованной страницы |
| Docara own-docs lock | 5.6.1 | Соответствует наблюдаемым ссылкам docara.test |

Снимок HEAD/dirty counts/lock hashes записан в JSON. Счётчики относятся к моменту запуска; отдельные source content digests позволяют сверять именно исследованный корпус. Remote refs не обновлялись; это локальная сверка, не утверждение о всех ветках GitHub.

### Проверка реально опубликованных ссылок

Read-only HTTP запросы к `https://ui-doc.test/ru/utilities/layout/container/` и `https://docara.test/ru/` успешны после разрешения sandbox network boundary.

- ui-doc отдаёт ссылки с `sf-v5.6.4-833b9ab1-b07ee017` и runtime-путём `833b9ab11ed59c8ee3443ce365faf00e2d9fcaea`.
- SHA-256 опубликованного `distr/core/js/smart-base.js` равен локальному release-файлу: `1a2e4886d1ab2aec3bc239736639a7f50aa38dc911a5690c617fdac60878212a`.
- Docara отдаёт ссылки с `sf-v5.6.1-34f5ff45-23d00d92` и runtime-путём `34f5ff453f324ce08d0a0b55e45fa4239a09f2b1`.
- Каталог `typography/5.4.0/core.css` сам по себе не обозначает Framework 5.4.0: его URL имеет отдельный revision query. Не определять версию по одному историческому имени папки.

Проверен один файл по bytes и ссылки двух страниц; полный сетевой manifest, версия самого Docara package на сайтах и интерактивная browser-приёмка всей публикации здесь не проверены. Отставание docara.test требует совместимого update, а не ручной подмены файлов.

## Матрица современного покрытия

Обозначения: «нет» — не обнаружено в явно названном корпусе; «частично» — есть реализация, но не вся требуемая система. Публичный новый API и синтаксис пока не приняты.

| Возможность | Source → release CSS | Loader / API | Документация и сценарий | Решение |
| --- | --- | --- | --- | --- |
| Container queries | Нет в обеих source-линиях и release CSS | Семейство требует проектирования | Нет основания обещать действующий SF API; нужна карточка в двух контейнерах | Добавить ограниченный профиль после принятия grammar/browser policy |
| Subgrid | Нет в проверенном source/CSS | Новый добавочный контракт | Существующий grid не доказывает subgrid | Добавить минимальный сценарий вложенного выравнивания, без смены старых grid значений |
| Cascade layers | Есть; общий порядок объявлен после первого использования | Не Loader-family, а boot/CSS contract | Новый подтверждённый конфликт порядка | Исправить первым после фиксации ожидаемого порядка и regression fixture |
| Logical properties | Есть: 57 utility-source файлов, 56 CSS utility-файлов с literal-свойствами | Существующие rules проходят общий корпус; это не доказательство полной семантики RTL | Проверить размеры, start/end, вертикальные направления и примеры | Расширять по конкретным пробелам, сохранить физические left/right |
| `color-mix()` | Есть в core и утилитах | Существующие цветовые семейства | Не начинать современную цветовую систему с нуля | Сохранить, проверить semantic tokens/fallback |
| OKLCH / `@property` | Не найдены | Не обязательное условие современности | Не обещать преимущества без задачи | Отложить до необходимости; не менять inheritance токенов автоматически |
| `:has()` | Есть в компонентах/Smart, не utility-семейство | Нет доказательства общего условного API | Локальная реализация ≠ система variants | Включить в решение по условиям без тотальной генерации комбинаций |
| `svh`/`dvh` | В main найден локальный `smart/gallery/.../default.css`; release-покрытия нет | Не общий height-контракт | Мобильная панель/галерея | Сохранить незавершённую работу; отдельно добавить нужный public sizing |
| `text-wrap` | В source не найден, в release — только Monaco CSS | Не публичная SF utility | Monaco не подтверждает наличие общего API | Добавочный сценарий заголовка/текста; не трогать кодовые пробелы |
| Scrollbar gutter | Есть core, scrollbar и scroll-subtle | Существующая utility rule покрыта | Оформление scrollbar и отсутствие layout shift — разные проверки | Проверить на overflow/scroller, не скрывать scrollbar повсеместно |
| Focus visible | Есть; main шире release | Один literal utility-файл, остальное компоненты | Поведенческий контракт сохраняется | Перенести только проверенные изменения; mouse/keyboard regression |
| Reduced motion | main: 26 файлов; release source/CSS: 3 | Нет общего utility-профиля по этому признаку | Условие accessibility, не декоративная функция | Сверить main vs release и унифицировать обязанности |
| Forced colors | Есть; main шире release | В том числе utility coverage | Требуется реальный accessibility сценарий | Не считать наличие media rule полной приёмкой |
| `@supports` | Не найден в выбранном CSS-корпусе | Нет подтверждённого общего progressive-enhancement профиля | Требуется для новой browser policy | Принять fallback-стратегию до обязательного использования новых свойств |
| Anchor positioning | Не найден | Не добавлять вслепую | Позиционирование меню/tooltip | Поздний изолированный adapter, не блокирует весь фундамент |
| View transitions | Не найден в этом Framework CSS-корпусе | У Docara отдельная незавершённая работа | Не объединять независимые задачи по одному слову | Не затрагивать другой трек; интеграция после его принятия |
| Scroll timelines | Не найдены | Не обязательный API первого этапа | Пока нет принятого обязательного сценария | Отложить |

Эта матрица — современное покрытие, не постраничный аудит всей документации. Для существующих семейств общий CSS/Loader корпус проверен; для новых семейств нужна новая specification/example пара. Статус документации не определяется только совпадением слова в Markdown.

## Два подтверждённых архитектурных дефекта

### F1. Порядок CSS-слоёв не задаёт ожидаемый приоритет задним числом

В release core.css первое `@layer sf.base` находится раньше декларации `@layer sf.reset, sf.tokens, sf.base, sf.utilities, sf.components, sf.overrides, sf.states;` (декларация на строке 2456). В source порядок расположен в конце `src/core/scss/index.scss` после `@use`.

Контрольный элемент с конфликтующими правилами sf.base/sf.tokens получает с исходным CSS синий цвет. При помещении **той же декларации** до CSS получает красный. Значит, реальный порядок отличается от предполагаемого, даже без динамического Loader. Это согласуется с правилом первого объявления [CSS cascade layers](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/At-rules/@layer).

Рекомендация: определить bootstrap объявления порядка до любого слоя; проверить полный bundle, split CSS и async arrival, обычный CSS хоста, `!important` и прежние consumers. Просто переставить строку и объявить совместимость нельзя: изменение реального каскада может менять существующие интерфейсы. Сейчас опубликованные файлы не исправлялись.

### F2. Полнота существующих классов есть, строгость границы — неполная

`doesClassRuleMatchTokens` проверяет начало совпадения, но не конец и допустимость полного значения. В результате `gap-1-unrelated`, `gap-nonexistent`, `md:gap-1-unrelated` приняты. `x-gap-1` корректно отвергнут. Штатный audit проверяет prefix-negative и поэтому остаётся зелёным.

Рекомендация: явно разделить внутренний быстрый отбор кандидатов по префиксу и окончательное распознавание допустимого класса. Использовать сгенерированную модель семейства/значений, сохраняя aliases и registered extensions. Добавить suffix-negative и invalid-value в штатные тесты. Не закреплять список только сегодняшних классов так, чтобы он блокировал разрешённые проектные расширения. Полный объём подобных совпадений по всем семействам ещё не посчитан.

## Browser policy: что уже есть и чего не хватает

В builder `libs/componentBuildLifecycle.js` задано `browsers: "last 1 version, >1%"`. Это параметр обработки CSS, а не доказанная продуктовая матрица поддержки. В просмотренных framework docs точного минимального browser/WebView контракта не найдено. Префиксы не реализуют отсутствующие layout-возможности браузера.

Политика принята владельцем 9 сентября 2026 года:

1. Тестовый профиль: актуальные Chrome/Edge, Firefox и Safari, включая iOS Safari. На каждой поставке фиксировать конкретные версии тестов, не только фразу «последние».
2. Существующие обещания и поведение не понижать молча. Новые container-query/subgrid возможности включать добавочно; для обязательного использования фиксировать поддержку в тестовом профиле и пригодное поведение при отсутствии возможности.
3. Обязательной поддержки старых браузеров и фиксированных WebView нет. Не вводить скрытые обязательства по legacy и не выдавать проверку одного Chromium за проверку всех поддерживаемых движков.
4. Примеры должны проверять и отсутствие возможности. [Container queries](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Containment/Container_queries) и [subgrid](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Grid_layout/Subgrid) — разные возможности с отдельной совместимостью.

Выбор закрыт: новые возможности ориентированы на обновляемые основные браузеры. Конкретные версии и результаты межбраузерной проверки фиксируются при поставке; принятие политики не означает, что такие тесты уже выполнены.

## Что делать дальше по тому же плану

1. Принять browser policy и ожидаемые публичные приоритеты CSS, сохранив подтверждённые интерфейсы.
2. Первым корректирующим срезом подготовить F1/F2 с полными отрицательными и прежними consumer fixtures; никакой массовой переделки каталога.
3. Следующим — ограниченный container-query контракт и один Smart в широком/узком контейнере. Нужны синтаксис, конечные условия и лимит размера поставки.
4. Сопоставить незавершённые accessibility/sizing изменения main с release, не переписывая их заново.
5. Docara source discovery и дальнейшее обновление документации выполнять в своих пунктах генерального плана. AI First и другая работа над Docara runtime не присоединяются автоматически.

Первый технический срез и выбор browser requirements завершены. Общий фундамент не объявляется готовым. Следующий рубеж — исправления F1/F2 и их регрессионная проверка. Опубликованные runtime, releases, публичные сайты, skill sources, git history и Федерация не изменены.

## Исправляющий срез: 9 сентября

Исходники изменены в существующей изолированной линии ui-loader `/private/tmp/sf5-utility-release-source`, baseline `19db6d2d786a06e2718c3bb848d616a987e81a44`. Main с незавершёнными компонентами не менялся. Это локальный кандидат, не новая опубликованная версия.

- F1: добавлен первый Sass-модуль `sf_layer_order`, сохранён прежний заявленный порядок. Core компилируется с load paths штатного builder (core/scss и resources/core). [Проверка кандидата](foundation-cascade-fixed.json): Chromium 151, исходный порядок кандидата и принудительное предварительное объявление дают одинаковый ожидаемый красный цвет. Это узкое доказательство исправления, не регрессия всех consumers и async arrival.
- F2, ограниченный срез gap: пять rules (default/sm/md/lg/xl) распознают целиком реальные пространственные значения и совместимые aliases. Не изменён общий механизм расширений Loader. [Точная функция Loader](foundation-loader-boundary-fixed.json): 7/7, прежние 3 дефекта устранены. Остальные семейства ещё требуют строгой проверки suffix/invalid value; F2 целиком не закрыт.
- Focused tests: 6/6. Полный CSS→Loader audit: 640 модулей, 28058 записей классов, ноль пропусков/position/prefix ошибок.
- Общий unit suite при `UI_DOC_ROOT=/private/tmp/sf5-utility-docs`: 150/152. Остались сбой проверки search/directional panel Admin Menu и отсутствующий локальный `human-centered-simplicity-pre-audit.json`. Эти файлы не менялись данным срезом; отдельный baseline-прогон не выполнен, поэтому регрессионный вердикт не выдан. Отсутствующее evidence не фабрикуется.

Следующее действие: расширить отрицательную матрицу на весь каталог и доказать каскад на split/async CSS и существующих consumers. До этого не выпускать кандидат; новые CSS-возможности остаются следующим пунктом того же плана.

## Продолжение исправляющего среза

- Штатный `audit-utility-loader-contract.mjs` теперь проверяет suffix-negative и недопустимое конечное значение, кроме прежнего prefix-negative. По корпусу 640 модулей первоначально найдено 47928 срабатываний на отрицательные образцы в 628 модулях. Это число проверок, не число уникальных классов или ошибок CSS. Валидные 28058 записей по-прежнему покрыты без пропусков.
- Дополнительно сужены пять rules `justify-content` и default `isolate`, без удаления канонических имён и aliases. После этого остаётся 47866 отрицательных срабатываний; strict audit закономерно завершает работу с кодом 1. F2 остаётся открытым, прежний PASS относится только к более слабой матрице.
- Тест Admin Menu ожидал запятую после search selector, хотя неизменённый исходник имеет самостоятельные LTR/RTL блоки. Тест исправлен на точные самостоятельные блоки и дополнен мобильным RTL. Component/Smart source и исходный тест до исправления совпадали с HEAD; сбой не внесён изменением core/gap. Второй сбой — отсутствие local pre-audit evidence для другого исторического процесса; не заменять фиктивным отчётом.
- [Последовательное подключение CSS](foundation-cascade-arrival.json): 5/5 Chromium. Проверены normal/important приоритеты, два порядка utility/component, раннее объявление с поздним core, unlayered host CSS и отрицательный контроль без bootstrap. Это последовательные style attachments через animation frames, не сетевой E2E Loader и не приёмка Safari/Firefox.
- Отрицательный контроль подтверждает: source fix core недостаточен, если компонент успел объявить слой раньше core. До выпуска нужно обеспечить раннее объявление в реальных точках входа и проверить existing consumers. Самовольное добавление inline-style в каждый host не выполнено.
- Focused tests: 10/10. Общий unit suite с `UI_DOC_ROOT=/private/tmp/sf5-utility-docs`: 152/153, единственный оставшийся сбой — отсутствующее historical pre-audit evidence. Container-query срез ещё не начат: исправления Loader и bootstrap не завершены и остаются ближайшим пунктом.

## Генерация точных правил: проверенный кандидат

В изолированном ui-loader добавлен build-time `scripts/utility-exact-rule.mjs`: конечный список классов преобразуется в детерминированное регулярное выражение с общими префиксами. Формат runtime-регистрации RegExp не меняется. Новая CSS-сборка должна порождать новый набор допустимых имён; не использовать список этой ревизии как постоянный runtime allowlist для будущих расширений.

Диагностический режим штатного audit `--candidate-exact` проверен на существующем release CSS. Результат: 640 модулей, 28058 записей, 0 пропусков, 0 отрицательных совпадений, 0 position failures. Сгенерированы 638 class rules; `headers/default` и `theme/default` не имеют class selectors в проверяемом слое, их отдельные tag/other triggers не заменяются пустым regex. Статус source-rules без флага остаётся needs_revision: опубликованный Loader этим экспериментом не исправлен.

Размер 638 candidate records: 146479 bytes JSON, 9385 gzip; для тех же 638 записей release manifest — 56871 bytes, 6446 gzip. Прирост около 2.9 KB gzip, не нулевой. Это только записи class rules, не измерение полного JS/runtime payload и не benchmark загрузки.

Unit fixtures проверяют fractions, отрицательные и responsive/state формы, aliases, литеральную пунктуацию, неправильные окончания, LF, стабильность результата независимо от порядка входа и добавление проектного значения через новый build input. Кандидат пока использует существующий source rule для отбора триггеров из CSS: отсутствие нового триггера должно продолжать ловиться независимым coverage gate, а не маскироваться генератором.

Обнаруженная точка интеграции builder: `libs/generatedPackaging.js`, `collectRuleManifest`/`materializeRuleManifest` сейчас читают только source rules; `rule.js` также отдельный build artifact. Для реализации надо одновременно обновить оба потребляемых пути и привязать их к CSS этой же сборки. Dirty builder (RTL/PostCSS изменения) не менялся; нельзя исправить только JSON и оставить загружаемый JS прежним. Это ближайшее действие, а не завершённая поставка.

## Интеграция сборщика

Изолированный export ui-builder `96b56d2a4e5bd4e3be3f839ffebf205ba7fa77c2`: `/private/tmp/sf5-foundation-builder.Z1vlCx`. Это рабочие изменения вне main, пока не Git-коммит. `libs/exactUtilityRules.js` компилирует index.scss текущих исходников, разбирает sf.utilities через PostCSS/selector parser, проверяет coverage исходными rules и формирует точный regex. Общий helper используется Webpack pre-loader и JSON packaging; object metadata/relation сохраняются. Зависимости Sass передаются Webpack для watch invalidation; постоянного кеша старых токенов нет.

postcss-selector-parser стал прямой используемой build-time зависимостью с уже существующей версией lock. Lock bytes и accepted digest не менялись. Проверка repository contract теперь требует реального consumer parser вместо прежнего списка «удалён как неиспользуемый»; проверки остальных удалённых dependencies сохранены.

Реальные 640 utility rules прогнаны через компиляцию SCSS: 638 с class selectors, два classless, failures0. Независимый аудит release CSS с режимом `--compiled-rules`: 28058 entries, все strict negatives и позиции PASS. Builder suite115/115. Первый настоящий core build завершился status1 на packaging: VM timeout100ms; emitted JS имеется, но это не release PASS. У emitted JS отдельно проверено совпадение regex всех640 утилит с generator output; gradient relation сохранена. Проводится повторный build с именованными diagnostics; лимит VM не повышен.

Остаются: успешная полная упаковка, JSON/JS readback готового build, воспроизводимость и live Loader/consumer regression. Prototype compiler в ui-loader ещё существует как диагностический материал; после завершения интеграции устранить двойной алгоритм и оставить builder владельцем генерации.

Повторный core product build завершён успешно: `/private/tmp/sf5-foundation-core-retry-diagnostics.json` содержит status0/diagnostics[]. Готовый JSON и исполненный JS совпадают для всех640 utility rules. Тем самым упаковка и двойной readback подтверждены; независимые повторные сборки, предупреждения инструментов и реальный browser Loader остаются следующими проверками.

## Воспроизводимость core и реальный Loader

[Сравнение двух сборок](foundation-core-repro.json): повторный output `/private/tmp/sf5-foundation-core-repro` завершён status0/diagnostics[], все59 файлов побайтово равны core-retry, включая gzip. Digest инвентаря обоих: `48b20d108ddb575fca2fe5a4400c21d245ec2eecd0071584c1bec2422a0a5ad4`. Это две пустые output-директории с одинаковыми рабочими исходниками, не окончательное доказательство release из immutable source/builder commits. Полный Utility product отдельно ещё не пересобран.

[Browser Loader evidence](foundation-loader-browser.json): настоящий core.js с lazy chunks и Loader, HTTP перехвачен Playwright и обслужен локальными файлами; production не менялся. Candidate core + прежние utility assets5.6.4, без utility.full.css. Начальные gap-nonexistent/justify-center-extra не вызывают загрузки соответствующих модулей. DOM mutation на flex/gap/justify/responsive classes загружает CSS и получает display:flex, justify-content:center, column-gap:20px. Повторное добавление класса не повторяет запросы. Ошибок JS, missing assets и внешних запросов нет. Светлая/LTR и тёмная/RTL варианты сохраняют ожидаемое выравнивание.

Наблюдение: md:col-gap-2 сейчас легально присутствует в двух модулях (column-gap/md и gap/md), поэтому приходят два разных CSS. Это не повторный запрос одного ресурса, но отдельная задача ownership/dedup в контракте утилит; текущий тест не доказывает минимальный возможный набор загрузок.

Тест использует корректное подключение core.css до core.js и модулей. Сценарий компонента, который загрузился до объявления порядка, не объявляется исправленным. Следующие проверки: завершение bootstrap contract, failure/retry и lifecycle, полный Utility build, контейнерный сценарий и остальные доказательные сценарии из генерального плана.

## Полный Utility и отказ CSS

Полный product utility собран в `/private/tmp/sf5-foundation-utility-product`, diagnostics status0/diagnostics[]. Аудит именно новых CSS против готового нового rule JSON:640 modules/28058 entries,0 missing/partial/position/uncovered. Это одна полная сборка Utility; её независимая повторная сборка ещё нужна.

[Отказ основного CSS](foundation-loader-fallback.json) и [тот же сценарий с новыми Utility assets](foundation-current-utility-browser.json): HTTP503 для justify default.css приводит к загрузке default.min.css; выравнивание работает, pageerrors/missing0, повторных запросов нет. Это fallback на другой файл, не доказательство восстановления после отказа обоих файлов или перезагрузки с закешированным missing-state.

В исходнике addStyle отдельно исправлены порядок регистрации load/error handlers (до append), once listeners и удаление failed link с корректным сообщением CSS-ошибки. Новые unit fixtures проверяют немедленный load/error при вставке, отклонение Promise, очистку failed link и сохранение successful versioned link. Focused8/8. Новое исправление требует отдельного browser readback после завершения core rebuild, нельзя приписывать его результат предыдущим evidence.

Этот rebuild завершён status0/diagnostics[]. [Проверка собранного исправления](foundation-style-cleanup-browser.json) с новым core-style-recovery и новой Utility сборкой прошла: failed link удалён, min fallback работает. Полный отказ обоих CSS и восстановление после него остаётся отдельным незавершённым сценарием.

## Постоянный кеш ошибок и полная воспроизводимость Utility

[Двойной отказ при постоянной версии](foundation-loader-persistent-failure-before.json) воспроизвёл дефект: CSS ошибки записываются в SF_MISSING_PLUGINS без срока, и восстановленная сеть после reload не помогает. Предыдущий [прогон без cacheVersion](foundation-loader-double-failure-before.json) прошёл, потому что такая конфигурация сбрасывает кеш; он не доказывает поведение versioned поставки.

Исправление source: CSS missing flags остаются только в памяти текущего документа. При записи persistent missing-state и чтении legacy-state CSS флаги отфильтровываются; существующая JS missing policy не изменена. Сохраняется подавление повторов текущей страницы, не добавлен автоматический бесконечный retry. Четыре focused tests PASS (listeners/cleanup/versioned URL/legacy filtering/persistence). Новый compiled browser readback требуется отдельно.

[Вторая полная Utility сборка](foundation-utility-repro.json): status0/diagnostics[],3842/3842 файлов идентичны первой, включая gzip. Общий digest `d0fd9c713655bd70d722eb3dd5437c23a2ceb5c047567b2b2952528402311173`. Ограничение прежнее: одинаковые рабочие inputs, пока не release из новых зафиксированных коммитов.

Core-network-recovery собран успешно status0/diagnostics[]. [Compiled after evidence](foundation-loader-persistent-failure-after.json) PASS: при той же cacheVersion после обоих отказов, восстановления сети и reload CSS снова запрашивается и применяется. Повторные URL в этом двухстраничном отчёте ожидаемы между загрузками страницы, не повторное подключение внутри одного lifecycle. In-place retry без reload и JS policy остаются отдельно от этого доказательства.

## Контейнерный сценарий на существующем Smart

[Исполняемый эксперимент](check-foundation-container.mjs) собирает настоящий `src/smart/list-item/index.js` с его базовым классом и Lit; реализация Smart и его стандартный шаблон не копируются и не меняются. Одинаковые props и существующий trailing slot показаны в областях 800px, 320px и 320px внутри 800px при одном viewport 1200px. Локальные размеры — условия испытания, не новые размерные токены продукта.

[Браузерное доказательство](foundation-container-proof.json): Chromium151, направления row/column/column, отсутствие горизонтального переполнения длинного русского текста, сохранение текста и trailing slot. Проверены light/LTR и dark/RTL, увеличение основного текста с16px до32px через существующий токен, изменение плотности через существующий токен и сужение только родителя без пересоздания Smart. Скриншот light/LTR просмотрен: широкая строка и узкие вертикальные варианты различаются ожидаемо.

Граница эксперимента: локальное правило `@container foundation-panel` в sf.overrides, порог берётся из Sass breakpoint sm (520px). Это доказывает возможность без новой архитектуры JS, но **не принимает** публичный синтаксис container utilities, не доказывает точность Loader для новых условий и не является продуктовой сборкой. Здесь целенаправленно нет fallback на viewport query: при отсутствии container queries остаётся пригодный вертикальный вариант.

Остаются: конечный публичный профиль контейнерных условий и его согласование, стоимость селекторов/модулей, named-container routing, Firefox/Safari, изменение шаблона через штатный registry, взаимодействие/фокус и lifecycle. Плотность изменена в испытании, но отдельная численная проверка изменения padding пока не сделана. Текущий результат — PARTIAL для этапа, не завершение плана.

## Внешний вид Smart и состояние

[Контракт и ограничения](smart-view-contract.md) теперь связаны с [более сильной браузерной проверкой](foundation-template-proof.json): настоящий внешний ES module загружен штатным runtime, изменён DOM, props и слот сохранены при обновлении, disconnect/connect/after-render и возврате к default. Контейнерный набор при этом повторно прошёл. Runtime/source не изменялись.

Следующее конкретное требование — исключить частичное устаревание внешнего шаблона при совпадении части его классов с DOM-оптимизацией встроенного вида. Это source-backed риск, ещё не воспроизведённый дефект; общий контракт произвольной замены вида пока не принят.

Продолжение: риск воспроизведён в [before](foundation-template-shared-before.json) и исправлен в общей границе sfBaseElement. [After](foundation-template-shared-after.json) подтверждает оба обновлённых текста и ближайшие регрессии. Три unit tests PASS; стандартная DOM-оптимизация сохранена, внешние виды используют собственный render. Исправление находится в изолированном source worktree, не в опубликованном runtime; полный Smart product build и остальная приёмка ещё нужны.

Следующий lifecycle срез также дал реальный дефект: external→default не вызывал destroy. [Before](foundation-template-cleanup-before.json)/[after](foundation-template-cleanup-after.json) показывают изменение числа контролируемых активных владельцев с1 до0. Cleanup теперь выполняется перед заменой модуля и гарантируется в finally при disconnect, идемпотентен; override компонента не обходит его. Focused suite5/5. Подробнее и пределы доказательства — [Smart/view contract](smart-view-contract.md).

## Обнаружение исходников Docara и следующее решение

В корневой README добавлен прямой вход к проекту docs/site, главной и её sidecar, а также команды существующего SDK. Реальный inspect page /ru/ подтвердил content/ru/index.md и provenance настроек; capabilities успешен; list page вернул129 страниц. Использован установленный PHP8.4.20, поскольку PHP8.2.33 из PATH не удовлетворяет текущим установленным Composer dependencies. Runtime, структура исходников и глобальный PHP не менялись. Это завершённый source-discovery шаг, не все шесть authoring задач.

[Предложение контейнерного API](container-conditions-proposal.md) подготовлено для принятия. Префикс cq и именованные контейнеры пока не реализованы и не объявлены доступными пользователям. Замеры размера требуют дальнейшей реализации кандидата и сборки.

## Проверенный авторский путь Docara

В developer-sdk.md добавлен короткий путь обычного автора, отдельно от разработки Smart: inspect→редактирование→validate→preview и dry-run новой страницы. Проверенные операции текущего checkout:

- list smart: success,16 записей; inspect docara.navigation: success. Попытка inspect smart docara.card корректно отклонена: это Markdown-контейнер, а не запись данного Smart registry. Различие явно объяснено, новый параллельный каталог не создан.
- scaffold page guides/foundation-authoring-check с locale ru/profile how_to: план создаёт один Markdown; apply не выполнялся, пробная страница не создана. План хранится в .docara/sdk-plans и является служебным артефактом, не обещанием read-only dry-run.
- validate /ru/: технических ошибок0, редакторская рекомендация1. validate обновлённой /ru/development/developer-sdk/: ошибок0/рекомендаций0.
- preview /ru/: status ok, full_site production-path render; .docara-preview/output/page/preview.json. accepted_build_receipt=false,3669 файлов runtime в preview. Это локальный изолированный результат, не публикация и не browser acceptance.

В таблице раздела4 плана фактически пять строк задач, хотя критерий этапа5 говорит о шести. Для проверки использованы эти пять задач плюс preview из идеального результата раздела; состав не расширяет архитектурный scope. Источник/настройки/каталоги/диагностика/план страницы проверены; применение нового Markdown и браузерный просмотр остаются необходимыми для полной end-to-end приёмки авторского пути.

[Браузерный просмотр существующего preview](docara-author-preview.json) теперь проверен [отдельным сценарием](check-docara-author-preview.mjs): Chromium151,1280/390px, один H1, нет горизонтального overflow, broken images, page errors или отсутствующих ресурсов. Lazy images проверяются после реальной прокрутки, не объявляются сломанными до запроса. Набор проверяет опубликованные preview bytes через перехваченный HTTP без внешней сети. Он не проверяет переходы по всем ссылкам, формы, все браузеры или полную визуальную приёмку.

Readback важен: preview содержит Framework `sf-v5.6.1-34f5ff45-23d00d92`, не новую candidate сборку. Этот PASS подтверждает существующий авторский preview, но не релиз исправлений Loader/Smart. Обновление lock и согласованная поставка остаются обязательными.

## Общая source-регрессия после Smart исправлений

Полный unit corpus после изменений дал163/164: единственный отказ — отсутствующий исторический pre-audit JSON. Оригинал найден в основном ui-loader checkout и перенесён байт-в-байт в isolated source; cmp подтверждён, SHA256 `1c1c18d617e5520ba997d1c0e52d5861521da7e3b35f08fe02899615eaae2e6e`. Тест проверил реальные multi-repo git ranges, diff hashes и surface inventory. Это восстановление существующего evidence, не новый отчёт и не выдача внешнего PASS: его статус остаётся PENDING_EXTERNAL_TESTER_REVIEW.

Повторный **полный unit run165/165 PASS** с UI_DOC_ROOT=/private/tmp/sf5-utility-docs. Количество выросло с164 до165, потому что ранее файл теста падал при загрузке, а теперь выполнились два содержащихся в нём теста. Это unit regression, не полный release gate, не cross-browser acceptance и не подтверждение всей цели.

Фактическая Docara preview receipt для /ru/ содержит цепочку site→framework-lock→section→page→content, document_ir62nodes, main_source pagebuilder_document_ir и тот же plan_hash, что preview manifest. Runtime выбрал layout docara.docs с preset landing. Поле declarative_pipeline.status=published означает запись в локальную сборку, не внешнюю публикацию. Проверен реальный declarative pipeline; сквозная проверка с backend adapter ещё остаётся.

## Штатный Smart product

Полная Smart product сборка создана в `/private/tmp/sf5-foundation-smart-product` через candidate ui-builder `run-smart-product-build.cjs`: status0, diagnostics[], webpack errorsCount0/warningsCount0. Это working-tree product, не release из зафиксированных новых refs.

[Браузерная проверка](foundation-smart-product-browser.json) использует именно `smart/list-item/js/list-item.min.js` этой сборки, а не экспериментальную webpack-компиляцию. Chromium PASS: контейнерный сценарий, внешний view, shared-DOM обновления, props, remount, default и cleanup. Геометрический CSS остаётся локальным прототипом; Loader новых container utilities этим не проверен.

Исторический первый cross-browser запуск остановился до тестов: Playwright требовал firefox1538 и webkit2336, а в кеше были другие версии. Этот локальный недостаток runtime впоследствии устранён: успешные Firefox/WebKit проверки перечислены в разделе «Cross-browser Smart evidence». Отдельная Safari/Edge приёмка остаётся открытой; WebKit PASS не заменяет Safari.

Прочитан текущий accepted canonical-page-composition contract Larena Specs: Content владеет значениями, Layout — неисполняемой структурой/валидацией, UI — Smart/render/assets, Docara — product presenters/workflows. Он согласуется с разделением слоёв фундамента; это source-level сопоставление, не runtime proof backend adapter. Larena packages в отдельных каталогах larena-ui/larena-docara отсутствуют; дальше искать их по реальному Workspace registry, а не придумывать пути.

Для межмодульных дублей подготовлено [решение о владении и совместимости](utility-ownership-proposal.md): проверяемый alias для column-gap→gap, но не для перегруженной .table. Это proposal, не реализованный контракт; новое имя display-table требует принятия.

## Исправленный gap в полном Utility product

2026-09-09: `/private/tmp/sf5-foundation-utility-gap-product` собран штатным builder:status0/diagnostics[]. Full source unit170/170PASS. [CSS↔Loader audit](foundation-gap-product-loader-audit.json):640modules/28058classes, missing/partial/position/uncovered0. Registry прежнего Core применим: изменён порядок declarations, не состав селекторов.

Матрица8640computedcases прошла уже на product CSS: [Chromium](foundation-gap-product-chromium.json), [Firefox](foundation-gap-product-firefox.json), [WebKit](foundation-gap-product-webkit.json). [Настоящий Loader network сценарий](foundation-gap-product-network.json) также PASS: новые модули подключаются без full utility bundle; двойной отказ обычного/min CSS не блокирует загрузку после восстановления и reload с тем же cache version.

Повторный clean Utility build запущен в utility-gap-repro. До сравнения файлов новая воспроизводимость не заявляется. Остальные product исходники этим gap-only изменением не затронуты; опубликованные locks не обновлялись.

## Регрессия всех сочетаний gap

2026-09-09: новый source contract test проверяет два генерационных прохода и все axis aliases для пяти variants. Вместе с utility Loader contract11/11PASS. [Браузерная матрица](foundation-gap-matrix.json) на focused compiled CSS проверила **8640 сочетаний**:5variants×12shorthand sizes×12axis sizes×2shorthand aliases×6axis aliases, failures0. Измеряются вычисленные row-gap/column-gap, expected значения разрешаются через настоящие core space tokens.

Граница: viewport2000px, не проверка breakpoint boundaries, не network Loader, не полный product artifact. Полная Utility product сборка с изменённым gap запущена отдельно; до её окончания статус сборки не заявляется.

## Исправление shorthand/axis приоритета gap

2026-09-09: [до исправления](foundation-gap-priority.json) один набор gap-8 col-gap-2 даёт column-gap80px или20px в зависимости от порядка gap/column-gap CSS. Даже отдельный gap-пакет теряет col-gap2 из-за расположения gap8 позднее в цикле токенов. Это реальная геометрическая зависимость от порядка, а не только лишний запрос.

В пяти source gap variants генерация разделена на два прохода: сначала все shorthand gap/g, затем все axis aliases. Публичные имена/значения не менялись. Все5 SCSS variants компилируются. [После исправления](foundation-gap-priority-after.json) default-сценарий одинаков для gap-only и обоих порядков подключения:row80/column20 для gap8+col2, row20/column80 для gap2+col8. Это focused Sass compile + Chromium proof, не новая product сборка.

Осталось: автоматическая регрессия всех значений/пяти variants, обоих axes и aliases, затем пересборка Utility и её воспроизводимость/Loader smoke. Прежние Utility build hashes теперь относятся к исходникам до этого изменения; не выдавать их за доказательство новой версии. Дублированная маршрутизация col-gap пока сохранена до принятия alias-контракта.

## Межмодульные дубли всего Utility-каталога

2026-09-09: [аудит](foundation-utility-overlap.json) всех640 неминифицированных CSS-модулей сравнил точные селекторы при одинаковом стеке at-rules. Найдено61 пересечение:60 col-gap с идентичными declarations и один `.table` с разными определениями. Display задаёт display:table, table-модуль добавляет оформление, ширину100% и margin-bottom1rem. Это не конфликт одинаковых CSS-свойств, но перегруженное имя: display-утилита подключает и оформление таблицы.

Ограничение метода: разные селекторы с пересекающимися DOM-условиями, shorthand/longhand и различная специфичность этим аудитом не покрыты. До изменения routing нужно решить совместимость CSS module ownership. Исключить column-gap из генератора только по имени нельзя: текущий audit требует покрытия каждого собственного CSS-селектора. Обычные .table тоже нельзя переименовать без переходного контракта. Runtime в этом шаге не менялся.

## Открытые границы Safari и ownership col-gap

2026-09-09: установлен Safari26.6.2. Реальный SafariDriver create-session вернул session not created: Allow remote automation выключено. Настройки не менялись, тестовая служба завершена; readiness evidence `/private/tmp/sf5-safari-readiness.json`. Пользователю отправлен отдельный запрос. Edge не установлен. Эти проверки остаются открытыми; WebKit PASS их не заменяет.

[Сверка actual generated registry](foundation-col-gap-ownership.json): все60 базовых/responsive col-gap классов (12значений×5условий) одновременно сопоставляются gap и column-gap модулям. На примере md:col-gap-2 оба SCSS источника задают тот же column-gap через sf-space токен в том же breakpoint. Это не ложное распознавание подстроки, а дублированное владение публичным селектором. Удалять маршрутизацию вслепую нельзя: public CSS→own-module invariant и совместимость прямого подключения требуют явной политики alias/owner. Исправление точных границ regex не закрывает этот вопрос.

## Полная регрессия исправления draft-preview

2026-09-09: `php vendor/bin/phpunit --filter 'PreviewKernelTest|PortableSiteBuilderTest'` (PHP8.4.20) завершился exit0: **53 tests,1081 assertions**,12:01.086. Проверены оба полных выбранных test-класса, не только новый draft case. Лог `/private/tmp/docara-foundation-preview-regression.log`. Это не полный suite всего репозитория.

В сочетании с CLI scaffold→apply→validate→preview, browser1280/390 и production verifier rejection этот результат закрывает найденный дефект авторского пути на локальных исходниках. Поставка/обновление потребителей пока не выполнены. Дополнительно Pint по трём изменённым PHP-файлам PASS.

## Клавиатурный фокус внешнего Smart при обновлении данных

2026-09-09: browser scenario расширен реальным Tab-переходом во внешний list-item template и последующим обновлением text без смены template/disabled. [Chromium](foundation-external-focus.json), [Firefox](foundation-external-focus-firefox.json), [WebKit](foundation-external-focus-webkit.json): PASS, тот же DOM node, document.activeElement сохранён, :focus-visible=true. Используется strict candidate Core/Component/Smart через настоящий Loader. Остальные существующие assertions сценария также проходят.

Не распространять этот результат на смену самого шаблона, отключение активного элемента, input selection/IME или полную визуальную/контрастную доступность focus ring. Эти ситуации не проверены данным assertion.

## Component reproducibility и запрет публикации preview

2026-09-09: второй полный Component build завершился status0/diagnostics[]. [Сравнение](foundation-component-repro.json):1917/1917 файлов побайтно идентичны, digest `3a911a5c58a2329d091540d361bcf205551aab7e8f874411957c3a2b77ccceb0`. Это дополняет отдельные Core/Utility/Smart working-input проверки; immutable release gate не заменён.

На настоящем preview проекта с новым draft выполнен `scripts/verify-static-build.php`: exit1 с PREVIEW_BUILD_PURPOSE_FORBIDDEN. Отказ ожидаемый и подтверждает, что preview receipt нельзя принять как production build. SDK-инструкция явно предупреждает о неопубликованных материалах в preview-кеше и запрещает использовать его вместо обычной публикационной сборки.

Полная регрессия PreviewKernelTest + PortableSiteBuilderTest запущена отдельно; до завершения процесса её PASS не заявляется.

## Исправление draft-preview и завершённый авторский сценарий

2026-09-09: PortableSiteBuilder включает draft только при BuildPurpose::Preview; public purpose сохраняет прежнее исключение. PreviewKernel пересобирает preview cache целиком, если новый маршрут отсутствует в прежнем receipt; ошибки повреждённого receipt не подавляются. Источник остаётся draft:true. SDK-инструкция дополнена этим поведением.

Focused regression: **2 tests/10 assertions PASS** — новый draft после существующего preview, его первый full-site и повторный single-page просмотр, неизменность Markdown, accepted_build_receipt=false, отсутствие страницы в public output. Реальный CLI scaffold→apply→author→validate→preview в отдельном проекте теперь завершился успешно. [Chromium preview](docara-draft-author-preview.json) на1280/390px PASS: верный H1, нет overflow/missing resources/page errors. Используется штатный starter Framework5.6.1, не кандидат нового SF5; проверка авторского пути не выдаётся за согласованный релиз.

Сценарий исправлен локально, не опубликован. Полная PreviewKernel/PortableSiteBuilder regression и дальнейшая согласованная поставка остаются необходимыми.

## Авторский путь: созданная страница и обнаруженный draft-preview gap

2026-09-09: в новом `/private/tmp/docara-foundation-author.cfw2Ou` штатный init создал проект; scaffold how_to plan `3f2c9b015653f3a1db503a6163f59c09e0ae95f09e7724a45be0cd4a065271f6` применён успешно. Создан только content/ru/guides/foundation-check.md; registry reload PASS, preview_ready=true. После добавления настоящих шагов validate: errors0/not_declared0/editorial review1. Публичная документация не изменялась тестовой страницей.

**Preview не прошёл:** PREVIEW_PAGE_UNKNOWN, поскольку scaffold создаёт draft:true, а PortableSiteBuilder исключает draft независимо от BuildPurpose::Preview. PreviewKernel затем не находит страницу в receipt. Полный CLI результат `/private/tmp/docara-foundation-author-preview-result.json`. Это реальный разрыв между обещанием SDK и авторским workflow, не отсутствие содержимого или неправильный маршрут.

Не снимать draft для маскировки ошибки. Требуется поддержать черновик только в изолированной preview-сборке с accepted_build_receipt=false и regression test: public build по-прежнему исключает черновики, preview показывает выбранный черновик, источник остаётся draft:true. До этого авторский путь не принят. Browser harness параметризован для произвольных root/route/title; новый preview ещё не проверен в браузере.

## Полная Component-сборка и строгая интеграция кандидатов

2026-09-09: штатная Component product сборка `/private/tmp/sf5-foundation-component-product` завершилась status0/diagnostics[]. Новый `--strict-candidate` режим browser harness требует явный Component root и убирает fallback к baseline release. Отсутствующий ресурс вызывает отказ проверки; источник каждого запроса записывается.

Одинаковый сценарий прошёл на новой связке Core+Component+Smart в [Chromium](foundation-all-products-integration.json), [Firefox153](foundation-all-products-firefox.json), [WebKit26.5](foundation-all-products-webkit.json): missingAssets[], errors[], strictCandidate=true. Chromium18 запросов обслужены исключительно тремя новыми product roots. Прямой script Smart не используется: регистрацию запускает Core Loader.

Это browser smoke выбранного list-item/external-template/container сценария, а не всей библиотеки. Локальный CSS контейнерного прототипа сохраняется; utility product доступен в fixture, но не считать его загруженным, если запросов к нему нет. WebKit не заменяет Safari. Component reproducibility, immutable release refs, весь перечень архитектурных решений и публикация по-прежнему открыты.

## Текущий Core: воспроизводимость и Loader→Smart

2026-09-09: повторная чистая Core сборка `/private/tmp/sf5-foundation-core-current-repro` завершилась status0/diagnostics[]. [Сравнение](foundation-core-current-repro.json) с core-network-repro:59/59 файлов идентичны, digest `d4bdf8f07d51d8a86015ab10eaf312f0574ec5a982c6f663c00c8fd8229dd48d`, differences[]. Это working-input reproducibility, не immutable-commit release gate.

[Интеграционный браузерный сценарий](foundation-core-smart-integration.json) теперь запускает настоящий Core JS и предоставляет загрузку list-item его Loader, вместо прямого script Smart. Chromium PASS: контейнеры/темы/LTR-RTL/текст200%/внешний shared-DOM template/props/remount/destroy. Проверены наличие SF.Loader, фактические пути Core/Smart запросов, отсутствие missing assets/page errors. В JSON есть весь список использованных файлов.

Граница доказательства: Core и Smart — новые кандидаты; component dropdown/icons/checkbox/icon-buttons берутся из baseline ui5.6.4, что явно видно в requestedAssets. Это совместимость смешанной тестовой связки, не готовая согласованная поставка. Требуется собрать обычные Component из тех же исходников и убрать baseline fallback из итогового release smoke. Локальный container CSS всё ещё прототип, а не принятый public API.

## Core после Smart lifecycle: обновление состава кандидата

2026-09-09: новый полный Core `/private/tmp/sf5-foundation-core-network-repro` собран status0/diagnostics[]. [Сравнение с предыдущим Core](foundation-core-network-repro.json) **неидентично**: из59 файлов изменились только `core/js/smart-base.js`, его min и оба gzip. Прочитанный diff соответствует ранее внесённым sfBaseElement исправлениям external view update/destroy/disconnect.

Следствие: предыдущий Core был собран до изменений общего Smart base; он не является тем же входом для reproducibility. Не исключать эти четыре файла из проверки и не выдавать55/59 за PASS. Новый Core — первая полная сборка текущего состава; нужна вторая чистая сборка из того же состояния и повтор совместной runtime-проверки. Latest Core candidate теперь `/private/tmp/sf5-foundation-core-network-repro`, прежний network-recovery оставлен как исторический evidence.

Сравниватель имеет автоматический положительный/отрицательный тест `check-foundation-repro.test.mjs`: изменённый, отсутствующий и добавленный файл обнаруживаются, exit status проверен. 1/1 PASS.

## Повторная Smart-сборка

2026-09-09: второй полный `run-smart-product-build.cjs` в новом `/private/tmp/sf5-foundation-smart-repro` завершился status0/diagnostics[]. [Побайтовое сравнение](foundation-smart-repro.json) с ранее проверенным в браузерах `/private/tmp/sf5-foundation-smart-product`: **621 файл идентичен**, общий SHA256 inventory `c86b79f46c492550a624726546a0714be47d56f37441f542355b1c9c25f3708f`, differences[]. Проверены все файлы дерева, включая gzip, не только выбранный list-item.

Сравниватель теперь перечисляет changed/left-only/right-only файлы при расхождении и не заявляет доказанную идентичность входных ревизий по одним выходным файлам. Эта проверка ещё не заменяет сборку из immutable source/builder commits. В stderr есть advisory Browserslist об устаревшем caniuse-lite; зависимости автоматически не обновлялись, product diagnostics остаются пустыми.

## Backend page composition: обнаруженная несовместимость

2026-09-09: штатные DocumentationPageCompositionTest и DocumentationPageCompositionSchemaTest запущены в пакете larena/docara с PHP8.4.20 и временной SQLite, которую TestCase создаёт отдельно для каждого теста. Рабочая база не используется. Повторный запуск с LOG_CHANNEL=stderr устранил невозможность записи журнала в vendor; результат: 8 тестов, 4 failures. JUnit: `/private/tmp/sf5-foundation-backend-composition-clean.xml`.

Все четыре отказа связаны с `ui_smart_prop_unknown:ui.admin_menu:settings-state-endpoint`. AdminShellViewComposer передаёт endpoint, но текущий `ui/resources/smart/ui-admin-menu/manifest.json` не объявляет его. Проверен настоящий Composer symlink Docara → Workspace UI, это не отсутствие подключения пакета. Схема хранения/rollback прошла, но полный draft→preview→publish сценарий не принят.

Не ослаблять SmartPropsValidator и не приписывать этот отказ новому SF5 candidate: backend использует собственную текущую связку пакетов. Требуется согласованное исправление producer/manifest/frontend contract владельцем Larena; чужие незавершённые изменения сохраняются. До повторного успешного запуска сквозное backend-доказательство остаётся открытым.

## Backend Smart contract: исполняемая проверка

2026-09-09: в существующем `larena-workspace/packages/ui` выполнены штатные `CompositeSmartViewTest.php`, `SmartViewDescriptorTest.php`, `SmartManagerTest.php` с PHP 8.4.20, `zend.assertions=1`, `assert.exception=1`. Все три завершились успешно. Пакеты, база данных и runtime locks не изменялись.

Проверены JSON descriptor → presets/modifiers → вложенные Smart children → HTML artifact/asset graph; передача данных поиска, таблицы и пагинации; отклонение неизвестных children/props/views, небезопасных raw_html/javascript значений и коллизий manifest. Это существующий backend-first механизм, новый параллельный adapter не требуется.

Ограничение: activation в тестах — явно заданная fixture, а не реальная доставка assets. PASS не доказывает публикацию, совместимость с новой candidate сборкой SF5 или полный путь Content → Page Composition → browser. Эти проверки остаются открытыми.

## Cross-browser Smart evidence

Совместимые браузеры загружены штатным Playwright CLI в изолированный `/private/tmp/sf5-foundation-browsers`; системные браузеры и пользовательский кеш не заменялись. [Firefox153](foundation-smart-firefox.json) и [WebKit26.5](foundation-smart-webkit.json) прошли тот же сценарий с настоящим product `list-item.min.js`: wide/narrow/nested, light/LTR, dark/RTL,200% текст, shared-DOM props, detach/attach, возврат к default и destroy. Это дополняет Chromium, но не означает полноту всех Smart, реальный Safari/Edge или готовность контейнерного публичного API.

Backend исходники найдены в larena-workspace/packages: docara/src/Composition/DocaraPageBlockPresenter.php разрешает опубликованные или draft значения Content отдельно от композиции и безопасных file refs; ui/src/Runtime/SmartManager.php валидирует props/slots, выбирает renderer через registry и возвращает FrontendRenderArtifact с графом assets. Код прочитан, package/runtime не менялся. Следующий backend proof должен использовать эти реальные классы и их существующий тестовый контур, а не новый параллельный адаптер.
