---
extends: _core._layouts.documentation
section: content
title: 'Перевод с учетом Markdown'
description: 'Перевод с учетом Markdown'
---

# Перевод с учетом Markdown 

В этой главе описываются следующие **Фактическая реализация PHP** который извлекает переводимый текст из Markdown, отправляет его в Azure и записывает переводы обратно **без нарушения разметки**.

---

## Где это происходит
- **Класс:** `App\Helpers\Translate`
- **Вход:** `generateTranslateContent(string $file, string $lang): string`
- **Предварительный шаг:** Передняя часть обрабатывается отдельно через `frontMatterParser()` и `translateFromMatter()`.

---

## Настройка парсера
Мы создаем CommonMark **Окружающая среда** с помощью нашего расширения Custom Tags и создайте **Анализатор MarkdownParser** (не конвертер):

```php
private function initParser(): void
{
    $environment = new Environment([]);
    $environment->addExtension(new CustomTagsExtension($this->registry));
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new FrontMatterExtension());
    $this->parser = new MarkdownParser($environment);
}
```

---

## Сбор текстовых узлов
Мы разбираем Markdown в AST и проходим по нему. Только **`Text`** узлы собираются; Блоки кода/встроенный код являются различными типами узлов и поэтому пропускаются неявно.

```php
$document  = $this->parser->parse($file);
$textNodes = [];
$walker = $document->walker();
while ($event = $walker->next()) {
    $node = $event->getNode();
    if ($event->isEntering() && ($node instanceof Text)) {
        $text = trim($node->getLiteral());
        if ($text !== '') {
            $textNodes[] = $node;
        }
    }
}
```

### Диапазоны строк текстового сегмента
Для каждого `Text` узел мы поднимаем до ближайшего **`AbstractBlock`** и используйте его начальные/конечные линии:

```php
private function getNodeLines(Node $node): array
{
    $parent = $node;
    $range  = ['start' => 0, 'end' => 0];
    while ($parent !== null && !$parent instanceof AbstractBlock) {
        $parent = $parent->parent();
    }
    if ($parent !== null) {
        if (method_exists($parent, 'getStartLine')) $range['start'] = $parent->getStartLine();
        if (method_exists($parent, 'getEndLine'))   $range['end']   = $parent->getEndLine();
    }
    return $range;
}
```

### Фильтрация неязыковых строк
Мы отправляем только те строки, которые содержат хотя бы одну **Буква Юникода**:

```php
if (!preg_match('/\p{L}/u', $text)) continue; // skip numbers, symbols, etc.
```

Затем мы строим список кандидатов:
```php
$textsToTranslateArray[] = [
  'text'  => $text,
  'start' => $lines['start'],
  'end'   => $lines['end'],
];
```

---

## Пропуск кэша
Перед тем, как обратиться к провайдеру, мы заменяем все строки, найденные в кэше, и отправляем только **Скучает по**.

```php
$flatten = array_map(fn($x) => $x['text'], $textsToTranslateArray);
[$cachedIdx, $flatten] = $this->checkCached($flatten, $lang);
$keys      = array_keys($textsToTranslateArray);
$keysAssoc = array_flip($cachedIdx);
$extracted = array_intersect_key($textsToTranslateArray, $keysAssoc);

// carry cached translations
foreach ($extracted as $k => $val) {
    $extracted[$k]['translated'] = $flatten[$k];
}

// keep only misses for API calls
$textsToTranslateArray = array_values(array_diff_key($textsToTranslateArray, $keysAssoc));
```

> **Ключи кэша** являются SHA-1 над нормализованной формой исходной строки (`normalize()` удаляет CRLF и сворачивает горизонтальное пустое пространство).

---

## Пакетирование и отправка
Оставшиеся элементы разбиваем на **≈ 9000-char** Чанки и вызовите Azure. После каждого запроса мы дросселируем **символов в минуту**.

```php
$chunks = $this->chunkTextArray($textsToTranslateArray);
$finalTranslated = [];
foreach ($chunks as $chunk) {
    $translatedChunk = $this->translateText($chunk, $lang); // uses curlRequest()
    $finalTranslated = array_merge($finalTranslated, $translatedChunk);

    $chars = 0; foreach ($chunk as $c) $chars += mb_strlen($c['text']);
    $this->throttleByCharsPerMinute($chars);
}
```

`translateText()` Карты Ответы назад **по индексу** и обновляет кэш:
```php
foreach ($textsToTranslate as $i => &$original) {
    $original['translated'] = $translateData[$i]['translations'][0]['text'] ?? $original['text'];
    $this->setCached($toLang, $original['translated'], $original['text']);
}
```

---

## Повторная сборка результатов в исходном порядке
Объединяем кэшированные хиты (`$extracted`) и свежие переводы в один массив, выровненный по свойству **Исходные индексы**:

```php
$finalBlock = $finalTranslated; // only API results
$i = 0;
foreach ($keys as $k) {
    if (array_key_exists($k, $extracted)) {
        $finalTranslated[$k] = $extracted[$k];
    } else {
        $finalTranslated[$k] = $finalBlock[$i++];
    }
}
```

---

## Замена снизу вверх по линейным диапазонам
Мы нормализуем EOL до `\n`, разбить на строки, затем внести изменения **снизу вверх**. Для каждого блока мы:
1) нарезать диапазон затронутых линий;
2) Найдите **последний** появление оригинального текста в этом фрагменте;
3) замените его на перевод;
4) Соедините измененные строки обратно в документ.

```php
$normalized = str_replace("\r\n", "\n", $file);
$lines = preg_split('/\R/u', $normalized);

foreach (array_reverse($finalTranslated) as $block) {
    $start = $block['start'];
    $end   = $block['end'];
    $slice = implode("\n", array_slice($lines, $start - 1, $end - $start + 1));

    $replaced = $this->replace_last_literal($slice, $block['text'], $block['translated']);
    $replacedLines = explode("\n", $replaced);

    array_splice($lines, $start - 1, $end - $start + 1, $replacedLines);
}

return implode("\n", $lines);
```

**Точный используемый помощник:**

```php
private function replace_last_literal(string $haystack, string $search, string $replace): string {
    $pos = mb_strrpos($haystack, $search);
    if ($pos === false) return $haystack;
    return markdown‑aware-translation.mdmb_substr($haystack, 0, $pos)
         . $replace
         . mb_substr($haystack, $pos + mb_strlen($search));
}
```

> Использование метода **последний** Вхождение уменьшает вероятность касания более ранних дубликатов в одном блоке при нескольких `Text` Узлы имеют идентичное содержимое.

---

## Что остается нетронутым
- **Блоки кода** (`FencedCode`, `IndentedCode`) и **Встроенный код** (`Code`).
- **URL-адреса** и места назначения ссылок/изображений; Переводятся только удобочитаемые надписи/альтернативный текст.
- **Пользовательские атрибуты тегов**; Обрабатывается только внутреннее текстовое содержимое.

---

## Крайние случаи и примечания
- **Начальные/конечные строки = 0**: Если предок узла не раскрывает информацию о строке, `start/end` Может быть `0`. Защита от негативных показателей при нарезке; На практике узлы блока CommonMark предоставляют номера строк для авторского контента.
- **Повторяющиеся фразы в одном диапазоне**: Мы ориентируемся на **последний** Матч в блоке. Если вам нужен точный таргетинг на несколько одинаковых фраз, добавьте смещения столбцов.
- **CRLF**: входные данные нормализуются в LF для обработки; Выходные данные объединяются с `\n`.

---

## Контрольный список безопасности
- [ ] Только сбор `Text` узлов (`instanceof Text`).
- [ ] Пропускать неязыковые строки (`/\p{L}/u`).
- [ ] Де-дупликация через кэш перед отправкой провайдеру.
- [ ] Партия по размеру и дроссельная заслонка по CPM.
- [ ] Заменить **снизу вверх** с использованием захваченных диапазонов линий.
- [ ] Сохранение кэшей после выполнения.

---

## Связанные пути к коду
- **Лицевая сторона**: `frontMatterParser()` + `translateFromMatter()`
- **PHP-массивы**: `translateLangFiles()`, `generateSettingsTranslate()`, `makeContent()`
- **Звонки Azure**: `curlRequest()`, `translateText()`

