---
extends: _core._layouts.documentation
section: content
title: 'Поставщик Azure и ограничение скорости'
description: 'Поставщик Azure и ограничение скорости'
---

# Azure Provider & Rate Limit (PHP)

В этой главе описываются следующие **точная реализация PHP** используется нашим переводчиком для вызова Azure Cognitive Services — Переводчика, пакетных запросов и регулирования по символам в минуту.

---

## Среда и конечная точка
- `.env` Переменные, загружаемые во время выполнения:
  - `AZURE_KEY`
  - `AZURE_REGION`
  - `AZURE_ENDPOINT` (По умолчанию: `https://api.cognitive.microsofttranslator.com`)
- Эффективная конечная точка для каждого запроса:

```php
$url = $this->endpoint . '/translate?api-version=3.0&to=' . $toLang;
// Note: we do not set &from=...; Azure will auto‑detect the source language.
```

Заголовки:
```php
$headers = [
    'Content-Type: application/json',
    'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
    'Ocp-Apim-Subscription-Region: ' . $this->region,
];
```

---

## HTTP-клиент (`curlRequest`)
```php
private function curlRequest(array $data, string $toLang): array
{
    $url = $this->endpoint . '/translate?api-version=3.0&to=' . $toLang;
    $headers = [
        'Content-Type: application/json',
        'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
        'Ocp-Apim-Subscription-Region: ' . $this->region,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Ошибка CURL: ' . curl_error($ch) . "\n";
    }

    return json_decode($response, true);
}
```
- **Формат корпуса**: массив объектов JSON с одним `Text` имущество, например, `[{"Text":"Hello"}, {"Text":"World"}]`.
- **Сопоставление ответов**: массив, выровненный по индексу с запросом.

---

## Запросы на строительство
Мы отправляем текст в трех контекстах, все через `curlRequest()`:

1) **Текстовые узлы Markdown** — `translateText()`
```php
private function translateText($textsToTranslate, $toLang): array
{
    $postData = array_map(fn($item) => ['Text' => $item['text']], $textsToTranslate);
    $translateData = $this->curlRequest($postData, $toLang);

    foreach ($textsToTranslate as $index => &$original) {
        $original['translated'] = $translateData[$index]['translations'][0]['text'] ?? $original['text'];
        $this->setCached($toLang, $original['translated'], $original['text']);
    }
    return $textsToTranslate;
}
```

2) **Лицевая сторона** — `translateFromMatter()` → `makeContent()`
```php
private function translateFromMatter(array $frontMatter, string $lang): array
{
    [$cachedIdx, $frontMatter] = $this->checkCached($frontMatter, $lang);
    $items = $keys = [];
    foreach ($frontMatter as $k => $v) {
        if (!in_array($k, $cachedIdx, true)
            && in_array($k, $this->config['frontMatter'], true)
            && is_string($v) && preg_match('/\p{L}/u', $v)) {
            $keys[]  = $k;
            $items[] = ['Text' => $v];
        }
    }
    return $this->makeContent($items, $frontMatter, $lang, $keys);
}
```

3) **Массивы языка/настроек PHP** — `translateLangFiles()` / `generateSettingsTranslate()` → `makeContent()`
```php
private function makeContent(array $items, $langContent, $lang, array $keys): mixed
{
    if (!$items) return $langContent;

    $translateData = $this->curlRequest($items, $lang);
    foreach ($translateData as $i => $entry) {
        $translated = $entry['translations'][0]['text'] ?? null;
        if ($translated !== null) {
            $this->setCached($lang, $translated, $langContent[$keys[$i]]);
            $langContent[$keys[$i]] = $translated;
        }
    }
    return $langContent;
}
```

---

## Пакетирование (`chunkTextArray`) и где он используется
Большие тексты Markdown разбиваются на **≈ 9 000 символов** Пакеты перед отправкой:

```php
private function chunkTextArray(array $items, int $maxChars = 9000): array
{
    $chunks = [];
    $currentChunk = [];
    $currentLength = 0;

    foreach ($items as $item) {
        $length = mb_strlen($item['text']);

        if ($length >= $maxChars) {
            if (!empty($currentChunk)) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
                $currentLength = 0;
            }
            $chunks[] = [$item];
            continue;
        }

        if ($currentLength + $length > $maxChars) {
            $chunks[] = $currentChunk;
            $currentChunk = [];
            $currentLength = 0;
        }

        $currentChunk[] = $item;
        $currentLength += $length;
    }

    if (!empty($currentChunk)) {
        $chunks[] = $currentChunk;
    }

    return $chunks;
}
```

**Использование (путь Markdown):**
```php
$chunks = $this->chunkTextArray($textsToTranslateArray);
$finalTranslated = [];
foreach ($chunks as $chunk) {
    $translatedChunk   = $this->translateText($chunk, $lang);
    $finalTranslated   = array_merge($finalTranslated, $translatedChunk);
    $chars = 0;
    foreach ($chunk as $c) $chars += mb_strlen($c['text']);
    $this->throttleByCharsPerMinute($chars);
}
```

---

## Регулирование по символам в минуту
Переводчик применяет **Бюджет CPM** с небольшим случайным дрожанием на партию.

```php
private function throttleByCharsPerMinute(int $chars): void
{
    $limit   = $this->config['chars_per_minute'] ?? 30000; // default
    $seconds = ($chars / max(1, $limit)) * 60.0;           // proportional delay
    $seconds += mt_rand(0, 200) / 1000.0;                  // +0..200 ms jitter
    usleep((int) round($seconds * 1_000_000));
}
```
- **Ключ конфигурации**: `chars_per_minute` (вставьте его в `translate.config.php` при необходимости).
- Задержка называется **после** для каждого запроса указывается размер этого пакета.

---

## Сопоставление и кэширование ответов
### Сопоставление по индексу
Все ответы на перевод отображаются обратно с помощью **Индекс массива** К оригинальным позициям:
```php
$original['translated'] = $translateData[$index]['translations'][0]['text'] ?? $original['text'];
```

### API кэша
Кэшируем по **стабильный хеш** нормализованной исходной строки:
```php
private function setCached(string $toLang, string $translatedText, string $originalText): void
{
    $key = $this->normalize($originalText); // SHA-1 over trimmed, whitespace-normalized text
    $this->prevTranslation[$toLang][$key] = $translatedText;
}

private function getCached(string $toLang, string $originalText): ?string
{
    $key = $this->normalize($originalText);
    return $this->prevTranslation[$toLang][$key] ?? null;
}
```
Файлы кэша сохраняются в разделе `<cache_dir>/translations/`:
- `translate_<lang>.json` — ключевые → переведенный текст
- `hash.json` — контрольные суммы для каждого файла для пропуска неизмененных файлов
- `.config.json` — названия локалей для Jigsaw's `beforeBuild`

---

## Обработка ошибок (текущее поведение)
- `curlRequest()` Отпечатки `Ошибка CURL: ...` когда cURL завершается сбоем и возвращает `json_decode($response, true)` (которые могут быть `null`).
- Вызывающие объекты используют резервные варианты с нулевым слиянием, например: `...['text'] ?? $original` — оригинальный текст сохраняется, если ответ отсутствует.

> Если вам нужно более строгое поведение, добавьте проверки статуса HTTP и повторные попытки (см. **Поставщик Azure** глава для рекомендаций).

---

## Сквозной пример (Markdown)
```php
// Gather text nodes with line ranges
$texts = [
  ['text' => 'Hello **world**', 'start' => 12, 'end' => 12],
  ['text' => 'Read more',        'start' => 18, 'end' => 18],
];

// Batch → request → throttle
$chunks = $this->chunkTextArray($texts);
$result = [];
foreach ($chunks as $chunk) {
    $tr = $this->translateText($chunk, 'ru');
    $result = array_merge($result, $tr);
    $chars = array_sum(array_map(fn($x) => mb_strlen($x['text']), $chunk));
    $this->throttleByCharsPerMinute($chars);
}

// Apply bottom‑up by lines (omitted here; see Markdown chapter)
```

---

## Примечания
- Мы полагаемся на **Автоопределение** для исходного языка; Если ваша базовая локаль фиксирована, вы можете расширить `curlRequest()` для добавления `&from=<base>` из конфигурации.
- Размер пакета и CPM являются значениями по умолчанию для реализации; Настройте их в своей конфигурации и логике оболочки по мере необходимости.

