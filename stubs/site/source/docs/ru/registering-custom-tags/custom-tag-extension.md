---
extends: _core._layouts.documentation
section: content
title: 'CustomTagExtension и реестры'
description: 'CustomTagExtension и реестры'
---

# CustomTagExtension и реестры

На этой странице объясняется, как создаются пользовательские теги **подключено к League CommonMark** и как **Реестров** Предоставьте спецификации для конвейера синтаксического анализа/рендеринга.

---

## Обзор компонентов
- **CustomTagExtension** — Расширение CommonMark, которое устанавливает наши парсеры и рендереры.
- **CustomTagRegistry (Реестр пользовательских тегов)** — Runtime registry `CustomTagSpec` объекты (по одному на тип тега), используемые парсерами/рендерерами.
- **TagRegistry** — Фабрика/мост, которая принимает экземпляры класса тегов и создает `CustomРеестр тегов` (через адаптер).
- **Пользовательский тегSpec** — Неизменяемый объект данных, описывающий тег: регулярные выражения, обёртка, дефолты, хуки.

---

## CustomTagExtension
**Роль:** Зарегистрируйте наш парсер запуска блоков и рендерер нод в среде CommonMark.

**Типовая форма:**
```php
namespace App\Helpers\CommonMark;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

final class CustomTagExtension implements ExtensionInterface
{
    public function __construct(private CustomTagRegistry $registry) {}

    public function register(EnvironmentBuilderInterface $env): void
    {
        // Block start parser which recognizes !type / !endtype
        $env->addBlockStartParser(new UniversalBlockParser($this->registry), 100);

        // Renderer for the AST node
        $env->addRenderer(CustomTagNode::class, new CustomTagRenderer($this->registry), 0);

        // (Optional) Inline parser if/when implemented
        // $env->addInlineParser(new UniversalInlineParser($this->registry));
    }
}
```

**Примечания**
- Тем **приоритет** (`100`) гарантирует, что наш парсер запуска блока запустится достаточно рано, прежде чем конфликтующие парсеры.
- Расширение обычно устанавливается вашим проектом `Parser` во время настройки среды.

---

## CustomTagRegistry (Реестр пользовательских тегов)
**Роль:** Быстрый поиск спецификаций **по типу** и перечислите все спецификации для сканирования.

**Ожидаемый API (иллюстративный):**
```php
final class CustomTagRegistry
{
    /** @var array<string, CustomTagSpec> */
    private array $byType;

    /** @param iterable<CustomTagSpec> $specs */
    public function __construct(iterable $specs)
    {
        $map = [];
        foreach ($specs as $spec) $map[$spec->type] = $spec;
        $this->byType = $map;
    }

    /** @return iterable<CustomTagSpec> */
    public function getSpecs(): iterable { return $this->byType; }

    public function get(string $type): ?CustomTagSpec { return $this->byType[$type] ?? null; }
}
```

**Где он построен:**В `bootstrap.php`дорога `TagRegistry::register([...$instances])`, затем связывают в контейнер и вводят в `CustomTagExtension`/`Parser`.

---

## TagRegistry (заводской)
**Роль:** Преобразовать тег **Классы** (расширяя `BaseTag`) в **Реестр времени выполнения** из спецификаций.

**Типовая форма:**
```php
final class TagRegistry
{
    /**
     * @param CustomTagInterface[] $tags
     */
    public static function register(array $tags): CustomTagRegistry
    {
        $registry = new CustomTagRegistry();
        $seen = [];

        foreach ($tags as $tag) {
            if (!$tag instanceof CustomTagInterface) {
                throw new \InvalidArgumentException('All items must implement CustomTagInterface');
            }

            $type = $tag->type();
            if (isset($seen[$type])) {
                throw new \RuntimeException("Duplicate custom tag type '{$type}'");
            }
            $seen[$type] = true;

            $registry->register(CustomTagAdapter::toSpec($tag));
        }

        return $registry;
    }
}
```

**Почему именно фабрика?** Централизует преобразование (`tag` → `spec`), проверяет типы входных данных, **предотвращает дублирование `type()` Столкновений**и добавочно заполняет реестр среды выполнения.

---

## CustomTagSpec (контракт данных)
**Роль:** Неизменяемое описание тега, используемого парсером и рендерером.

**Поля (используемые во всей кодовой базе):**
- `string $type` — Идентификатор тега, используемый в `!type` / `!endtype`.
- `string $openRegex` — Якорное регулярное выражение для начальной строки; должен предоставлять именованный захват `(?<attrs>...)` если поддерживаются встроенные атрибуты.
- `?string $closeRegex` — Закрепленное регулярное выражение для линии закрытия; `null` средство **однострочный** (закрывается сразу).
- `string $htmlTag` — Элемент-оболочка по умолчанию (например, `div`, `section`).
- `array $baseAttrs` — Стандартные атрибуты объединены со встроенными; `class` Значения конкатенировать/дедуплицировать.
- `bool $allowNestingSame` — Может ли быть вложен один и тот же тип тега.
- `?callable $attrsFilter` — Подпись `fn(array $attrs, array $meta): array`; запускается раньше для нормализации/внесения в белый список.
- `?callable $renderer` — Подпись `fn(CustomTagNode $node, ChildNodeRendererInterface $children): mixed`.

**Создано:** `CustomTagAdapter::toSpec($tag)`.

---

## Сквозная проводка
1. **Конфигурация** перечисляет краткие имена классов тегов в разделе `tags`.
2. **bootstrap.php** материализует экземпляры тегов, вызовы `TagRegistry::register($instances)`и связывает `CustomTagRegistry`.
3. **Синтаксический анализатор** собирает среду CommonMark и устанавливает **CustomTagExtension** с привязанным реестром.
4. **Универсальный парсер блоков** Использует `getSpecs()` чтобы попробовать открытие/закрытие по линии; При совпадении он создает команду `CustomTagNode` и подает заявку на раннюю стадию `attrsFilter`.
5. **CustomTagRenderer (Пользовательский тегРендерер)** Рендерит узлы с помощью тега для каждого `renderer` или обёртка по умолчанию.

---

## Устранение неполадок
- **Продление не применяется**: верифицируйте свой проект `Parser` Устанавливает `CustomTagExtension` и которые предоставляет DI `CustomTagRegistry`.
- **Теги не распознаны**:обеспечивать `TagRegistry::register()` получает экземпляры ваших классов тегов и которые `openRegex()` не пустой (переходник выкинет иначе).
- **Рендерер для каждого тега не вызывается**:подтверждать `renderer` устанавливается на кнопку **спекуляция** (т.е. возвращен из тега), и что реестр, используемый рендерером, такой же, как и блочный парсер.

---

## Чек-лист для тестирования
- Окружение содержит наш парсер запуска блока и рендерер нод.
- `CustomTagRegistry::getSpecs()` Возвращает ожидаемый набор типов.
- Поиск спецификаций по типам работает во время рендеринга (`CustomTagRenderer` путь).
- Однострочные теги ведут себя корректно при `closeRegex` есть `null`.
- Однотипное правило вложенности, применяемое блочным парсером с помощью `allowNestingSame`.

