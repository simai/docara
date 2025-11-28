---
extends: _core._layouts.documentation
section: content
title: 'CustomTagRenderer (Пользовательский тегРендерер)'
description: 'CustomTagRenderer (Пользовательский тегРендерер)'
---

# CustomTagRenderer (Пользовательский тегРендерер)

Этот компонент рендерит `CustomTagNode` экземпляров в HTML в конвейере League CommonMark. Он либо делегирует полномочия **Рендерер по тегам** (если указано) или возвращается к элементу-оболочке по умолчанию с отрисованными дочерними элементами.

> Примечание: В некоторых репозиториях файл может иметь имя `CustomTagRender.php` в то время как класс `CustomTagRenderer`. Класс, описанный здесь, является рендерером, используемым CommonMark через `NodeRendererInterface`.

---

## Местоположение и подпись
- Пространство имен: `App\Helpers\CommonMark`
- Класс:`CustomTagRenderer`
- Реализует: `League\CommonMark\Renderer\NodeRendererInterface`

```php
final readonly class CustomTagRenderer implements NodeRendererInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): mixed
    {
        if (!$node instanceof CustomTagNode) return '';
        $spec = $this->registry->get($node->getType());

        if ($spec?->renderer instanceof \Closure) {
            return ($spec->renderer)($node, $childRenderer);
        }

        return new HtmlElement(
            $spec?->htmlTag ?? 'div',
            $node->getAttrs(),
            $childRenderer->renderNodes($node->children())
        );
    }
}
```

---

## Поток рендеринга
1. **Проверка типа**:Не-`CustomTagNode` ➜ Возврат пустой строки.
2. **Поиск спецификаций**: извлекает свойство тега `CustomTagSpec`От `CustomTagRegistry`около`type()`.
3. **Пользовательский рендерер?**
    - Если в спецификации предусмотрено закрытие при `$spec->renderer`, он вызывается как:
      ```php
      fn(CustomTagNode $node, ChildNodeRendererInterface $children): mixed
      ```
      При закрытии должен быть возвращен объект `HtmlElement` или струна.
4. **Рендеринг по умолчанию**
    - Если рендерер для каждого тега не предусмотрен, по умолчанию `HtmlElement` возвращается:
        - **Имя тега**: `$spec->htmlTag` или `'div'` если отсутствует.
        - **Атрибуты**: `$node->getAttrs()` (уже объединено/отфильтровано ранее).
        - **Детский HTML**: `$childRenderer->renderNodes($node->children())`.

---

## Потеговый рендерер: как его написать
Рендерер для каждого тега дает вам полный контроль над выводом. Рекомендуемый шаблон:

```php
public function renderer(): ?callable
{
    return function (CustomTagNode $node, ChildNodeRendererInterface $children): HtmlElement {
        $attrs = $node->getAttrs();
        $meta  = $node->getMeta();            // e.g., ['openMatch' => ..., 'attrStr' => ...]
        $inner = $children->renderNodes($node->children());

        // Read attributes safely; prefer HtmlElement for auto‑escaping
        $classes = $attrs['class'] ?? '';
        $caption = $attrs['caption'] ?? '';

        return new HtmlElement('figure', ['class' => $classes],
            $inner . ($caption !== '' ? new HtmlElement('figcaption', [], $caption) : '')
        );
    };
}
```

### Доступ к данным
- **Атрибуты**: `$node->getAttrs()` — объединенные значения по умолчанию + встроенные, уже нормализованные по `Attrs` и опционально отфильтрован по `attrsFilter($attrs, $meta)`.
- **Meta**: `$node->getМета()` — Что включено `openMatch` (захватывает регулярное выражение) и `attrStr` (необработанный сегмент атрибутов).
- **Дети**: `$children->renderNodes($node->children())` — внутренний Markdown в виде HTML.

> Предпочитать `HtmlElement` над ручным конкатенированием струн; Он обрабатывает экранирование атрибутов за вас.

---

## Распределение обязанностей
- **Где нормализовать/проверить атрибуты?** В теге `attrsFilter($attrs, $meta)`, а не в рендерере. Сосредоточьтесь на структуре.
- **Где происходит объединение атрибутов?** Во время запуска блока (см. `UniversalBlockParser`) и перед рендерингом.
- **Кто выбирает бирку-обертку?** Технические характеристики `htmlTag` для пути по умолчанию; рендереры для каждого тега могут игнорировать его и выводить любую необходимую структуру.

---

## Пограничные случаи и поведение
- **Неизвестные характеристики**: Если реестр возвращает `null` (неправильная настройка), резервный тег по умолчанию имеет значение `'div'` с любыми атрибутами, которые есть в узле.
- **Пустое содержимое**: дети могут быть пустыми; Путь по умолчанию по-прежнему возвращает элемент-оболочку.
- **Тип возвращаемого значения**: Возврат `HtmlElement` или струна. Избегайте возврата необработанных неэкранированных данных пользователя.

---

## Чек-лист для тестирования
- Путь по умолчанию: без рендерера для каждого тега, подтвердить обёртку = `spec.htmlTag` (или `div`) и атрибуты присутствуют.
- Пользовательский путь: убедитесь, что замыкание вызвано; Verify it использует `$children->renderNodes(...)` и уважает атрибуты.
- Атрибуты: перепроверьте, что классы объединены/дедупликированы вверх по течению; Рендерер не должен выполнять повторное слияние.
- Использование мета: если ваш рендерер полагается на именованные снимки из `openRegex`, утверждают, что они появляются в `$node->getMeta()['openMatch']`.

---

## Примечание о миграции (если вы видели старую подпись)
Более ранние черновики, иногда описываемые `renderer()` как `fn(string $innerHtml, array $attrs): string`. Текущая реализация проходит **узел** и **Дочерний рендерер** вместо. Чтобы адаптироваться:

- Получить внутренний HTML через `$children->renderNodes($node->children())`.
- Получение объединенных атрибутов через `$node->getAttrs()`.
- Использование `$node->getMeta()` для чтения захватов регулярных выражений или строки необработанного атрибута, если это необходимо.


