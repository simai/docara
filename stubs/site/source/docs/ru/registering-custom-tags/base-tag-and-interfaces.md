---
extends: _core._layouts.documentation
section: content
title: 'BaseTag и интерфейсы'
description: 'BaseTag и интерфейсы'
---

# BaseTag и интерфейсы

На этой странице определен контракт для пользовательских тегов и задокументировано поведение по умолчанию, предоставляемое `BaseTag`. Начните отсюда, прежде чем реализовывать собственные классы тегов.

---

## Контракт: `CustomTagInterface`
Расположен по адресу `App\Helpers\Interface\CustomTagInterface.php`.

```php
interface CustomTagInterface
{
    public function type(): string;

    /** Regex for the opening line. Must expose an `attrs` named group if attributes are supported. */
    public function openRegex(): string;

    /** Regex for the closing line. */
    public function closeRegex(): string;

    /** Wrapper element for default rendering (e.g., 'div', 'section'). */
    public function htmlTag(): string;

    /** Baseline attributes merged with inline attributes on the open line. */
    public function baseAttrs(): array;

    /** Allow nesting of the same tag type inside itself. */
    public function allowNestingSame(): bool;

    /** Optional filter to normalize/whitelist attributes. Signature: fn(array $attrs, array $meta): array */
    public function attrsFilter(): ?callable;

    /** Optional renderer to fully control output. Signature: fn(string $innerHtml, array $attrs): string */
    public function renderer(): ?callable;
}
```

> Обычно вы **вытягивать `BaseTag`** который реализует этот интерфейс с безопасными настройками по умолчанию и проверенными регулярными выражениями.

---

## Реализация по умолчанию: `BaseTag`
Расположен по адресу `App\Helpers\CommonMark\BaseTag.php`.

```php
abstract class BaseTag implements CustomTagInterface
{
    abstract public function type(): string;

    public function openRegex(): string {
        return '/^\s*!' . preg_quote($this->type(), '/') . '(?:\s+(?<attrs>.+))?$/u';
    }

    public function closeRegex(): string {
        return '/^\s*!end' . preg_quote($this->type(), '/') . '\s*$/u';
    }

    public function htmlTag(): string { return 'div'; }

    public function baseAttrs(): array { return []; }

    public function allowNestingSame(): bool { return true; }

    public function attrsFilter(): ?callable { return null; }

    public function renderer(): ?callable { return null; }
}
```

### Почему эти дефолты?
- **Регулярные выражения** закреплены в точке **Начало линии** и терпимо относиться к ведущим пробелам. Тем `openRegex()` Предоставляет именованный захват `attrs` Таким образом, парсер может извлекать встроенные атрибуты, если они присутствуют.
- **`htmlTag()`** По умолчанию `div`, который является самой безопасной обёрткой блоков.
- **`allowNestingSame()`** есть `true` сохранять гибкость авторства; Вы можете отключить его там, где это имеет смысл.
- **`attrsFilter()`** и **`renderer()`** являются точками расширения: используйте их только в случае необходимости дополнительного контроля.

---

## Пошаговое руководство

### `type(): string`
- Уникальный, короткий, строчный по соглашению (например, `note`, `example`, `video`).
- Отображается в Markdown как `!<type>` и `!end<type>`.

### `openRegex()` / `closeRegex()`
- Если вы переопределите, сохраните параметр **семантика**:
    - Якорь с `^` чтобы избежать случайных совпадений по средней линии.
    - Сохраняйте параметр **Именованная группа** `(?<attrs>...)` для открытой строки, если вам нужны встроенные атрибуты.
    - Использование Юникода `u` Редактировать так `\s` а классы символов обрабатывают пробелы, отличные от ASCII.
- Пример (настройка для разрешения псевдонима):

```php
public function openRegex(): string {
    $t = preg_quote($this->type(), '/');
    return '/^\s*!(?:' . $t . '|ex)\b(?:\s+(?<attrs>.+))?$/u';
}

public function closeRegex(): string {
    $t = preg_quote($this->type(), '/');
    return '/^\s*!end(?:' . $t . '|ex)\b\s*$/u';
}
```

> Изменение регулярных выражений является продвинутым: убедитесь, что вы не нарушаете способность парсера находить границы или захватывать `attrs`.

### `htmlTag(): string`
- Верните имя элемента-оболочки, например, `'section'`, `'aside'`, `'figure'`.
- Держите это в курсе **допустимое имя тега HTML**; Рендерер не проверяет имена элементов.

### `baseAttrs(): array`
- Предоставьте минимальные семантические значения по умолчанию, чаще всего базовые классы CSS.
- Порядок слияния атрибутов: `baseAttrs()` → встроенные атрибуты из Markdown → корректировки времени рендеринга.
- Классы бывают **Объединенные и дедуплицированные**; скалярии (например, `id`) перекрываются более поздними источниками.

### `allowNestingSame(): bool`
- Возвращать `false` чтобы запретить `!note` внутри `!note` (Парсер блоков будет рассматривать внутренние открытия как текст до внешнего закрытия).

### `attrsFilter(): ?callable`
- Подпись: `fn(array $attrs, array $meta): array`.
- `$meta` Содержит метаданные парсера из начальной строки, в том числе:
  - `openMatch` — полный массив совпадений регулярных выражений для `openRegex()` (например, именованные группы)
  - `attrStr` — подстрока необработанного атрибута после `!type`
- Подходит для **Белый список**, **отображение** семантических опций в классы или производных attr от **захваченные группы**.

Пример: карта `theme` в классы, удалите неизвестные ключи и используйте именованный захват `variant`От `openRegex()` При наличии:

```php
public function attrsFilter(): ?callable
{
    return function (array $attrs, array $meta): array {
        $out = [];
        $allowed = ['id', 'class', 'data-x', 'theme'];
        foreach ($attrs as $k => $v) if (in_array($k, $allowed, true)) $out[$k] = $v;

        // optional: derive from open regex capture
        $variant = $meta['openMatch']['variant'] ?? null; // requires a (?<variant>...) group in openRegex
        if ($variant) {
            $out['class'] = trim(($out['class'] ?? '') . ' variant-' . $variant);
        }

        if (isset($out['theme'])) {
            $map = ['info' => 'is-info', 'warning' => 'is-warn'];
            $cls = $map[$out['theme']] ?? null;
            unset($out['theme']);
            if ($cls) $out['class'] = trim(($out['class'] ?? '') . ' ' . $cls);
        }
        return $out;
    };
}
```

### `renderer(): ?callable`
- Подпись: `fn(string $innerHtml, array $attrs): string`.
- Используйте, когда по умолчанию `<htmlTag ...>innerHtml</htmlTag>` мало.
- **Экранирование атрибутов** вы делаете повторную инъекцию; использование `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.
- Пример: рендеринг как `<figure>` с необязательным атрибутом caption:

```php
public function renderer(): ?callable
{
    return function (string $innerHtml, array $attrs): string {
        $classes = htmlspecialchars($attrs['class'] ?? '', ENT_QUOTES, 'UTF-8');
        $caption = htmlspecialchars($attrs['caption'] ?? '', ENT_QUOTES, 'UTF-8');
        $fig = '<figure class="' . $classes . '">' . $innerHtml;
        if ($caption !== '') $fig .= '<figcaption>' . $caption . '</figcaption>';
        return $fig . '</figure>';
    };
}
```

---

## Жизненный цикл тега (end-to-end)
1. **Обнаружение открытия/закрытия**: `UniversalBlockParser` спички `openRegex()` / `closeRegex()` для тега `type()`.
2. **Внутренний разбор**: все, что находится между маркерами, разбирается как Markdown на дочерние узлы.
3. **Атрибуты**: открытая линия `attrs` сегмент анализируется по `Attrs`, нормализовано (пробелы/кавычки в Юникоде) и объединено с `baseAttrs()`.
4. **Фильтрация**:если `attrsFilter()` существует, он называется как `fn($attrs, $meta)` где `$meta` Включает `openMatch` и `attrStr`.
5. **Перевод**:если `renderer()` существует, это называется; в противном случае по умолчанию `<htmlTag ...attrs>innerHtml</htmlTag>` выбрасывается.

---

## Рекомендации
- Хранить `type()` короткие и устойчивые; Его изменение является критическим авторским изменением.
- Предпочитать `baseAttrs()` + авторские классы по жесткому кодированию тяжелого стиля.
- Использование `attrsFilter()`Кому**нормализовать** ввод автора; Избегайте этого в `renderer()`.
- Экранируйте все, что вы выводите в пользовательском файле `renderer()`.
- Напишите небольшое приспособление Markdown для каждого тега; Он также служит документацией.

### Антиузоры
- Основной `openRegex()` без сохранения `attrs` захват.
- Возврат недопустимых имен элементов из `htmlTag()`.
- Упаковка сложной логики в `renderer()` который принадлежит CSS или `attrsFilter()`.

---

## Шаблон минимального тега
Используйте это в качестве отправной точки для новых тегов.

```php
namespace App\Helpers\CustomTags;

use App\Helpers\CommonMark\BaseTag;

final class MyTag extends BaseTag
{
    public function type(): string { return 'mytag'; }

    public function baseAttrs(): array { return ['class' => 'mytag']; }

    // Optional normalization
    public function attrsFilter(): ?callable
    {
        return fn(array $a) => $a; // no-op by default
    }

    // Optional custom render
    // public function renderer(): ?callable
    // {
    //     return fn(string $html, array $attrs): string => $html;
    // }
}
```

---

## Чек-лист для тестирования
- Распознаются маркеры открытия/закрытия; Совпадения с однотипным поведением `allowNestingSame()`.
- Атрибуты: в кавычках/без кавычек, `.class`, `#id` разбираются и объединяются; Дедупликация классов.
- `attrsFilter()` Ведет себя должным образом в допустимых/недопустимых входных данных.
- Обёртка по умолчанию против пользовательской `renderer()` оба создают действительный, экранированный HTML.

---

## Вопросы и ответы
**В: Могу ли я поддерживать логические атрибуты (стиль флага)?**  
О: Предпочитайте явное `key="true"` или на карте через `attrsFilter()` (например, лечить наличие `flag` Ключ как `true`).

**В: Как указать несколько псевдонимов для одного тега?**  
A: Переопределение `openRegex()`/`closeRegex()` осторожно (см. пример), но сохраняйте `attrs` Захват и запуск якорей.

**В: Как предотвратить использование определенных атрибутов?**  
О: Реализация `attrsFilter()` и ключи из белого списка; Отбросьте все остальное.

