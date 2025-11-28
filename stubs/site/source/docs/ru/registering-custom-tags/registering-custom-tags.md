---
extends: _core._layouts.documentation
section: content
title: 'Парсер (интеграция с пазлом)'
description: 'Парсер (интеграция с пазлом)'
---

# Парсер (интеграция с пазлом)

Этот компонент заменяет стандартный парсер front-matter/Markdown в Jigsaw и устанавливает наш **Пользовательские теги** расширение в League CommonMark.

---

## Местоположение и назначение
- **Класс:** `App\Helpers\Parser`
- **Расширяет:** `TightenCo\Jigsaw\Parsers\FrontMatterParser`
- **Цель:** Соберите среду CommonMark с помощью нашего пользовательского расширения и используйте его для преобразования Markdown в HTML во время сборки Jigsaw.

---

## Монтажная проводка
```php
public function __construct(FrontYamlParser $frontYaml, CustomTagRegistry $registry)
{
    parent::__construct($frontYaml);

    $env = new Environment();
    $env->addExtension(new CustomTagsExtension($registry));
    $env->addExtension(new CommonMarkCoreExtension());
    $env->addExtension(new FrontMatterExtension());
    $this->md = new MarkdownConverter($env);
}
```

### Зависимости
- **`FrontYamlParser`** — YAML-парсер Jigsaw Front Matter (используется родительским классом).
- **`CustomTagRegistry`** — Runtime registry нашего `CustomTagSpec`s, внедренный в пользовательское расширение.

### Установленные расширения
- **`CustomTagsExtension`** — Наше расширение, которое регистрирует `UniversalBlockParser` и `CustomTagRenderer`.
- **`CommonMarkCoreExtension`** — Стандартные блочные/инлайн функции CommonMark.
- **`FrontMatterExtension`** — Позволяет при необходимости распознавать огражденные передние блоки материала трубопроводом преобразователя.

> **Примечание о названии:** В других документах мы используем этот термин **CustomTagExtension** (единственное число). В коде этого проекта используется `CustomTagsExtension` (множественное число). Оба относятся к одной и той же роли расширения; Отдайте предпочтение имени класса, используемому в вашей кодовой базе.

---

## Преобразование Markdown
```php
/**
 * @throws CommonMarkException
 */
public function parseMarkdownWithoutFrontMatter($content): string
{
    return (string) $this->md->convert($content);
}
```
- Jigsaw обрабатывает извлечение передней материи в родительском классе; Этот метод преобразует **тело** Разметка в HTML с использованием нашей среды.
- Бросает `CommonMarkException` Если преобразование не удалось (поднимите пузырь для видимости сбоя сборки).

---

## Жизненный цикл в сборке Jigsaw
1. **Привязка bootstrap** Карты `TightenCo\Jigsaw\Parsers\FrontMatterParser` ➜ `App\Helpers\Parser`.
2. Jigsaw вызывает парсер для обработки каждого файла Markdown:
    - Разбор родительского класса **Лицевая часть**с `FrontYamlParser`.
    - `parseMarkdownWithoutFrontMatter()` конвертирует оставшийся Markdown с помощью нашего CommonMark `Environment`.
3. Внутри окружающей среды, **CustomTagsExtension** Устанавливает:
    - `UniversalBlockParser` — детектирует `!type` / `!endtype` Блоки и сборки `CustomTagNode`s.
    - `CustomTagRenderer` — рендерит узлы тегов с помощью рендереров для каждого тега или оболочек по умолчанию.

---

## Персонализация и опции
- **Конфигурация CommonMark:** Вы можете передать массив опций в `Environment` При необходимости:
  ```php
  $env = new Environment(['renderer' => ['inner_separator' => "\n"]]);
  ```
- **Дополнительные расширения:** Добавьте другие официальные расширения (таблицы, автоссылки и т.д.), позвонив по телефону `$env->addExtension(new ...)` Перед созданием `MarkdownConverter`.
- **Крючки событий:** Класс импортирует `DocumentParsedEvent`; вы можете зарегистрировать прослушиватели в среде, если требуется постобработка AST (например, генерация слагов, TOC). Пример:
  ```php
  $env->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $e) {
      // mutate $e->getDocument() as needed
  });
  ```

---

## Устранение неполадок
- **Пользовательские теги не распознаются:** обеспечивать `bootstrap.php` Связывает `FrontMatterParser::class`Кому`App\Helpers\Parser::class` и что `CustomTagsExtension` добавляется.
- **Рендерер для каждого тега не вызывается:** Убедитесь, что реестр содержит спецификации с помощью `renderer` Замыкания; Один и тот же экземпляр реестра должен быть передан расширению и рендеру.
- **Передняя часть просачивается в HTML:** подтвердите, что Jigsaw удаляет его (родительский класс обрабатывает это) и что ваш файл Markdown имеет правильные разделители передней части.
- **Сбой сборки с `CommonMarkException`:** проверьте содержимое на наличие недопустимого HTML/Markdown или временно удалите пользовательские расширения, чтобы изолировать причину.

---

## Минимальный тест
Создайте документ по приспособлениям:
```md
---
title: Parser test
---

!example class:"mb-2"
Hello **world**
!endexample
```
Ожидаемый вывод включает в себя элемент-обёртку с объединёнными классами и отрисованный жирным шрифтом текст.
