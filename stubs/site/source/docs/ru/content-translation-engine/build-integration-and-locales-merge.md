---
extends: _core._layouts.documentation
section: content
title: 'Интеграция сборки и слияние локалей'
description: 'Интеграция сборки и слияние локалей'
---

# Интеграция сборки и слияние локалей

В этой главе объясняется, как переводятся локали **внедрено в сборку Jigsaw** и как директория кэша связывает все воедино.

---

## Где происходит слияние
В `bootstrap.php`, во время Jigsaw's `beforeBuild` Хук мы считываем сгенерированный файл локалей и сливаем его с конфигурацией времени выполнения:

```php
$events->beforeBuild(function ($jigsaw) use ($container) {
    $locales   = $jigsaw->getConfig('locales');
    $tempConfig = __DIR__ . '/temp/translations/.config.json'; // must match cache_dir

    if (is_file($tempConfig)) {
        $allLocales = [];
        $tempConfigJson = json_decode(file_get_contents($tempConfig), true) ?: [];

        foreach ($locales as $key => $locale) {
            $allLocales[$key] = $locale; // project locales
        }
        foreach ($tempConfigJson as $key => $value) {
            $allLocales[$key] = $value;  // generated locales override or add
        }

        $jigsaw->setConfig('locales', $allLocales);
    }
});
```

**Важный:** Путь должен совпадать с конфигурацией вашего переводчика:
- `main` (корень проекта) + `cache_dir` + `translations/.config.json`
- С конфигурацией по умолчанию, то есть `temp/translations/.config.json`.

---

## Что пишет переводчик
После завершения прогона перевода `saveCache()` записывает три файла в папку `<cache_dir>/translations/`:

```
translate_<lang>.json  # cache: normalized-string-hash → translated text
hash.json              # per-file MD5 to skip unchanged files next run
.config.json           # locales map consumed by Jigsaw beforeBuild
```

### `.config.json` формат
Переводчик пишет **Плоская карта**: `code → display name`использование `Symfony\Component\Intl\Languages::getName()`
```json
{
  "en": "English",
  "ru": "Русский"
}
```
> Если в проекте требуется более насыщенная форма (например, `{ "en": { "name": "English" } }`), настройте либо слияние bootstrap, либо `saveCache()` соответственно. По умолчанию простая строка работает как отображаемое имя локали.

---

## Комплексный поток сборки
1. **Перевести**
   ```bash
   composer translate
   ```
   – Заполнение/обновления `temp/translations/*.json` и `.config.json` с любыми новыми языками.

2. **Создание сайта**
   ```bash
   vendor/bin/jigsaw build
   ```
   – `beforeBuild` Сливается `.config.json` в `config('locales')` Таким образом, становятся доступными новые языки **в той же сборке**.

3. **Обслуживание (dev)**
   ```bash
   vendor/bin/jigsaw serve
   ```
   –Перезапуск `serve` После перевода бегите, чтобы выбрать только что добавленные локали.

---

## Пример CI/CD
```yaml
steps:
  - run: composer install --no-interaction --prefer-dist
  - run: php bin/translate           # or composer translate
  - run: vendor/bin/jigsaw build
  - persist_to_workspace: public/    # or upload artifacts
```

**Примечания**
- Обеспечивать `.env` присутствует в CI с `AZURE_KEY`, `AZURE_REGION`, `AZURE_ENDPOINT`.
- Убедитесь, что рабочий каталог является корневым каталогом проекта (поэтому `bin/translate` можно найти `vendor/autoload.php`).

---

## Обеспечение согласованности контуров
- `translate.config.php` → `'cache_dir' => 'temp/'`
- `bootstrap.php` → `__DIR__ . '/temp/translations/.config.json'`

Если вы измените `cache_dir`, соответствующим образом обновите путь начальной загрузки. Несоответствие приведет к появлению новых локалей **не** объединяются.

---

## Семантика конфликта
- Проект слияния копий `locales` **первый**, то оверлеи, сгенерированные из `.config.json`.
- Если код локали существует в обоих случаях, то метод **Сгенерированное значение выигрывает** для этого ключа. Это сделано для того, чтобы обеспечить возможность именования во время выполнения, полученные от транслятора.

---

## Очистка и повторный запуск
- Чтобы принудительно выполнить полное перестроение переводов, удалите директорию кэша:
  ```bash
  rm -rf temp/translations
  ```
- Следующий `composer translate` воссоздаст кэши и `.config.json` с нуля.

---

## Устранение неполадок
- **Новый язык не отображается**
    - Проверять `temp/translations/.config.json` существует и является действительным JSON.
    - Убедитесь, что путь начальной загрузки совпадает `cache_dir`.
    - Перезапуск `jigsaw serve`.

- **Метка локали выглядит неправильно**
    - Переводчик записывает отображаемые имена через `Languages::getName($code)` (тогда `mb_ucfirst`). Если вам нужны индивидуальные этикетки, постобработка `.config.json` или переопределить во время слияния.

- **Сбой сборки из-за отсутствия автозагрузки**
    - Обеспечивать `bin/translate` Требует `vendor/autoload.php` через **Абсолютный путь** и `chdir($root)` перед запуском.

- **Устаревший кэш**
    - Если содержимое изменилось, а переводы — нет, удалите файл кэша для каждого языка `translate_<lang>.json` или весь `temp/translations`папка.

---

## Контрольный перечень
- [ ] `cache_dir` в `translate.config.php` совпадает с траекторией в `bootstrap.php`.
- [ ] `.env` с учетными данными Azure.
- [ ] `composer translate` Прогоны перед `jigsaw build` в CI.
- [ ] Перезагрузка `serve` после добавления языков.
- [ ] `.config.json` содержит все ожидаемые коды локали.

