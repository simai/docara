# Добавление Smart-компонента Simai Framework

Новый `:::ui.*` вызов нельзя добавить только изменением parser, schema или
метаданных каталога. Единственный admission authority — точный
`simai-framework.lock.json`.

Сначала пройдите общий выбор уровня в
[руководстве по развитию возможностей](/development/extensions/). Этот раздел
описывает только случай, когда native Markdown и typed-компонент Docara уже не
решают задачу.

Минимальный принимаемый набор:

1. подтвердить, что задачу нельзя решить native Markdown или существующим
   typed-компонентом Docara;
2. добавить source-backed `larena.ui.smart_manifest.v1` с props, host renderer
   и immutable upstream revision;
3. добавить manifest в точный Simai Framework lock с provider revision и
   SHA-256;
4. описать полный immutable asset dependency plan, включая транзитивные
   зависимости;
5. добавить узкие consumer metadata в
   `resources/component-catalog/smart/`: они описывают использование в Docara,
   но не могут расширять manifest;
6. добавить positive и negative props, constraint, dependency и lock tests;
7. проверить в браузере поведение, клавиатуру, темы, адаптивность и
   доступность;
8. добавить presentation в language packs и отдельный exact fixture; generated
   catalog создаст страницу компонента автоматически без ручного
   Markdown-дубля.

Каждый critical asset из manifest должен либо присутствовать в вычисленном
asset plan, либо быть назван в сужающей consumer-policy с конкретной причиной.
Static verifier сверяет каждый материализованный Framework asset с точной
проекцией lock: путь, безопасный тип файла, SHA-256 и полный набор файлов.
До очистки каталога назначения build preflight проверяет bytes каждого
зафиксированного файла и равенство проекции полному dependency closure всех
допущенных компонентов.
Текущий portable-контур явно исключает Larena backend event bridge: Docara не
допускает backend handlers, data binding или выполнение эффектов. Это
ограничение отражается в машинном каталоге, а не скрывается.

Поля `authoring.parameters` в эффективном каталоге описывают ввод автора, а
не уже нормализованные host props. `required: false` означает только, что
автор может не указывать параметр. Поле `default` присутствует, когда точное
базовое значение есть в `atlas.example_props`; без него свойство остаётся
отсутствующим до явного ввода или применения preset. `preset` остаётся
отдельным необязательным параметром. `validation` переносит строковые и
числовые правила свойства, а `mirrors` явно показывает детерминированное
заполнение связанного свойства (например, текста доступного имени). Поле
`authoring.constraints` без потерь переносит
`allowed_combinations` и `requires` из manifest, чтобы разработчик и ИИ не
генерировали формально допустимые, но несовместимые сочетания.

Сборка требует точного равенства между Smart metadata и manifest keys,
допущенными lock. Поэтому лишний metadata-файл, отсутствующий manifest,
несовпадающий hash или moving revision завершает сборку ошибкой. Схема
`docara.component_call.v1` проверяет только форму `ui.*` вызова и не является
реестром допуска.

Consumer metadata не может повысить readiness или расширить название,
категорию и состояния manifest. Поддерживаемый компонент требует
`safe_to_suggest` и `safe_to_render`. Его состояния точно равны состояниям
manifest в исходном порядке за вычетом
`consumer_policy.excluded_states`; каждое исключение связано с конкретной
запрещённой парой property/value, которая сама проверяется по точной схеме
свойства. Описание берётся из policy-owned ограниченного контракта поведения
Docara, а не копируется из более широкого upstream-описания.

Для простых семантических конструкций сначала проверьте, достаточно ли
обычного Markdown или typed-рецепта Docara. Точные поддерживаемые вызовы,
параметры и примеры находятся в [generated catalog](/components/catalog/).

## Как зафиксировать ещё не готовую потребность

Если renderer или admission-набор ещё не готов, добавьте неисполняемую запись
в `resources/component-catalog/requirements/` с lifecycle
`admission_pending`, `framework_gap` или `deferred`. Запись должна содержать
owner, reason, fallback и admission condition. Она делает дефицит видимым, но
не разрешает директиву.

Requirement-запись никогда не переключается в `supported` на месте. После
готовности она удаляется, а исполняемая возможность появляется из своего
authoritative source: native profile, typed definition либо Smart metadata с
точным lock admission. Для исполняемой записи обязательны renderer, тесты,
документация и example fixture. Каталог
`_docara/component-catalog.json` в output сборки не является каноническим
реестром Simai Framework и не подтверждает production или public-release
readiness.
