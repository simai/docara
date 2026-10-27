---
description: Коды ошибок команд для разработчика и AI-агента — заготовки scaffold, просмотр preview, визуальная проверка qa, отслеживание документации, локальный MCP — с причиной и способом исправления.
tags: [ошибка, scaffold, preview, qa, documentation, mcp, sdk]
---

# Ошибки инструментов разработчика

Эти ошибки возвращают команды, которые создают и проверяют части проекта:
`scaffold`, `preview`, `qa`, `documentation` и тот же набор операций через
локальный MCP. Как устроены эти команды, описано в разделе
[«SDK для разработчика и AI-агента»](/ru/development/developer-sdk/).

Большинство команд работает в два шага: сначала `--dry-run` составляет план
и возвращает его хеш `plan_id`, затем `--apply=<plan_id>` применяет именно
этот план. Если проект изменился между шагами, план не применяется.

## Проект

| Код | Причина и что сделать |
| --- | --- |
| `SDK_PROJECT_ROOT_INVALID` | Папка проекта — символическая ссылка или её нет. **Что сделать:** Запускайте команду из настоящей папки проекта. |
| `SDK_PROJECT_CONFIG_MISSING` | В папке нет обычного файла `docara.json`. **Что сделать:** Перейдите в папку сайта или создайте проект командой `docara init`. |
| `SDK_PROJECT_CONFIG_INVALID` | `docara.json` — не корректный JSON. **Что сделать:** Исправьте синтаксис файла. |
| `SDK_PROJECT_NAMESPACE_INVALID` | `smart.namespace` в `docara.json` содержит недопустимые символы. **Что сделать:** Начните имя со строчной латинской буквы и используйте только строчные буквы, цифры, `_` и `-`, например `project`. |
| `SDK_PROJECT_OWNED_ROOT_UNSAFE` | Папка `smart/` или `design/`, куда пишут команды, — символическая ссылка. **Что сделать:** Замените ссылку обычной папкой. |
| `SDK_WRITE_PATH_UNSAFE`, `SDK_WRITE_COLLISION` | Команда не может безопасно записать файл: путь ведёт за пределы проекта, проходит через ссылку или файл уже есть. **Что сделать:** Уберите ссылку или существующий файл, названный в сообщении, и повторите `--dry-run`. |
| `SDK_DISCOVERY_KIND_UNKNOWN`, `SDK_SCHEMA_KIND_UNKNOWN` | Команда `list`, `inspect` или `schema` получила неизвестный вид. **Что сделать:** Возьмите вид из `docara list` или справки команды `--help`. |
| `SDK_TEST_LAYOUT_CONTEXT_MISMATCH` | `docara test layout` получил страницу, которая использует другой макет. **Что сделать:** Укажите в `--page` страницу, где этот макет действительно выбран. |
| `SDK_INPUT_INVALID` | Аргумент команды записан неверно. **Что сделать:** Сверьтесь со справкой `docara <команда> --help`. |
| `SDK_OPERATION_FAILED` | Операция остановилась по причине, у которой нет своего кода. **Что сделать:** Прочитайте текст сообщения; если он не помогает, см. [«Внутренние ошибки»](/ru/reference/errors/internal/). |

## Заготовки: scaffold

| Код | Причина и что сделать |
| --- | --- |
| `SCAFFOLD_KIND_INVALID` | Неизвестный вид заготовки. **Что сделать:** Укажите `smart`, `design` или `page`. |
| `SCAFFOLD_NAMESPACE_FORBIDDEN` | Имя компонента не начинается с пространства имён проекта. **Что сделать:** Пишите `project.имя`, если в `docara.json` указано `"namespace": "project"`. |
| `SCAFFOLD_TARGET_EXISTS` | Файл или папка заготовки уже есть. **Что сделать:** Выберите другое имя или удалите старую заготовку. |
| `SCAFFOLD_PAGE_ARGUMENT_REQUIRED` | Для страницы не указаны язык или заголовок. **Что сделать:** Добавьте `--locale=ru --title="…"`. |
| `SCAFFOLD_PAGE_ROUTE_INVALID` | Адрес страницы записан неверно. **Что сделать:** Пишите строчными латинскими буквами, цифрами и дефисами внутри языка: `guides/new-page`. |
| `SCAFFOLD_PAGE_PROFILE_INVALID` | Неизвестный тип страницы в `--type`. **Что сделать:** Укажите `landing`, `article`, `tutorial`, `how_to`, `reference` или `explanation`. |
| `SCAFFOLD_PAGE_SOURCE_ARGUMENT_INVALID` | Указан только один из `--source` и `--entity`. **Что сделать:** Укажите оба или ни одного. |
| `SCAFFOLD_PAGE_VALIDATION_FAILED` | Страница создана, но сборка её не находит, например её закрывает настройка навигации. **Что сделать:** Проверьте `section.json` раздела и запустите `docara validate page <адрес>`. |
| `SCAFFOLD_PLAN_ID_INVALID` | В `--apply` передан не тот хеш. **Что сделать:** Скопируйте `plan_id` из ответа `--dry-run` целиком. |
| `SCAFFOLD_PLAN_INVALID`, `SCAFFOLD_PLAN_HASH_MISMATCH` | Файл плана в `.docara/sdk-plans/` повреждён или изменён вручную. **Что сделать:** Составьте план заново командой `--dry-run`. |
| `SCAFFOLD_PLAN_STALE`, `SCAFFOLD_CONTENT_HASH_MISMATCH` | После `--dry-run` изменились `docara.json` или файлы, которые план собирался записать. **Что сделать:** Повторите `--dry-run` и примените новый план. |
| `SCAFFOLD_PLAN_COLLISION` | План с таким хешем уже записан. **Что сделать:** Примените его `--apply=<plan_id>` или удалите файл плана из `.docara/sdk-plans/`. |
| `SCAFFOLD_SOURCE_TEMPLATE_INVALID` | Шаблон примера из источника документации повреждён. **Что сделать:** Обновите источник и повторите `--dry-run`. |

## Просмотр фрагмента: preview

`docara preview` показывает один компонент, область или макет с готовой
страницы сайта.

| Код | Причина и что сделать |
| --- | --- |
| `PREVIEW_PAGE_INVALID`, `PREVIEW_PAGE_UNKNOWN` | Страница в `--page` записана не как публичный адрес или такой страницы нет. **Что сделать:** Укажите адрес с `/` в начале и в конце: `/ru/project-demos/`. |
| `PREVIEW_PAGE_MISSING`, `PREVIEW_PAGE_OUTPUT_INVALID`, `PREVIEW_RECEIPT_INVALID` | Страница ещё не собрана или сборка устарела. **Что сделать:** Выполните полную сборку и повторите. |
| `PREVIEW_SELECTOR_INVALID`, `PREVIEW_SELECTOR_FORBIDDEN` | Для `page` и `layout` указан `--selector`, а для других видов его нет или он записан неверно. **Что сделать:** Для `page` и `layout` уберите `--selector`; для остальных укажите имя строчными буквами: `--selector=ui.dropdown`. |
| `PREVIEW_TARGET_NOT_FOUND` | На странице нет такого компонента или области. **Что сделать:** Выберите страницу, где он стоит, или проверьте имя. |
| `PREVIEW_WATCH_OPTIONS_INVALID` | `--interval` или `--max-cycles` вне допустимых значений. **Что сделать:** Укажите интервал от 50 до 5000 миллисекунд и число циклов не меньше 0. |
| `PREVIEW_DEPENDENCY_PATH_INVALID` | Файл, за которым следит `--watch`, лежит вне проекта или стал ссылкой. **Что сделать:** Уберите ссылку и перезапустите просмотр. |
| `PREVIEW_OUTPUT_COLLISION`, `PREVIEW_PUBLIC_ROOT_INVALID`, `PREVIEW_PUBLIC_ROOT_PURPOSE_INVALID`, `PREVIEW_PUBLIC_FILE_INVALID`, `PREVIEW_HTML_INVALID`, `PREVIEW_ASSET_PROVENANCE_INVALID` | Папка просмотра занята, повреждена или собрана не для просмотра. **Что сделать:** Удалите папку просмотра, названную в сообщении, и повторите команду. |

## Визуальная проверка: qa

`docara qa` снимает компонент, область или макет в светлой и тёмной теме, на
широком и узком экране и сравнивает снимки с эталоном.

| Код | Причина и что сделать |
| --- | --- |
| `QA_KIND_INVALID` | Неизвестный вид проверки. **Что сделать:** Укажите `smart`, `region` или `layout`. |
| `QA_LAYOUT_CONTEXT_MISMATCH` | Страница в `--page` использует другой макет. **Что сделать:** Укажите страницу, где этот макет выбран. |
| `QA_TARGET_LOCATOR_MISSING`, `QA_TARGET_BINDING_INVALID` | На странице не найден проверяемый элемент или план относится к другому элементу. **Что сделать:** Проверьте имя и страницу, составьте план заново `--dry-run`. |
| `QA_PLAN_ID_INVALID`, `QA_PLAN_ID_MISMATCH`, `QA_ARTIFACT_INVALID`, `QA_PREVIEW_BINDING_INVALID`, `QA_PLAN_COLLISION` | План в `.docara-qa/plans/` не совпадает с хешем, повреждён или уже есть. **Что сделать:** Передайте точный `plan_id` из ответа `--dry-run` или составьте план заново. |
| `QA_REFERENCE_DRAFT_INVALID`, `QA_REFERENCE_DRAFT_BINDING_INVALID`, `QA_REFERENCE_DRAFT_SCENARIOS_INVALID`, `QA_REFERENCE_COLLISION` | Черновик эталона неполный, снят по другому плану или уже утверждён. **Что сделать:** Снимите эталон заново по текущему плану и повторите `--finalize-reference`. |
| `QA_REFERENCE_INVALID`, `QA_REFERENCE_BINDING_INVALID`, `QA_REFERENCE_ID_MISMATCH`, `QA_REFERENCE_MANIFEST_MISMATCH` | Утверждённый эталон изменён после утверждения. **Что сделать:** Не правьте снимки эталона вручную — утвердите новый эталон. |
| `QA_SCREENSHOT_INVALID`, `QA_REPORT_BINDING_INVALID`, `QA_REPORT_SCENARIOS_INCOMPLETE` | Снимков меньше, чем в плане, или отчёт снят по другому плану. **Что сделать:** Запустите сравнение заново по утверждённому плану. |
| `QA_VISUAL_DIFF_MISMATCH` | Снимок отличается от эталона. **Что сделать:** Посмотрите разницу; если изменение задумано, утвердите новый эталон. |
| `QA_ACCEPTANCE_FAILED` | Есть ошибки доступности, ошибки в консоли браузера или горизонтальная прокрутка. **Что сделать:** Исправьте найденные в отчёте проблемы и повторите проверку. |

## Отслеживание документации

Отслеживание сравнивает страницы с источниками, например с описанием
компонентов SIMAI Framework. Настройка описана в разделе
[«Актуальность документации»](/ru/authoring/documentation-tracking/).

| Код | Причина и что сделать |
| --- | --- |
| `DOCUMENTATION_TRACKING_DISABLED` | Отслеживание выключено. **Что сделать:** Включите `documentation_tracking` в `docara.json`. |
| `DOCUMENTATION_PROVIDER_UNKNOWN` | Неизвестный поставщик источника. **Что сделать:** Укажите `simai_framework` или `contract_json`. |
| `DOCUMENTATION_SOURCE_UNKNOWN`, `DOCUMENTATION_SOURCE_DUPLICATE`, `DOCUMENTATION_SOURCE_ID_MISMATCH` | Источник не настроен, указан дважды или его `id` в настройках не совпадает с `id` в файле источника. **Что сделать:** Оставьте один источник с одинаковым `id` в обоих местах. |
| `DOCUMENTATION_SOURCE_CONTRACT_INVALID`, `DOCUMENTATION_SOURCE_HASH_MISMATCH` | Файл источника повреждён или не совпадает с закреплённой версией. **Что сделать:** Верните файл источника из той же версии, что указана в `simai-framework.lock.json`. |
| `DOCUMENTATION_FRAMEWORK_LOCK_INVALID`, `DOCUMENTATION_FRAMEWORK_RULES_INVALID`, `DOCUMENTATION_FRAMEWORK_RULES_MISSING` | Закреплённая версия SIMAI Framework не подходит этой версии Docara. **Что сделать:** Обновите Docara или верните закрепление, которое она поставляет. |
| `DOCUMENTATION_ENTITY_UNKNOWN`, `DOCUMENTATION_ENTITY_DUPLICATE` | В источнике нет сущности с таким ключом или ключ повторяется. **Что сделать:** Возьмите ключ из `docara documentation status --json`. |
| `DOCUMENTATION_ROUTE_INVALID`, `DOCUMENTATION_ROUTE_LOCALE_INVALID` | Страница сущности не найдена, найдено несколько или она не на основном языке источника. **Что сделать:** Свяжите сущность с одной страницей на языке `source_locale`. |
| `DOCUMENTATION_EXAMPLE_CASES_MISSING` | На странице нет обязательных примеров, перечисленных в источнике. **Что сделать:** Добавьте примеры, названные в сообщении. |
| `DOCUMENTATION_EXCLUSION_REASON_REQUIRED` | Сущность исключена без причины. **Что сделать:** Напишите причину исключения. |
| `DOCUMENTATION_REVIEW_INVALID` | Неизвестный вид проверки в `--review`. **Что сделать:** Укажите `ai_verified` или `human_reviewed`. |
| `DOCUMENTATION_STATUS_FILTER_INVALID` | Неизвестный статус в `--status`. **Что сделать:** Возьмите статус из раздела [«Статусы»](/ru/authoring/documentation-tracking/#статусы). |
| `DOCUMENTATION_LOCK_INVALID`, `DOCUMENTATION_LOCK_DUPLICATE`, `DOCUMENTATION_LOCK_SOURCE_LOCALE_MISMATCH` | `documentation.lock.json` повреждён, сущность записана в нём дважды или язык в нём не совпадает с настройками. **Что сделать:** Не правьте файл вручную: верните его из истории и примите изменения командой `docara documentation accept`. |
| `DOCUMENTATION_PLAN_ID_INVALID`, `DOCUMENTATION_PLAN_INVALID`, `DOCUMENTATION_PLAN_HASH_MISMATCH`, `DOCUMENTATION_PLAN_COLLISION` | План в `.docara/documentation-plans/` не совпадает с хешем, повреждён или уже есть. **Что сделать:** Передайте точный `plan_id` из ответа `--dry-run` или составьте план заново. |
| `DOCUMENTATION_PLAN_STALE` | Страница или источник изменились после `--dry-run`. **Что сделать:** Повторите `--dry-run` и примените новый план. |
| `DOCUMENTATION_PAGE_INVALID`, `DOCUMENTATION_INPUT_UNSAFE` | Страница или файл источника — ссылка или лежат вне проекта. **Что сделать:** Замените ссылку обычным файлом внутри проекта. |
| `DOCUMENTATION_WRITE_FAILED`, `DOCUMENTATION_WRITE_PATH_UNSAFE`, `DOCUMENTATION_WRITE_CASE_COLLISION` | Не удалось записать файл: нет прав, путь проходит через ссылку или отличается от существующего только регистром. **Что сделать:** Исправьте путь или права и повторите. |
| `DOCUMENTATION_SOURCE_COMPATIBILITY_ADAPTER` | Предупреждение: закреплённая версия SIMAI Framework не содержит своего описания для отслеживания, и Docara строит его из правил Framework, которые поставляет сама. **Что сделать:** Ничего; предупреждение уйдёт, когда закреплённая версия Framework начнёт поставлять своё описание. |

## Локальный MCP

| Код | Причина и что сделать |
| --- | --- |
| `MCP_METHOD_NOT_FOUND`, `MCP_TOOL_UNKNOWN` | Клиент вызвал метод или инструмент, которого у Docara нет. **Что сделать:** Получите список инструментов методом `tools/list` и вызывайте только их. |
| `MCP_WRITE_CAPABILITY_REQUIRED` | Применение плана запрещено: MCP запущен только для чтения. **Что сделать:** Просмотрите план, затем перезапустите локальный MCP с `--allow-writes`. |

## См. также

- [SDK для разработчика и AI-агента](/ru/development/developer-sdk/)
- [Команды](/ru/reference/cli/)
- [Актуальность документации](/ru/authoring/documentation-tracking/)
- [Коды ошибок](/ru/reference/errors/)
