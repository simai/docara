---
description: Внутренние проверки Docara, которые автор не вызывает правкой страниц и настроек, что делать, если они сработали, и отметки успешных проверок в выводе команд.
tags: [ошибка, внутренняя-ошибка, сообщить-об-ошибке, validate, doctor]
---

# Внутренние ошибки

Docara проверяет не только проект, но и себя: что её собственные описания
компонентов, шаблоны и промежуточные данные сборки согласованы. Эти проверки
не зависят от текста страниц и настроек — если такая ошибка появилась,
дело не в вашем проекте.

## Что делать

1. Выполните полную сборку ещё раз: `docara build <окружение>`. Иногда
   ошибку вызывает прерванная прошлая сборка.
2. Проверьте, что Docara установлена целиком и не изменена вручную:
   `composer install` восстановит файлы пакета из `composer.lock`.
3. Если ошибка повторяется, сообщите о ней в
   [репозиторий Docara](https://github.com/simai/docara/issues). Приложите
   код, вывод `docara doctor --json` и, если можно, страницу, на которой
   сборка остановилась. Не прикладывайте пароли и токены.

Не обходите ошибку правкой результата сборки: следующая сборка его заменит.

## Коды внутренних проверок

### Разбор страницы

Промежуточное представление страницы, которое Docara строит перед выводом.

| Код | Что проверяется |
| --- | --- |
| `DOCUMENT_AST_EMPTY`, `DOCUMENT_AST_INVALID`, `DOCUMENT_IR_EMPTY`, `DOCUMENT_INPUT_INVALID` | Страница разобрана в непустую корректную структуру |
| `DOCUMENT_IR_SOURCE_LOCATION_INVALID`, `DOCUMENT_IR_SOURCE_NODE_TYPE_INVALID`, `DOCUMENT_SOURCE_SPAN_INVALID` | У каждого блока есть точное место в исходном файле |
| `DOCUMENT_HEADING_INVALID`, `DOCUMENT_LINK_INVALID`, `DOCUMENT_MARKDOWN_NODE_INVALID` | Заголовки, ссылки и обычный Markdown разобраны без потерь |
| `DOCUMENT_IR_COMPONENT_NODE_INVALID`, `DOCUMENT_IR_COMPONENT_BLOCK_NODE_INVALID`, `DOCUMENT_IR_CONTAINER_NODE_INVALID`, `DOCUMENT_COMPONENT_RENDERER_NODE_INVALID`, `DOCUMENT_COMPONENT_BLOCK_RENDERER_NODE_INVALID` | Блоки и компоненты переданы на вывод в ожидаемом виде |
| `DOCUMENT_SMART_CALL_NODE_INVALID`, `DOCUMENT_SMART_COMPONENT_NODE_INVALID`, `DOCUMENT_SMART_COMPONENT_ORDINAL_INVALID`, `DOCUMENT_SMART_VIEW_INVALID` | Вызовы Smart-компонентов пронумерованы и переданы на вывод целиком |
| `DOCUMENT_CONTAINER_CONTRACT_MISSING`, `DOCUMENT_CONTAINER_ORDER_INVALID`, `DOCUMENT_CONTAINER_SLOT_INVALID`, `DOCUMENT_CONTAINER_DEPTH_SEMANTICS_INVALID`, `DOCUMENT_CONTAINER_RENDERER_INVALID`, `DOCUMENT_CONTAINER_RENDERER_UNSUPPORTED`, `DOCUMENT_CONTAINER_CHILD_ARTIFACTS_REQUIRED` | У полосы, сетки и карточки есть полное описание вложенности |
| `DOCUMENT_IR_RENDERER_UNKNOWN`, `DOCUMENT_IR_RENDERER_DUPLICATE` | Для каждого вида блока есть ровно один способ вывода |
| `DOCUMENT_OUTLINE_PARSE_FAILED`, `DOCUMENT_OUTLINE_SERIALIZE_FAILED` | Оглавление построено по готовой странице |

### Вывод страницы

| Код | Что проверяется |
| --- | --- |
| `PAGE_BUILDER_FAILED` | Обёртка над ошибкой вывода страницы: в сообщении после двоеточия — исходная причина. Если в ней есть другой код, ищите его в этом справочнике. |
| `PAGE_BUILDER_DOCUMENT_ARTIFACT_REQUIRED`, `PAGE_BUILDER_DOCUMENT_IR_REQUIRED`, `RENDER_ARTIFACT_HTML_REQUIRED` | Каждая страница получила разобранный документ и готовый HTML |
| `RESOLVED_BLOCK_PLAN_INVALID`, `RESOLVED_RENDER_PLAN_INVALID`, `RESOLVED_SMART_PLAN_INVALID`, `COMPOSITION_RECIPE_PAGE_RESULT_INVALID` | План вывода страницы собран целиком |
| `DECLARATIVE_BLOCK_RENDERER_UNSUPPORTED`, `DECLARATIVE_DOCUMENT_ELEMENT_INVALID`, `DECLARATIVE_DOCUMENT_MARKDOWN_INVALID`, `DECLARATIVE_DOCUMENT_NODES_REQUIRED`, `DECLARATIVE_DOCUMENT_NODE_INVALID`, `DECLARATIVE_DOCUMENT_NODE_RESOLVERS_REQUIRED`, `DECLARATIVE_DOCUMENT_NODE_RESOLVER_DUPLICATED`, `DECLARATIVE_DOCUMENT_RENDERERS_REQUIRED`, `DECLARATIVE_DOCUMENT_RENDERER_DUPLICATED`, `DECLARATIVE_DOCUMENT_SMART_INVALID`, `DECLARATIVE_REGION_SMART_INVALID` | Шаблон страницы получил все части документа и областей |
| `LAYOUT_DESCRIPTOR_INVALID`, `LAYOUT_REGION_INVALID`, `NAVIGATION_SCOPE_INVALID` | Описание макета, его областей и навигации согласовано |
| `SURFACE_SEMANTIC_FRAME_INVALID`, `SURFACE_SEMANTIC_PADDING_INVALID`, `SURFACE_SEMANTIC_TONE_INVALID`, `SURFACE_PADDING_2XL_REQUIRES_CONTAINER` | Оформление полосы и первого экрана получило проверенные значения |
| `MARKDOWN_HERO_EFFECT_ANCHOR_MISSING` | У первого экрана с фоновым эффектом есть место для эффекта |
| `SMART_INVOCATION_INVALID`, `SMART_TEMPLATE_CONTEXT_INVALID`, `SMART_CONTEXT_BRANDING_INVALID` | Шаблон Smart-компонента получил полный набор данных |

### Встроенные описания Docara

| Код | Что проверяется |
| --- | --- |
| `TYPED_COMPONENT_SOURCE_INVALID`, `TYPED_COMPONENT_DEFINITION_INVALID`, `TYPED_COMPONENT_DEFINITION_MISMATCH`, `TYPED_COMPONENT_DUPLICATE`, `TYPED_COMPONENT_RENDERER_UNKNOWN` | Описания блоков Docara на месте, не повторяются и совпадают с кодом вывода |
| `CONTENT_COMPONENT_REGISTRY_EMPTY`, `CONTENT_COMPONENT_DEFINITION_INVALID`, `CONTENT_COMPONENT_TEMPLATE_INVALID`, `CONTENT_COMPONENT_ALIAS_INVALID`, `CONTENT_COMPONENT_ALIAS_MISMATCH`, `CONTENT_COMPONENT_PROP_CONTRACT_INVALID`, `CONTENT_COMPONENT_SLOT_CONTRACT_INVALID`, `CONTENT_COMPONENT_NORMALIZATION_INVALID` | Описания строчных компонентов корректны |
| `SCHEMA_INVALID`, `SCHEMA_NOT_FOUND` | Схемы настроек входят в пакет и читаются |
| `NATIVE_MARKDOWN_PROFILE_INVALID` | Набор поддерживаемых конструкций Markdown совпадает с включёнными расширениями |
| `DOCARA_AI_CONTRACT_INVALID`, `DOCARA_AI_CONTRACT_MISSING` | Описание возможностей для AI-агента входит в пакет |
| `SDK_RESULT_CONTRACT_INVALID` | Ответ команды с `--json` соответствует своей схеме |
| `TARGET_SITE_JSON_FORBIDDEN`, `TARGET_PUBLIC_INPUT_FORBIDDEN`, `TARGET_COMPOSITION_KIND_INVALID`, `TARGET_CONFIG_PROSE_FORBIDDEN`, `TARGET_COMPONENT_PROSE_FORBIDDEN` | Файлы пакета не попадают во входные данные сайта, а тексты страниц не хранятся в настройках компонентов |

## Отметки успешных проверок

`docara doctor`, `docara validate` и их вывод `--json` перечисляют и
пройденные проверки — со статусом `pass`. Это не ошибки: исправлять ничего не
нужно.

| Код | Что прошло проверку |
| --- | --- |
| `PROJECT_CONFIG_VALID` | Настройки проекта |
| `SCHEMA_CATALOG_VALID` | Каталог схем настроек |
| `SMART_REGISTRY_VALID`, `DESIGN_REGISTRY_VALID`, `BINDING_REGISTRY_VALID` | Списки Smart-компонентов, дизайн-описаний и привязок; рядом — их количество |
| `DESIGN_ARTIFACT_VALID` | Одно дизайн-описание |
| `PAGE_MARKDOWN_VALID` | Markdown одной страницы |
| `DOCUMENTATION_SOURCES_VALID`, `DOCUMENTATION_SOURCE_VALID` | Источники отслеживания документации: все и один |

## См. также

- [Коды ошибок](/ru/reference/errors/)
- [Устранение неполадок](/ru/troubleshooting/)
