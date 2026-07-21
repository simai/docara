# Smart-компоненты Docara

Docara использует тот же основной принцип, что Larena и Simai Framework:
интерфейс собирается из компонентов с известным контрактом, а не из HTML,
спрятанного в compiler или builder. Standalone-сборка при этом остаётся
PHP-only и не получает runtime-зависимость от Laravel.

## Три разных слоя

```text
Layout region -> Section recipe -> Smart component
```

| Что видит пользователь | Область | Секция | Smart-компонент |
| --- | --- | --- | --- |
| Бренд в шапке | `header` | `docara.header` | `docara.brand` |
| Левое меню | `sidebar` | `docara.navigation` | `docara.navigation` |
| Содержание страницы | `outline` | `docara.outline` | `docara.toc` |

Область задаёт место в макете. Секция описывает, какими блоками это место
наполнено. Smart-компонент владеет props, представлениями, шаблонами, assets,
frontend-событиями и readiness.

Имена `docara.header` и `docara.outline` остаются у секций ради стабильности
конфигурации. Старые component ID принимаются только как deprecated aliases:
`docara.header` разрешается в `docara.brand`, а `docara.outline` — в
`docara.toc`. Новому коду следует использовать только canonical ID.

## Где находится компонент

```text
resources/smart/docara.<name>/manifest.json
resources/smart/docara.<name>/views/*.json
resources/smart/docara.<name>/templates/*
resources/smart/assets/*
src/Smart/DocaraSmartContribution.php
src/Declarative/Rendering/View/*ViewModel.php
```

Manifest описывает контракт и Atlas: props, events, views, presets, assets,
состояния и readiness. View выбирает только зарегистрированный template.
Template получает immutable ViewModel. CSS и JavaScript публикуются только для
компонентов, которые присутствуют в asset plan страницы.

Интерфейсные подписи также входят в проверяемые props. Builder берёт их из
language pack текущей страницы и передаёт в `docara.navigation` и
`docara.toc`; product template и component JavaScript не содержат
языкозависимых строк. Поэтому один компонент работает с любым числом локалей и
RTL без отдельных шаблонов.

## Представления и presets

- `docara.brand`: `default`, `compact`;
- `docara.navigation`: `default`, `compact`, `tree`;
- `docara.toc`: `default`, `compact`.

View определяет способ отображения одного контракта. Preset даёт именованную
готовую конфигурацию view. Встроенная секция навигации использует `tree`, чтобы
показывать до четырёх уровней, сохранять вложенность и раскрывать активную
ветвь.

## Общий реестр

`SmartRegistry` строится из contributions. Встроенные contributions сейчас:

- `FrameworkSmartContribution` для допущенных `ui.*`;
- `DocaraSmartContribution` для product-компонентов `docara.*`.

Один `SmartManifestValidator` проверяет общую платформонезависимую часть обоих
семейств. Для `ui.*` дополнительно действует exact Framework lock и consumer
policy. Registry fail-closed отклоняет неизвестный component, view, template,
asset, несовместимые props или неполную readiness-запись.

## Frontend-поведение

`docara.navigation` сам владеет раскрытием дерева, защитой ссылок с дочерними
пунктами, показом активной ветви и своим CSS. `docara.toc` владеет событием
перехода по содержанию, определяет активный заголовок при прокрутке, помечает
его через `aria-current="location"` и выводит маркер на линии правой области.
`docara.brand` владеет вариантами бренда и адаптивным отображением подписи.

Общий publisher shell только размещает готовые артефакты и registered chrome
fragments. Он не содержит разметку product-компонентов и не выбирает их по
именам.

## Как изменить вид левого меню

Левое меню не зашито в макет страницы. Его данные и внешний вид разделены:

```text
section/region JSON
  -> props с деревом навигации
  -> view docara.navigation
  -> template + component-owned CSS/JavaScript
```

Для изменения только внешнего вида не нужно править Markdown, builder или
publisher shell:

| Задача | Источник |
| --- | --- |
| Контракт props, views, presets и assets | `resources/smart/docara.navigation/manifest.json` |
| Выбор зарегистрированного template | `resources/smart/docara.navigation/views/*.json` |
| Разметка всего меню | `resources/smart/docara.navigation/templates/*.blade.php` |
| Разметка одного рекурсивного пункта | `resources/smart/docara.navigation/templates/item.php` |
| Состояния, токены и адаптация Framework Menu | `resources/smart/assets/navigation.css` |
| Раскрытие ветвей и component event | `resources/smart/assets/navigation.js` |
| Выбор view встроенной секцией | `resources/sections/docara.navigation.json` |

Встроенный `tree` использует настоящие примитивы Simai Framework:
`sf-menu`, `sf-menu-item`, `sf-menu-element`, `sf-icon-button` и `sf-icon`.
Размеры, отступы, цвета hover/active, радиусы и темы задаются токенами
Framework. Поэтому светлая, тёмная и системная темы не требуют отдельных
шаблонов.

Если нужен существенно другой вариант, добавьте новый зарегистрированный view
в `resources/smart/docara.navigation/views/`, соответствующий template и
preset в manifest, а затем выберите его в section JSON. Не создавайте
page-specific CSS и не
редактируйте `build_local` или `build_production`: это generated output.

Текущий `tree` сопоставлен с Simple Menu из SF UI Kit: Figma node
`17583:25972` задаёт Menu Item и его состояния, а `17607:34059` показывает
собранное многоуровневое меню. Фиксированная ширина демонстрации Figma не
копируется — компонент занимает доступную ширину responsive-области `sidebar`.

## Как добавить новый product Smart

1. Создайте manifest, views, templates и component-owned assets.
2. Добавьте immutable ViewModel и преобразование проверенных props.
3. Верните definition из отдельного `SmartContribution` или расширьте
   `DocaraSmartContribution`.
4. Если компонент доступен автору, отдельно сузьте allowlist соответствующего
   Block или Section.
5. Добавьте positive, negative, alias/compatibility и browser tests.
6. Покажите компонент в source-backed демонстраторе.

Project JSON не может указывать PHP-класс, callback, template path, raw HTML или
JavaScript. Это сохраняет portable-сборку детерминированной и безопасной.

Живой результат: [product Smart-компоненты в одном макете](/examples/product-smart-runtime/).
