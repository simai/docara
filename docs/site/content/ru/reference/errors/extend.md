---
description: Коды ошибок оформления и расширения сайта — меню и области макета, своя композиция оболочки, привязки данных, компоненты проекта в smart/, макеты и секции в design/, рецепты композиции — с причиной и способом исправления.
tags: [ошибка, макет, области, smart, design, рецепт, привязка]
---

# Ошибки оформления и расширения

Эти ошибки относятся к тому, как собирается каркас страницы: меню, области
макета, собственные секции оболочки, компоненты проекта в `smart/`, макеты и
секции в `design/` и рецепты композиции. Как это устроено, описано в
разделах [«Области макета»](/ru/settings/layout-and-regions/) и
[«Композиция интерфейса»](/ru/development/composition/).

## Меню и макет

| Код | Причина и что сделать |
| --- | --- |
| `DECLARATIVE_NAVIGATION_DEPTH_EXCEEDED` | Меню получилось глубже четырёх уровней. **Что сделать:** Перенесите страницы глубже четвёртого уровня выше или объедините разделы. |
| `DECLARATIVE_CONTAINER_MAX_INVALID` | `layout.container.max` — не целое число от 1 до 8. **Что сделать:** Укажите число от 1 до 8; для большинства сайтов подходит `7`. |
| `DECLARATIVE_CONTENT_GAP_INVALID` | `layout.content.gap` — не целое число от 0 до 8. **Что сделать:** Укажите `0` для обычной статьи или число до 8 для лендинга. |
| `DECLARATIVE_REQUIRED_REGION_DISABLED` | Отключена основная область `main`. **Что сделать:** Уберите `layout.regions.main.enabled: false`. |
| `DECLARATIVE_LAYOUT_CONFIGURATION_INVALID` | `layout.key` называет незарегистрированный макет. **Что сделать:** Укажите `docara.docs` или макет проекта из `design/layouts/`. |
| `DECLARATIVE_SHELL_BRANDING_INVALID` | Логотип для тёмной темы задан без основного логотипа, или выбран режим `logo` без файла логотипа. **Что сделать:** Укажите основной логотип в разделе `brand`. |

## Своя композиция области

Области `layout.regions.<область>.sections` собираются из секций и блоков.

| Код | Причина и что сделать |
| --- | --- |
| `DECLARATIVE_REGION_UNKNOWN` | В `layout.regions` указана область, которой нет в макете. **Что сделать:** Используйте `header`, `sidebar`, `main`, `outline` или `footer`. |
| `DECLARATIVE_REGION_CONFIGURATION_INVALID`, `DECLARATIVE_REGION_SECTION_INVALID`, `DECLARATIVE_REGION_BLOCK_INVALID`, `DECLARATIVE_REGION_ELEMENT_INVALID` | Описание области, секции или блока записано неверно: не объект, нет `id` или `section`, неизвестное поле. **Что сделать:** Сверьте запись с примером в разделе [«Собственная композиция области»](/ru/settings/layout-and-regions/#собственная-композиция-области). |
| `DECLARATIVE_REGION_SECTION_LIMIT_EXCEEDED`, `DECLARATIVE_REGION_BLOCK_LIMIT_EXCEEDED` | В области больше 8 секций или в секции больше 12 блоков. **Что сделать:** Объедините или уберите лишние. |
| `DECLARATIVE_SECTION_INSTANCE_ID_DUPLICATED`, `DECLARATIVE_BLOCK_INSTANCE_ID_DUPLICATED`, `DECLARATIVE_DOCUMENT_NODE_ID_DUPLICATED` | Два элемента получили одинаковый `id`. **Что сделать:** Дайте каждой секции и блоку свой `id`. |
| `DECLARATIVE_DOCUMENT_REGION_MANAGED` | Область `main` заполняется самой страницей, её нельзя описать секциями. **Что сделать:** Уберите `sections` у `main`. |
| `DECLARATIVE_SECTION_CAPABILITY_MISMATCH`, `DECLARATIVE_SECTION_REGION_FORBIDDEN` | Секция не подходит этой области. **Что сделать:** Поставьте её в область, для которой она предназначена, — это видно в `docara atlas --json`. |
| `DECLARATIVE_BLOCK_SECTION_FORBIDDEN`, `DECLARATIVE_BLOCK_SLOT_FORBIDDEN`, `DECLARATIVE_REGION_BLOCK_KIND_FORBIDDEN`, `DECLARATIVE_BLOCK_PAYLOAD_MISMATCH` | Блок нельзя ставить в эту секцию или в это место секции, или его данные не подходят его виду. **Что сделать:** Используйте `shell.element` или `shell.smart` и сверьте поля с примером. |
| `DECLARATIVE_ELEMENT_INVALID`, `DECLARATIVE_ELEMENT_HREF_FORBIDDEN`, `DECLARATIVE_ELEMENT_HREF_INVALID`, `DECLARATIVE_ELEMENT_ARIA_LABEL_INVALID` | Элемент `shell.element` записан неверно: `href` указан не у ссылки, ссылка ведёт не на адрес этого сайта или подпись для программ экранного доступа пустая. **Что сделать:** Давайте `href` только элементу `a`, пишите адрес от корня сайта и заполняйте подпись. |
| `DECLARATIVE_VIEW_UTILITY_NOT_ALLOWED` | В `utilities` указан класс, которого нет в закреплённой версии SIMAI Framework. **Что сделать:** Используйте классы из [справочника утилит Framework](https://ui.simai.io/). |
| `DECLARATIVE_AUTHOR_EXECUTABLE_SURFACE_FORBIDDEN` | В настройках `layout` есть поле, похожее на шаблон или код (в имени — `template`, `html`, `script`, `style`, `php`, `path` и подобные), или значение с `<script`, `<style`, `<?php`, `javascript:`. Настройки выбирают только зарегистрированные части. **Что сделать:** Уберите это поле; свою разметку оформите секцией в `design/`. |

## Привязки данных оболочки

| Код | Причина и что сделать |
| --- | --- |
| `DECLARATIVE_REGION_BINDING_FORBIDDEN`, `DECLARATIVE_BINDING_CAPABILITY_MISMATCH` | `bind` называет неизвестную привязку или привязку, которая не подходит этой области. **Что сделать:** Возьмите привязку из `docara atlas --json` для нужной области. |
| `BINDING_SMART_MISMATCH`, `BINDING_PRESENTATION_FORBIDDEN` | Привязка не умеет заполнять этот компонент или его представление. **Что сделать:** Выберите компонент и представление, которые указаны у привязки. |
| `BINDING_OWNED_PROP_COLLISION` | Параметр, который заполняет привязка, задан ещё и вручную. **Что сделать:** Уберите этот параметр из `props`. |

## Компоненты проекта

Компоненты проекта лежат в `smart/<имя>/`. Создавать их удобно командой
`docara scaffold smart <имя> --dry-run`.

| Код | Причина и что сделать |
| --- | --- |
| `SMART_PROJECT_NAMESPACE_RESERVED` | Пространство имён `ui` или `docara` занято встроенными компонентами. **Что сделать:** Выберите другое имя в `smart.namespace`, например `project`. |
| `SMART_PROVIDER_NAMESPACE_INVALID`, `SMART_PROVIDER_NAMESPACE_REQUIRED`, `SMART_PROVIDER_NAMESPACE_NOT_OWNED`, `SMART_PROVIDER_NAMESPACE_COLLISION` | Имя компонента не начинается с пространства имён проекта или оно совпадает с чужим. **Что сделать:** Назовите папку `smart/<пространство>.<имя>/`, например `smart/project.notice/`. |
| `SMART_PROVIDER_JSON_INVALID`, `SMART_MANIFEST_INVALID`, `DECLARATIVE_SMART_MANIFEST_INVALID` | `manifest.json` или файл представления — не корректный JSON или в нём не хватает полей, например примера параметров для атласа. **Что сделать:** Запустите `docara validate smart <имя>` и исправьте названное поле. |
| `SMART_PROVIDER_TEMPLATE_REQUIRED` | В папке `template/` компонента нет ни одного шаблона `.php` или `.blade.php`. **Что сделать:** Добавьте шаблон, например `template/default.php`. |
| `SMART_PROVIDER_ASSET_INVALID` | Путь к стилю или скрипту в разделе `assets` файла `manifest.json` записан не строкой или файла нет. **Что сделать:** Положите файл в папку компонента и укажите путь к нему строкой. |
| `SMART_PROVIDER_ALIASES_INVALID` | Дополнительные имена компонента в `extensions.docara.aliases` записаны неверно. **Что сделать:** Пишите их объектом `{"другое.имя": "причина"}` с непустой причиной. |
| `SMART_PROVIDER_ROOT_UNSAFE`, `SMART_PROVIDER_PATH_UNSAFE`, `SMART_PROVIDER_SYMLINK_FORBIDDEN` | В папке компонента есть символическая ссылка или путь выходит за `smart/`. **Что сделать:** Замените ссылки обычными файлами. |
| `SMART_REGISTRY_COMPONENT_NOT_FOUND` | Команда получила имя компонента, которого нет. **Что сделать:** Возьмите имя из `docara list smart`. |
| `DECLARATIVE_COMPOSITE_PRESET_UNKNOWN`, `DECLARATIVE_COMPOSITE_PRESET_INVALID` | У составного компонента нет такого пресета или он записан не строкой. **Что сделать:** Возьмите имя пресета из `docara inspect smart <имя> --json`. |
| `DECLARATIVE_COMPOSITE_PROPS_INVALID`, `DECLARATIVE_COMPOSITE_MANIFEST_INVALID` | Параметры составного компонента не подходят его описанию или описание повреждено. **Что сделать:** Сверьте параметры с `docara inspect smart <имя> --json`. |

## Макеты и секции проекта

Свои макеты, представления, секции и блоки лежат в `design/layouts/`,
`design/views/`, `design/sections/` и `design/blocks/`.

| Код | Причина и что сделать |
| --- | --- |
| `DESIGN_PROJECT_NAMESPACE_RESERVED` | Пространство имён `docara`, `content`, `shell` или `ui` занято. **Что сделать:** Используйте пространство имён проекта. |
| `DESIGN_ARTIFACT_JSON_INVALID` | Файл в `design/` — не корректный JSON. **Что сделать:** Исправьте синтаксис. |
| `DESIGN_ARTIFACT_ID_INVALID`, `DESIGN_VIEW_ID_INVALID` | Имя файла или `id` записаны неверно. **Что сделать:** Называйте представления `layout.<пространство>.<имя>` или `section.<пространство>.<имя>`. |
| `DESIGN_ARTIFACT_NAMESPACE_FORBIDDEN`, `DESIGN_NAMESPACE_COLLISION` | Описание использует чужое пространство имён. **Что сделать:** Используйте пространство имён проекта. |
| `DESIGN_ARTIFACT_DUPLICATE` | Два описания с одинаковым `id`. **Что сделать:** Переименуйте одно. |
| `DESIGN_ARTIFACT_PATH_INVALID`, `DESIGN_ARTIFACT_OUTSIDE_ROOT`, `DESIGN_PROVIDER_ROOT_INVALID`, `DESIGN_PROVIDER_ROOT_MISSING`, `DESIGN_PROVIDER_DIRECTORY_INVALID` | Папка `design/` или её подпапка — ссылка, её нет или файл лежит не в своей подпапке. **Что сделать:** Держите файлы в обычных папках `design/layouts`, `views`, `sections`, `blocks`. |
| `DESIGN_DEFAULT_LAYOUT_INVALID` | Ни один макет или несколько макетов объявлены макетом по умолчанию. **Что сделать:** Оставьте ровно один. |
| `DESIGN_LAYOUT_VIEW_INVALID`, `DESIGN_LAYOUT_REGION_CONTRACT_INVALID`, `DECLARATIVE_LAYOUT_VIEW_REGIONS_MISMATCH` | Макет ссылается на несуществующее представление, или представление ставит не все области макета либо ставит какую-то дважды. **Что сделать:** Поставьте каждую область макета в представление ровно один раз. |
| `DESIGN_SECTION_VIEW_INVALID`, `DESIGN_SECTION_SLOT_CONTRACT_INVALID`, `DESIGN_SECTION_REGION_CONTRACT_INVALID`, `DESIGN_SECTION_BLOCK_CONTRACT_INVALID`, `DECLARATIVE_SECTION_VIEW_SLOTS_MISMATCH` | Секция ссылается на несуществующее представление или блок, её места не совпадают с представлением или она не подходит указанной области. **Что сделать:** Проверьте секцию командой `docara validate section <id>`. |
| `DESIGN_VIEW_TREE_INVALID`, `DECLARATIVE_VIEW_TAG_NOT_ALLOWED`, `DECLARATIVE_VIEW_ATTRIBUTE_NOT_ALLOWED`, `DECLARATIVE_VIEW_ATTRIBUTE_INVALID`, `DECLARATIVE_VIEW_UTILITY_INVALID`, `DECLARATIVE_VIEW_NODE_INVALID`, `DECLARATIVE_VIEW_TARGET_DUPLICATED` | Дерево представления использует запрещённый тег или атрибут, записано неверно или ставит одно место дважды. **Что сделать:** Используйте разрешённые теги и атрибуты; список показывает `docara schema view --json`. |
| `DECLARATIVE_DEFINITION_NOT_ALLOWED`, `DESIGN_ARTIFACT_NOT_FOUND` | Указано описание, которое не зарегистрировано. **Что сделать:** Возьмите `id` из `docara list layout`, `view`, `section` или `block`. |

## Рецепты композиции

Рецепт композиции (`composition_recipe` в заголовке страницы) собирает
страницу из готовых частей.

| Код | Причина и что сделать |
| --- | --- |
| `COMPOSITION_RECIPE_PAGE_CONTENT_CONFLICT` | У страницы с рецептом есть свой текст. **Что сделать:** Перенесите видимый текст в источники рецепта, а в файле страницы оставьте только блок настроек между `---`. |
| `COMPOSITION_RECIPE_PLAN_PARITY_FAILED`, `COMPOSITION_RECIPE_DOCUMENT_INVALID` | Рецепт изменил план страницы, который он должен был лишь повторить, или описание страницы неполное. **Что сделать:** Пересоберите сайт целиком; если ошибка повторяется, сообщите о ней. |
| `COMPOSITION_RECIPE_LOCK_FAILED`, `COMPOSITION_RECIPE_ACTIVATION_CONFLICT` | Рецепт одновременно собирает другая сборка. **Что сделать:** Дождитесь её окончания и повторите. |
| `COMPOSITION_RECIPE_SNAPSHOT_MISSING`, `COMPOSITION_RECIPE_SNAPSHOT_UNKNOWN`, `COMPOSITION_RECIPE_SNAPSHOT_INTEGRITY_FAILED`, `COMPOSITION_RECIPE_SNAPSHOT_COLLISION`, `COMPOSITION_RECIPE_HEAD_INVALID`, `COMPOSITION_RECIPE_HEAD_COLLISION`, `COMPOSITION_RECIPE_DIGEST_INVALID`, `COMPOSITION_RECIPE_PAGE_KEY_INVALID`, `COMPOSITION_RECIPE_RESULT_INVALID`, `COMPOSITION_RECIPE_ACTIVATION_FAILED`, `COMPOSITION_RECIPE_ACTIVATION_REVISION_INVALID` | Сохранённый результат рецепта повреждён, изменён вручную или его не удалось записать. **Что сделать:** Удалите папку `.docara/composition-recipe/` в корне проекта и выполните полную сборку. |

## См. также

- [Области макета](/ru/settings/layout-and-regions/)
- [Композиция интерфейса](/ru/development/composition/)
- [Smart-компоненты](/ru/development/smart-components/)
- [Коды ошибок](/ru/reference/errors/)
