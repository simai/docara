---
extends: _core._layouts.documentation
section: content
title: 'Языковые пакеты Front Matter и PHP'
description: 'Языковые пакеты Front Matter и PHP'
---

# Языковые пакеты Front Matter и PHP 

В этой главе объясняется, как **Лицевая часть** и на основе PHP **Языковые пакеты** (`.lang.php`, `.settings.php`) переводятся и записываются обратно с использованием фактической реализации из `App\Helpers\Translate`.

---

## Где работает эта логика
- **Точка входа (на файл)**: `translateFiles()`
- **Помощники**: `frontMatterParser()`, `translateFromMatter()`, `translateLangFiles()`, `generateSettingsTranslate()`, `makeContent()`, `setByPath()`
- **ВВОД-ВЫВОД**: Symfony YAML для фронт-материи; `var_export()` для массивов PHP

---

## Перевод лицевой части
Передняя часть разбирается и транслируется перед телом Markdown.

### Разбор
```php
private function frontMatterParser($originalMarkdown): array
{
    $parser   = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
    $document = $parser->parse($originalMarkdown);
    $front    = $document->getFrontMatter();
    $content  = $document->getContent();
    return [$front, $content];
}
```

### Выбор ключей для перевода
Только ключи, настроенные в разделе `frontMatter` учитываются, и только если значение содержит буквы (`/\p{L}/u`). Кэшированные записи используются повторно.
```php
private function translateFromMatter(array $frontMatter, string $lang): array
{
    if (empty($this->config['frontMatter']) || !is_array($this->config['frontMatter'])) {
        return $frontMatter;
    }

    [$cachedKeys, $frontMatter] = $this->checkCached($frontMatter, $lang);
    $items = $keys = [];
    foreach ($frontMatter as $k => $v) {
        if (!in_array($k, $cachedKeys, true)
            && in_array($k, $this->config['frontMatter'], true)
            && is_string($v)
            && preg_match('/\p{L}/u', $v)) {
            $keys[]  = $k;
            $items[] = ['Text' => $v];
        }
    }
    return $this->makeContent($items, $frontMatter, $lang, $keys);
}
```

### Обратная запись (поток Markdown)
Внутри `translateFiles()` для`.md` Файлы:
```php
[$front, $original] = $this->frontMatterParser($content);
$frontTranslated    = $this->translateFromMatter($front, $lang);
$bodyTranslated     = $this->generateTranslateContent($original, $lang);
$yamlBlock          = "---\n" . Yaml::dump($frontTranslated) . "---\n\n";
$translated         = $yamlBlock + $bodyTranslated; // concatenation
```
> Дамп YAML сохраняет массивы/скаляры и обеспечивает корректность фронтального материала.

---

## Языковые пакеты PHP (`.lang.php`)
Файлы языковых пакетов возвращают ассоциативные массивы строк пользовательского интерфейса. Они есть **нагруженный**Перевод **за значение**, и написан обратно.

### Загрузка и кэширование
```php
$data = include $filePathName; // returns array
[$cachedKeys, $data] = $this->checkCached($data, $lang);
```

### Выбор значений
Только **струна** переводятся значения, содержащие буквы; клавиши в `$cachedKeys` хранятся как есть.
```php
$items = $keys = [];
foreach ($data as $k => $v) {
    if (!in_array($k, $cachedKeys, true) && is_string($v) && preg_match('/\p{L}/u', $v)) {
        $keys[]  = $k;
        $items[] = ['Text' => $v];
    }
}
```

### Перевод и написание текстов
`makeContent()` звонки Azure через `curlRequest()` и записывает результаты обратно, обновляя кэш. Окончательный файл PHP генерируется с помощью `var_export()`:
```php
$translated = $this->makeContent($items, $data, $lang, $keys);
$phpOut     = "<?php\nreturn " . var_export($translated, true) . ";\n";
file_put_contents($destPath, $phpOut);
```

---

## Пакеты настроек (`.settings.php`)
Файлы настроек могут иметь **вложенный** переводимые значения (например, `menu` массив). Мы собираем **Пути** на каждую переводимую строку и запишите их обратно с помощью `setByPath()`.

### Сбор кандидатов
```php
private function generateSettingsTranslate(array $settings, string $lang): array
{
    $paths = [];
    $texts = [];

    if (isset($settings['title']) && is_string($settings['title']) && preg_match('/\p{L}/u', $settings['title'])) {
        $paths[] = ['title'];
        $texts[] = $settings['title'];
    }

    if (!empty($settings['menu']) && is_array($settings['menu'])) {
        foreach ($settings['menu'] as $menuKey => $menuVal) {
            if (is_string($menuVal) && preg_match('/\p{L}/u', $menuVal)) {
                $paths[] = ['menu', $menuKey];
                $texts[] = $menuVal;
            }
        }
    }

    if (!$paths) return $settings;

    [$cachedIdx, $strings] = $this->checkCached($texts, $lang);

    // Build translation batch only for misses
    $toTranslate = [];
    $mapIdx      = [];
    foreach ($strings as $i => $text) {
        if (!in_array($i, $cachedIdx, true) && $text !== '') {
            $mapIdx[]     = $i;
            $toTranslate[] = ['Text' => $text];
        }
    }

    $decoded = $toTranslate ? $this->curlRequest($toTranslate, $lang) : [];

    // Stitch results back by original indexes
    foreach ($strings as $i => $text) {
        $translated = in_array($i, $cachedIdx, true)
            ? $text
            : ($decoded[array_search($i, $mapIdx, true)]['translations'][0]['text'] ?? $text);

        $this->setByPath($settings, $paths[$i], $translated, $lang);
    }

    return $settings;
}
```

### Запись вложенных значений
```php
private function setByPath(array &$arr, array $path, mixed $value, string $lang): void
{
    $ref =& $arr;
    foreach ($path as $idx => $key) {
        if ($idx === count($path) - 1) {
            $this->setCached($lang, $value, $ref[$key]); // update cache using original value
            $ref[$key] = $value;                         // write translation
            return;
        }
        if (!isset($ref[$key]) || !is_array($ref[$key])) $ref[$key] = [];
        $ref =& $ref[$key];
    }
}
```
Наконец, мы выводим полученный массив в `<?php return ...;` дорога `var_export()` точно так же, как для `.lang.php`.

---

## Пути назначения и структура
Путь назначения для переведенного файла определяется путем замены базового суффикса локали на целевой язык:
```php
$srcPath  = $file->getPathname();
$destPath = str_replace("_docs-{$this->config['target_lang']}", "_docs-{$lang}", $srcPath);
```
Директории создаются по запросу:
```php
$dir = dirname($destPath);
if (!is_dir($dir)) mkdir($dir, 0777, true);
```

---

## Поведение кэширования
Все три потока (передняя материя, `.lang.php`, `.settings.php`) используют тот же API кэша:
- **Манипуляция**: `normalize($text)` ⇒ SHA-1 над LF-нормализованной строкой, свернутой с пробелами.
- **Читать**: `[$cachedKeys, $data] = checkCached($data, $lang)` Помечает кэшированные позиции и встроенные кэшированные переводы.
- **Писать**: `setCached($lang, $translated, $original)` Обновляет кэш в памяти.
- **Упорствовать**: `saveCache()` Пишет `translate_<lang>.json`, `.config.json` (названия локалей через `Symfony\Component\Intl\Languages`), и `hash.json`.

---

## Инкрементальные обновления и защитные меры
- **Хеш для каждого файла**: `hashData[$lang][$filePath] = md5(file)` — Неизмененные файлы пропускаются при последующих прогонах.
- **Защита от дубликата языка**: если цель `lang` уже присутствует в Jigsaw `locales`, `translateFiles()` Бросает:
```php
if (in_array($lang, array_keys($this->usedLocales), true)) {
    throw new Exception('Language "' . $lang . '" is already translated.');
}
```

---

## Чек-лист для тестирования
- Клавиши передней части в `frontMatter` переводятся; другие остаются нетронутыми.
- `.lang.php`: переводятся только строковые значения с буквами; массивы/числа нетронуты.
- `.settings.php`: вложенные пути (например, `menu.*`) переведены; Нестроковые значения пропускаются.
- Попадание в кэш: повторные запуски избегают вызовов API; Выходы стабильны.
- Использование пути назначения `_docs-<lang>` Зеркальное отражение базового дерева.

---

## Советы
- Сохраняйте параметр `frontMatter` Список короткий и намеренный (названия, описания).
- Если вам нужно перевести дополнительные вложенные настройки (за пределами `title` и `menu`), расширить `generateSettingsTranslate()` с большим количеством сборщиков путей.
- Рассмотрите возможность упаковки `file_put_contents()` с атомарной записью (tmp файл → переименования) в CI.

