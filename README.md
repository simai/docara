# Docara Template Project (based on Jigsaw)

Документация и шаблон проекта на базе статического генератора Docara, построенного поверх [Jigsaw](https://jigsaw.tighten.com/).

## Установка

1. Клонируйте репозиторий сразу с сабмодулями (Docara ядро и папка `stubs/`):
   ```bash
   git clone --recurse-submodules git@github.com:simai/ui-doc-template.git
   cd ui-doc-template
   ```
   Если сабмодули не подтянулись, выполните:
   ```bash
   git submodule update --init --remote
   ```
2. Установите зависимости:
   ```bash
   yarn install
   composer install
   ```
3. Настройте переменные среды, создав `.env` в корне:
   ```text
   AZURE_KEY=<AZURE_KEY>
   AZURE_REGION=<AZURE_REGION>
   AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
   DOCS_DIR=docs
   ```
4. Запустите сборку в режиме разработки:
   ```bash
   yarn run watch
   ```
   Проект будет пересобираться при изменениях.

## Структура

- `source/` — шаблон сайта.
- `source/_core/` — сабмодуль с ядром Docara/Jigsaw.
- `stubs/` — сабмодуль с общими заготовками (stubs) фреймворка.
- `build_local/` — результат локальной сборки.
- `config.php` — конфигурация Jigsaw/Docara.

## Лицензия

MIT
