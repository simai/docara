# Модель компонентов

Страница документации начинается с обычного Markdown. Компонент нужен только
тогда, когда у фрагмента есть отдельный смысл, параметры, состояние или правила
композиции. Все варианты проходят один путь: Markdown → typed Document IR →
renderer registry → Smart Gateway → LayoutComposer → PageBuilder.

## Как выбрать

| Задача | Используйте | Где смотреть |
| --- | --- | --- |
| Заголовок, абзац, ссылка, изображение, список, цитата, код или таблица | Обычный Markdown | [Markdown](/ru/authoring/markdown/) |
| Короткая метка, кнопка-ссылка, значок или клавиша внутри строки | Inline Docara | [Каталог компонентов](/ru/components/) |
| Самостоятельный смысловой фрагмент | Блочный компонент Docara | [Каталог компонентов](/ru/components/) |
| Несколько вложенных блоков с ограничениями | Контейнер Docara | [Surface](/ru/components/surface/) и [Сетка](/ru/components/grid/) |
| Принятый внешний UI artifact | SIMAI Framework Smart | [Framework-компоненты](/ru/development/framework-components/) |
| Компонент конкретного проекта | Project-owned Smart или shell contribution | [Developer/AI SDK](/ru/development/developer-sdk/) |

## Обычный Markdown

Это основной и предпочтительный формат. Он покрывает заголовки, текст, ссылки,
изображения, списки, цитаты, inline-код, fenced code, таблицы и сноски. Raw HTML
страницы запрещён диагностикой `MARKDOWN_RAW_HTML_FORBIDDEN`. Все
поддерживаемые конструкции перечислены в
[каталоге компонентов](/ru/components/) и подробно описаны в
[руководстве по Markdown](/ru/authoring/markdown/).

## Inline-компонент Docara

Inline-компонент не разрывает абзац:

```markdown
Сборка :badge[готова]{type=main scheme=success size=1/2}.
Нажмите :kbd[⌘ K] или :button[откройте каталог]{href=/ru/components/ type=link}.
```

Публично поддерживаются `badge`, `button`, `icon` и `kbd`. Неизвестный alias,
prop или небезопасное значение отклоняются до render.

## Блочный компонент Docara

Блочный компонент занимает самостоятельное место в Document IR:

```markdown
:::alert {type=warning variant=outlined}
#### Обратите внимание

Проверьте параметры перед публикацией.
:::
```

Directive-name разрешается typed registry. Точные параметры и ошибки находятся
на странице конкретного компонента.

## Контейнеры и композиция

Контейнер принимает только children и slots, объявленные его registry-owned
contract. Он проверяет `min_children`, `max_children`, порядок и относительную
глубину subtree. Fence length помогает записать вложение, но не меняет тип и не
обходит admission.

```markdown
::::grid {columns=2 gap=2}
:::card
### Первый шаг

Проверьте конфигурацию.
:::
:::card
### Второй шаг

Соберите страницу.
:::
::::
```

Для общей контентной полосы используйте [Surface](/ru/components/surface/).
Hero, Showcase и Promo уже используют общую Surface presentation и не требуют
дополнительной авторской обёртки.

## SIMAI Framework

Docara принимает только immutable, exact-pinned Smart artifacts, прошедшие
owner admission и cross-host proof. Manifest, view, preset, slots, templates,
assets и hydration проверяются до render. Статус `supported` относится только
к явно принятому artifact; похожий raw Framework component автоматически не
становится поддерживаемым.

Пример разрешённой композиции:

```markdown
::::ui.dropdown {label="Тариф"}
:::ui.list-item {slot=options type=text value=team label="Командный"}
:::
::::
```

## Компоненты проекта

Проект может владеть content Smart и shell contribution в разрешённых project
roots. Конфигурация выбирает admitted IDs и не принимает callback, PHP class или
произвольный template/filesystem path. Dry-run фиксирует diff и input hashes;
apply выполняется только для неизменившегося hash-bound plan.

Практический путь: [inspect → scaffold → validate → preview → test](/ru/development/developer-sdk/).

## Fail-closed граница

Сборка останавливается при неизвестном компоненте или prop, неправильном
child/slot/count/order/depth, незакрытом fence, namespace collision, hash
mismatch, traversal, symlink/hardlink escape и попытке передать исполняемый или
неразрешённый путь. Эти проверки выполняются до template execution и не
ослабляются в preview.
