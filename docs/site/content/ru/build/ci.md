---
description: Как собирать и проверять сайт Docara при каждом изменении: команды для любого CI и готовый процесс GitHub Actions с GitHub Pages.
tags: [ci, github-actions, github-pages, автоматическая-сборка, проверка]
---

# Сборка в CI

Автоматическая сборка ловит ошибку до того, как она попадёт на сайт: каждое
изменение собирается с нуля, проверяется и только потом публикуется. Docara
останавливается с ненулевым кодом выхода при любой ошибке, поэтому CI сам
отметит неудачную проверку.

## Что нужно на сервере сборки

- PHP 8.2 или новее с расширениями `ctype`, `dom`, `intl`, `json`, `libxml`
  и `mbstring`;
- Composer;
- Node.js 20 или новее.

Сам готовый сайт — статические файлы: ни PHP, ни Node.js на хостинге не нужны.

## Команды для любого CI

Эти шаги подходят для любой системы: GitHub Actions, GitLab CI, Jenkins или
скрипта на своём сервере. Запускайте их в корне проекта — там, где лежат
`docara.json` и `composer.json`.

```bash
composer install --no-interaction --prefer-dist
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
php vendor/bin/docara qa report --env=production --min-score=80
php vendor/simai/docara/scripts/verify-page-weight.php build_production
```

| Шаг | Что проверяет |
| --- | --- |
| `composer install` | Ставит ту же версию Docara, что записана в `composer.lock` |
| `build production` | Собирает сайт; останавливается на ошибке в страницах и настройках |
| `verify-static` | Проверяет готовый каталог: ссылки, файлы, поиск, служебные данные |
| `qa report --min-score=80` | Останавливает проверку, если оценка качества документации ниже 80 из 100 |
| `verify-page-weight.php` | Останавливает проверку, если страница или картинка тяжелее порога |

Последние два шага необязательны. Порог качества удобнее записать в
`docara.json` полем `quality.min_score` — описание на странице
[«Качество документации»](/ru/authoring/documentation-quality/). Пороги веса
задаются файлом `--config=page-weight.json` — см. раздел
[«Вес страниц»](/ru/build/verify/#вес-страниц).

Публикуйте только каталог `build_production` и только после того, как все
шаги прошли.

## GitHub Actions и GitHub Pages

Этот процесс собирает сайт при каждом изменении ветки `main` и публикует его
на GitHub Pages. По той же схеме собирается сайт документации самой Docara.

Перед первым запуском включите публикацию: **Settings → Pages → Source:
GitHub Actions**. Затем сохраните файл `.github/workflows/docs.yml`:

```yaml {title=".github/workflows/docs.yml"}
name: Documentation

on:
  push:
    branches: [main]
  pull_request:
  workflow_dispatch:

permissions:
  contents: read

concurrency:
  group: pages
  cancel-in-progress: false

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@11d5960a326750d5838078e36cf38b85af677262
      - id: pages
        uses: actions/configure-pages@45bfe0192ca1faeb007ade9deae92b16b8254a0d # v6.0.0
      - uses: shivammathur/setup-php@f3e473d116dcccaddc5834248c87452386958240
        with:
          php-version: "8.3"
          coverage: none
          tools: composer
      - run: composer install --no-interaction --prefer-dist
      - uses: actions/setup-node@49933ea5288caeca8642d1e84afbd3f7d6820020 # v4.4.0
        with:
          node-version: "24"
      - name: Set the site address
        env:
          BASE_PATH: ${{ steps.pages.outputs.base_path }}
          SITE_URL: ${{ steps.pages.outputs.base_url }}
        run: |
          jq --arg base "${BASE_PATH%/}/" --arg site "${SITE_URL%/}/" \
            '.base_url = $base | .site_url = $site' docara.json > docara.json.ci
          mv docara.json.ci docara.json
      - run: php vendor/bin/docara build production
      - run: php vendor/bin/docara verify-static build_production
      - uses: actions/upload-pages-artifact@fc324d3547104276b827a68afc52ff2a11cc49c9 # v5.0.0
        with:
          path: build_production

  deploy:
    if: github.event_name != 'pull_request'
    needs: build
    runs-on: ubuntu-latest
    permissions:
      pages: write
      id-token: write
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@368f82528645a54fb793d4d04e342629a3f51346 # v5.0.1
```

Что здесь важно:

- **Адрес сайта.** Сайт проекта на GitHub Pages открывается по адресу
  `https://<владелец>.github.io/<репозиторий>/`, то есть в подкаталоге. Шаг
  «Set the site address» берёт адрес у GitHub и записывает его в `base_url`
  и `site_url`; `docara.json` в репозитории остаётся с `"base_url": "/"` для
  локальной работы. Со своим доменом GitHub сам вернёт пустой путь, и сайт
  соберётся от корня. Подробнее — на странице [«Адрес сайта»](/ru/settings/site-address/).
- **Проверка запросов на слияние.** На `pull_request` сайт собирается и
  проверяется, но не публикуется: за это отвечает условие `if` у `deploy`.
- **Закреплённые версии действий.** Действия указаны по точному коммиту, а
  не по подвижной метке: так процесс не изменится сам, если у действия выйдет
  новая версия. Обновляйте их осознанно.

## Если проверка остановилась

Docara печатает код ошибки в квадратных скобках. Найдите его в
[справочнике кодов ошибок](/ru/reference/errors/): там причина и что
исправить. Чтобы воспроизвести ошибку у себя, выполните те же команды
локально, начиная с `composer install`.

Частые причины в CI:

| Симптом | Причина и что сделать |
| --- | --- |
| Сборка останавливается, а локально проходит | Локально стоит другая версия Docara. **Что сделать:** Сохраните `composer.lock` в репозитории и выполните `composer install`, а не `composer update`. |
| Проверки прошли, но на опубликованном сайте нет стилей и ссылки ведут не туда | `base_url` не совпадает с адресом, по которому открывается сайт: `verify-static` проверяет сборку относительно её собственного `base_url`. **Что сделать:** Проверьте шаг с адресом сайта. |
| `QUALITY_SCORE_BELOW_MINIMUM` | Оценка качества ниже порога. **Что сделать:** Запустите `qa report` локально и исправьте страницы из списка «исправить в первую очередь». |

## См. также

- [Проверка сборки](/ru/build/verify/)
- [Размещение на хостинге](/ru/build/hosting/)
- [Публикация и откат](/ru/build/publish/)
- [Адрес сайта](/ru/settings/site-address/)
