# Расширение декларативной композиции

Этот путь предназначен для maintainer-разработчика пакета Docara. Автор сайта
может выбирать только зарегистрированные элементы через JSON; project JSON не
может указывать PHP, Blade, HTML template, callback или путь к исполняемому
файлу.

## Модель владения

```text
Layout
  -> Region
    -> Section
      -> Slot
        -> Block
          -> Smart или безопасный element
```

| Слой | Canonical definition | Исполняемая часть |
| --- | --- | --- |
| Layout | `resources/layouts/*.json` | зарегистрированный layout View Tree |
| Section | `resources/sections/*.json` | зарегистрированный section View Tree |
| Block | `resources/blocks/*.json` | renderer, разрешённый Section |
| View Tree | `resources/views/*.json` | общий `ViewTreeRenderer` |
| Product Smart | `resources/smart/<id>/manifest.json` и `views/*.json` | trusted template + immutable ViewModel |
| Framework Smart | owner manifest + exact Framework lock | consumer policy + registered view/template |

Файлы не обнаруживаются по glob. Каждый executable ID регистрируется явно и
проверяется fail-closed.

## Добавление Layout

1. Создайте `resources/layouts/<id>.json` по
   `resources/schemas/declarative-layout.schema.json`.
2. Создайте `resources/views/layout.<id>.json` по schema View Tree. Дерево
   содержит только разрешённые element/region nodes и утилиты Framework.
3. Зарегистрируйте оба файла в `DefinitionRepository::DEFINITIONS`.
4. Добавьте ID в enum `layout.key` файла `presentation.schema.json`.
5. Расширьте `RegionCompositionResolver`: допустимый key, список областей,
   section matrix и структурные defaults должны согласовываться с manifest.
6. Добавьте positive test разрешения и rendering, negative test неизвестного
   Layout и browser fixture.

Сейчас продукт публично допускает только `docara.docs`; одного нового JSON-файла
для второго Layout недостаточно.

## Добавление Section и Slot

1. Создайте `resources/sections/<id>.json`: задайте тип, разрешённые regions,
   slots, blocks и View Tree ID.
2. Создайте `resources/views/section.<id>.json`. Каждый slot в tree обязан
   существовать в Section manifest.
3. Зарегистрируйте Section и View Tree в `DefinitionRepository`.
4. Разрешите Section только в нужных regions в schema и
   `RegionCompositionResolver`; не добавляйте глобальный wildcard.
5. Проверьте duplicate ID, неверный region, неизвестный slot, пустую Section и
   успешную композицию.

## Добавление Block

1. Создайте `resources/blocks/<id>.json` по declarative Block schema.
2. Зарегистрируйте definition и добавьте ID только в `allowed_blocks` нужной
   Section.
3. Если Block доступен автору, расширьте узкий JSON contract и серверный
   validator одинаково. Schema без runtime-проверки или runtime без schema
   считаются незавершённой регистрацией.
4. Свяжите Block с существующим renderer либо добавьте отдельный renderer с
   immutable input model; HTML не должен появляться в compiler.
5. Добавьте positive/negative tests и точный пример.

## Добавление View Tree

View Tree описывает только структуру: безопасные HTML-теги, utilities, regions
и slots. Он не содержит переводимого текста и не исполняет PHP.

1. Скопируйте ближайшее дерево из `resources/views` и смените ID.
2. Проверьте его `declarative-view-tree.schema.json`.
3. Зарегистрируйте `view:<id>` в `DefinitionRepository`.
4. Укажите ID из соответствующего Layout или Section manifest.
5. Запустите inspector/render tests для неизвестного kind, тега, utility,
   region и slot.

## Добавление product Smart-компонента

Для `docara.<name>` нужны все части одной регистрации:

1. `resources/smart/<id>/manifest.json` — props, views, assets, lifecycle и
   owner;
2. `resources/smart/<id>/views/default.json` — template ID;
3. immutable ViewModel в `src/Declarative/Rendering/View` и factory method;
4. trusted PHP или Blade template в `resources/smart/<id>/templates`;
5. template registration в `TrustedTemplateRegistry`;
6. manifest/view registration в `DefinitionRepository`;
7. resolver/factory branch в `SmartRenderer` и `ViewModelFactory`;
8. если вызов разрешён в authored shell — enum schema, allowlist и prop checks
   в `RegionCompositionResolver`;
9. language-pack presentation, exact fixture, positive/negative tests,
   generated catalog и browser acceptance.

Template получает только object ViewModel. Не передавайте в него сырой
авторский массив и не читайте JSON из template.

## Добавление Smart-компонента Simai Framework

Сначала пройдите owner admission: manifest и assets должны существовать в
зафиксированной паре Core/Smart, а provider revision и SHA-256 — в exact lock.
Затем добавьте только сужающие consumer metadata, view, ViewModel, trusted
template и проверки из списка выше. Подробности:
[Framework-компоненты](/development/framework-components/).

## Обязательная проверка

```bash
php vendor/bin/phpunit tests/Unit/DeclarativeViewCompositionTest.php
php vendor/bin/phpunit tests/Unit/DeclarativeRegionCompositionTest.php
php vendor/bin/phpunit tests/Unit/DeclarativePageCompilerTest.php
php vendor/bin/phpunit tests/Unit/DeclarativeRenderingTest.php
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
```

После этого проверьте exact fixture в generated catalog и браузере. PASS одного
компонента не означает готовность всего Framework, public release или
production readiness Docara.
