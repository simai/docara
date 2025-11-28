---
extends: _core._layouts.documentation
section: content
title: 'Структура проекта'
description: 'Структура проекта'
---

# Структура проекта

На этой странице представлены файлы и каталоги, составляющие пользовательскую систему тегов Markdown, объясняется, что делает каждая часть и как они сочетаются друг с другом.

---

## Дерево каталогов

Укоренен в `source/_core/helpers`:

<div class="files">
    <div class="folder folder--open">source
        <div class="folder folder--open">_core
            <div class="folder folder--open">helpers
              <div class="folder folder--open">CommonMark
  <div class="file">Attrs.php</div>
  <div class="file">BaseTag.php</div>
  <div class="file">CustomTagAdapter.php</div>
  <div class="file">CustomTagNode.php</div>
  <div class="file">CustomTagRegistry.php</div>
  <div class="file">CustomTagRender.php</div>
  <div class="file">CustomTagExtension.php</div>
  <div class="file">CustomTagSpec.php</div>
  <div class="file">TagRegistry.php</div>
  <div class="file">UniversalBlockParser.php</div>
  <div class="file">UniversalInlineParser.php</div>
</div>
 <div class="folder folder--open">CustomTags
  <div class="file">ExampleTag.php</div>
</div>
 <div class="folder folder--open">Interface
  <div class="file">CustomTagInterface.php</div>
</div>
                <div class="file">Parser.php</div>
            </div>
        </div>
    </div>
</div>

---

## Пространства имен и автозагрузка
- Все классы проживают под `App\Helpers\…`.
- Убедитесь, что карты Composer `"App\\": "source/_core"` в `composer.json`.
- После добавления/удаления классов запустите `composer dump-autoload`.

---

## Высокоуровневая архитектура

```
Markdown source
   │
   ▼
CustomTagExtension (installs)
   ├─ UniversalBlockParser ──► CustomTagNode (AST)
   └─ UniversalInlineParser (reserved for inline forms)

CustomTagRegistry ──► CustomTagSpec (per-tag rules)

CustomTagRender ──► HTML (uses BaseTag::htmlTag/renderer)

Attrs ──► parse/merge attributes
```

---

## Компоненты и обязанности

### API разработки
- **Интерфейс/CustomTagInterface.php**
    - Официальный контракт на тег: `type()`, `openRegex()`, `closeRegex()`, `htmlTag()`, `baseAttrs()`, `allowNestingSame()`необязательный `attrsFilter()` и `renderer()`.
- **commonmark/basetag.pf**
    - Реализация интерфейса по умолчанию с разумным поведением.
    - Расширьте эту возможность для новых тегов, а не реализуйте интерфейс с нуля.
- **Пользовательские теги/** (например, `ExampleTag.php`)
    - Ваши классы тегов находятся здесь. Каждый из них возвращает уникальный `type()` и может переопределять значения по умолчанию.

### Регистрация и раскрытие информации
- **CommonMark/TagRegistry.php**
    - Вспомогатель/фабрика, которая принимает массив экземпляров тегов и создает реестр только для чтения.
- **CommonMark/CustomTagRegistry.php**
    - Привязанный к контейнеру сервис, используемый во время сборки; собирает экземпляры тегов из конфигурации и предоставляет их слою CommonMark.
- **Конфигурация** (за пределами этого дерева)
    - `config.php` имеет `tags` массив с короткими именами классов для регистрации.

### Слой разбора
- **CommonMark/CustomTagSpec.php**
    - Скомпилированная, неизменяемая спецификация для каждого тега: тип, открытие/закрытие регулярного выражения, правила вложенности, тег-оболочка, базовые атрибуты.
- **CommonMark/UniversalBlockParser.php**
    - Блочный парсер на основе строк, который распознает `!type` / `!endtype` использование спецификаций из реестра, захват внутреннего Markdown и сборка `CustomTagNode`.
- **CommonMark/UniversalInlineParser.php**
    - Зарезервировано для встроенных шаблонов/сокращений; Сохранено для симметрии и использования в будущем.
- **CommonMark/CustomTagNode.php**
    - Узел AST, содержащий тип тега, объединенные атрибуты (необработанные) и дочерние узлы (разобранный внутренний Markdown).
- **CommonMark/CustomTagAdapter.php**
    - Мост, который регистрирует спецификации, парсеры и рендереры в среде League CommonMark.
- **CommonMark/CustomTagExtension.php**
    - Точка входа расширения CommonMark; Устанавливает адаптер в окружающую среду.

### Слой рендеринга
- **CommonMark/CustomTagRender.php**
    - Конвейер рендеринга для `CustomTagNode`:
        1. Синтаксический анализ/нормализация встроенных атрибутов с помощью **Attrs** и объединить с помощью `baseВлечения()`.
        2. Применение для каждого тега `attrsFilter()` если присутствует.
        3. Если тег предоставляет `renderer()`, назовите его с помощью `(innerHtml, attrs)`.
        4. В противном случае выдавать `<htmlTag ...attrs>innerHtml</htmlTag>`.

### Коммунальные услуги
- **CommonMark/Attrs.php**
    - Надежный парсинг атрибутов для открытой строки:
        - Пары ключ-значение: `key="value"`, `key:'value'`, токены без кавычек.
        - Сокращения: `.class` (добавить), `#id` (набор).
        - Пробелы в Юникоде/смарт-кавычки нормализованы; Классы объединяются и дедуплицируются.
    - Слияние набора атрибутов с дедупликацией классов.

### Интеграция с парсером
- **Parser.php**
    - Конкретная замена для лобзика `FrontMatterParser`.
    - Сборка среды CommonMark и установка `CustomTagExtension` Так что теги работают во время `build`/`serve`.

---

## Взаимодействие с файлами (жизненный цикл)
1. `Parser` собирает окружение CommonMark ➜ устанавливает `CustomTagExtension`.
2. `CustomTagExtension` Использует `CustomTagAdapter` Чтобы зарегистрироваться:
    - `UniversalBlockParser`, `UniversalInlineParser`и рендерер для `CustomTagNode`.
3. `CustomTagRegistry` припасы `CustomTagSpec` экземпляры, производные от зарегистрированных классов тегов.
4. Разбор:
    - `UniversalBlockParser` Совпадения открытых/закрытых линий, конструктов `CustomTagNode` с необработанными аттрами и дочерними узлами.
5. Перевод:
    - `CustomTagRender` объединяет атрибуты (`Attrs`) и рендерится через оболочку или по тегу `renderer()`.

---

## Куда добавить вещи
- **Новый тег** ➜ `CustomTags/YourTag.php` (расширить `BaseTag`), добавить в `config('tags')`.
- **Новое поведение при синтаксическом анализе** ➜ `UniversalBlockParser` / `UniversalInlineParser`.
- **Пользовательская логика рендеринга для определенного тега** ➜ Переопределение `renderer()` в классе тегов.
- **Глобальные правила атрибутов** ➜ Расширяем логику в `Attrs`.

---

## Конвенций
- Один класс на файл; class basename совпадает с filename.
- `type()` должен быть глобально уникальным для всех тегов.
- Хранить `baseAttrs()` минимальный/семантический; позвольте авторам добавить представление в Markdown.
- Избегайте внедрения HTML: экранирование значений в custom `renderer()` Реализации.

---

## Устранение неполадок указателей
- **Тег не распознан**:Проверка `config('tags')`, namespace и run `composer dump-autoload`.
- **Отсутствуют атрибуты**: Подтвердите, что они находятся в списке **Открытая линия**; проверка `Attrs` нормализация кавычек/пробелов.
- **Неправильная обертка**:Проверять `htmlTag()` перекрыть; при использовании `renderer()`, помните, что он обходит обёртку по умолчанию.
- **Проблемы с вложенностью**:Регулировать `allowNestingSame()` в классе tag.



