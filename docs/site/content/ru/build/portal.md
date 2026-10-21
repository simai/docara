---
description: Сборка сайта Docara для одного пути портала и упаковка его в архив релиза с контрольной суммой и квитанцией.
---

# Сборка для портала

Портал собирает несколько продуктов в один сайт, например `https://portal.example.com/`:
каждый продукт занимает свой путь (`/ru/acme/docs/`), а портал владеет
главной страницей, языковым префиксом и файлами хостинга. Секция `portal` в
`docara.json` собирает сайт для такого пути, а команда `portal-artifact`
упаковывает результат в три файла релиза, которые портал скачивает, проверяет и
раскладывает по своим путям.

## Настройка

Портальная сборка — это отдельная конфигурация сайта: один язык, путь портала
в `base_url`, полный адрес в `site_url` и секция `portal`.

```json
{
  "default_locale": "ru",
  "locales": {
    "ru": {
      "label": "Русский",
      "direction": "ltr",
      "content_root": "content/ru",
      "public_prefix": "",
      "fallbacks": []
    }
  },
  "locale_routing": {
    "strategy": "default_unprefixed",
    "root": "default_locale",
    "detect_browser_language": false,
    "legacy_unprefixed_redirects": false
  },
  "base_url": "/ru/acme/docs/",
  "site_url": "https://portal.example.com/ru/acme/docs/",
  "portal": {
    "product": "acme-docs",
    "home_url": "https://portal.example.com/ru/",
    "home_label": "portal.example.com"
  }
}
```

| Ключ | Обязателен | Значение |
|---|---|---|
| `portal.product` | да | Идентификатор продукта в реестре портала: строчные латинские буквы, цифры и дефис, первая — буква. Входит в имена файлов релиза. |
| `portal.home_url` | да | Абсолютный `https`-адрес главной страницы портала. Шапка каждой страницы ссылается на него. |
| `portal.home_label` | нет | Текст ссылки на главную портала, до 80 символов. По умолчанию — имя хоста из `home_url`. |

Сборка остановится с понятной ошибкой, если:

- не задан `site_url` (`PORTAL_SITE_URL_REQUIRED`);
- языков больше одного (`PORTAL_SINGLE_LOCALE_REQUIRED`): каждый язык
  собирается отдельно под свой путь портала;
- `locale_routing.strategy` не `default_unprefixed`
  (`PORTAL_UNPREFIXED_ROUTING_REQUIRED`): языковой префикс уже входит в путь
  портала, поэтому внутри сайта страницы лежат без префикса.

Сайт без секции `portal` собирается как обычно.

## Что меняется в сборке

- `canonical` каждой страницы — полный адрес на портале: `site_url` плюс
  маршрут страницы.
- Шапка начинается со ссылки на `portal.home_url` и помечена
  `data-portal-shell="header"`, подвал — `data-portal-shell="footer"`. По этим
  атрибутам портал проверяет переход на общую оболочку.
- `llms.txt` остаётся в сборке и ссылается на страницы и
  `_docara/page-metadata.json` по адресам портала. `sitemap.xml` и `robots.txt`
  не создаются: их пишет портал.
- Если контент или ассеты публикуют `CNAME`, `.nojekyll`, `robots.txt`,
  `sitemap.xml` или `404.html` в корне сборки, сборка останавливается с
  `PORTAL_FILE_COLLISION`.
- Страница `404.html` ([Страница 404](/build/hosting/#страница-404)) не
  создаётся: ответ на отсутствующий адрес даёт портал. `"not_found":
  {"enabled": true}` в портальной сборке останавливает её с
  `PORTAL_NOT_FOUND_PAGE_FORBIDDEN`, а `404.md` публикуется как обычная
  страница `404/`.
- Ссылки контента, написанного для сайта с языковым префиксом, ведут на
  страницы без префикса: `[Старт](/ru/start/)` в сборке для
  `/ru/acme/docs/` становится `/ru/acme/docs/start/`. Ссылки от корня сайта
  (`/start/`) работают так же.
- Поисковый индекс, ассеты, медиа и кадры примеров лежат внутри сборки и
  адресуются под `base_url`.

Собирайте и проверяйте портальную сборку как любую другую:

```bash
php vendor/bin/docara build portal
php vendor/bin/docara verify-static build_portal
```

`verify-static` для портальной сборки проверяет `llms.txt` и отсутствие файлов
портала; ошибки выводятся с `reference: "@discovery-files"`.

## Упаковка релиза

```text
php vendor/bin/docara portal-artifact [environment] --release=TAG --repository=OWNER/NAME --commit=SHA [--product-version=VERSION] [--output=DIR]
```

Команда читает `build_<environment>` (по умолчанию `build_production`) и
секцию `portal` из `docara.json` и пишет в `--output` (по умолчанию
`build_<environment>.portal/`):

| Файл | Содержимое |
|---|---|
| `<product>-<version>-portal.tar.gz` | Сборка; `index.html` лежит в корне архива. |
| `<product>-<version>-portal.tar.gz.sha256` | SHA-256 архива в формате `sha256sum`. |
| `<product>-<version>-portal-receipt.json` | Квитанция `simai.io.portal-artifact` версии 1. |

`<version>` — значение `--product-version` или `--release` без начальной `v`.

```bash
php vendor/bin/docara portal-artifact portal \
  --release=v1.2.0 \
  --repository=acme/docs \
  --commit="$(git rev-parse HEAD)" \
  --output=portal-out
```

Квитанция:

```json
{
    "schema": "simai.io.portal-artifact",
    "version": 1,
    "product": "acme-docs",
    "release": "v1.2.0",
    "repository": "acme/docs",
    "commit": "<40 hex>",
    "locale": "ru",
    "base_path": "/ru/acme/docs/",
    "archive": "acme-docs-1.2.0-portal.tar.gz",
    "sha256": "<64 hex>",
    "files": 1706,
    "framework_pair": "ui-<12 hex>",
    "docara_version": "3.6.0"
}
```

`files` — число файлов в архиве, `framework_pair` — пара SIMAI Framework,
которую загружают страницы, `docara_version` — версия Docara, упаковавшая
сборку.

Перед записью команда проверяет сборку и останавливается с
`PORTAL_PAGE_INVALID`, `PORTAL_FILE_COLLISION`, `PORTAL_INDEX_MISSING` или
`PORTAL_BUILD_LINK`, если:

- в сборке есть символическая ссылка или файл портала;
- в корне нет `index.html`;
- у страницы нет `<html lang>` языка сборки, непустого `<title>`, описания или
  ссылки на главную портала;
- `canonical` страницы лежит вне `site_url`;
- страницы загружают разные пары Framework.

Страница без `description` во front matter получает описание из первого абзаца.
Если абзаца нет, добавьте `description` во front matter. Redirect-страницы
описания не требуют: их `canonical` ведёт на цель внутри `base_url`.

## Воспроизводимость архива

Архив собирается так, чтобы одинаковая сборка давала одинаковые байты:

- записи tar отсортированы по пути, каталоги идут перед своим содержимым;
- у каждой записи время изменения 0, владелец `root` (0:0), права `0644` для
  файлов и `0755` для каталогов;
- заголовок gzip фиксирован: без имени файла, со временем 0 и с
  неопределённой операционной системой.

Сжатые байты зависят от реализации zlib, поэтому сравнивайте архивы,
собранные на одной платформе. Собирайте канонический архив в одном месте,
например в CI своего проекта; портал закрепляет его SHA-256 из квитанции.

Сама документация Docara с версии 3.5.0 публикуется только на GitHub Pages:
workflow выпуска Docara больше не собирает и не прикладывает к релизу её
портальный архив. Секция `portal` и команда `portal-artifact` остаются
доступны проектам.

Проверка после скачивания:

```bash
sha256sum -c acme-docs-1.2.0-portal.tar.gz.sha256
jq -r .sha256 acme-docs-1.2.0-portal-receipt.json
```

Оба значения должны совпасть.
