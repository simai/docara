# Публикация и rollback

Публикуйте только каталог, который прошёл `verify-static`. Эта инструкция не
зависит от конкретного hosting provider: названия команд копирования и
переключения замените на принятые в вашей инфраструктуре.

## 1. Зафиксируйте public path

До сборки задайте `base_url` в `docara.json`:

```json
{
  "schema": "docara.site.v1",
  "preset": "docs",
  "framework_lock": "simai-framework.lock.json",
  "base_url": "/docs/"
}
```

Используйте `"/"` для корня домена и `"/docs/"` для подкаталога. Не исправляйте
ссылки вручную после сборки.

## 2. Соберите и проверьте

```bash
php vendor/bin/docara build production
php vendor/bin/docara verify-static build_production
```

При любом non-zero exit остановите публикацию.

## 3. Зафиксируйте digest

Сформируйте детерминированный manifest файлов проверенного каталога. Например,
в окружении с `shasum`:

```bash
(
  cd build_production
  find . -type f -exec shasum -a 256 {} \; | LC_ALL=C sort
) > build_production.manifest.sha256
shasum -a 256 build_production.manifest.sha256
```

Храните manifest рядом с deployment evidence, а не внутри публикуемого
каталога. На source и staging используйте одну реализацию digest-команды.

## 4. Скопируйте в новый staging

Не перезаписывайте активный каталог на месте. Создайте новый versioned
destination, например:

```text
releases/2026-07-19T120000Z/
```

Скопируйте туда только содержимое `build_production`. Credentials передавайте
через access/secret tooling инфраструктуры; не добавляйте их в Docara source,
shell history, Markdown или output.

После копирования сформируйте manifest staging и сравните его с source.
Несовпадение останавливает переключение.

## 5. Выполните smoke

До переключения, через staging URL или локальную привязку, проверьте:

1. root page возвращает успешный HTTP status;
2. вложенный pretty route открывается;
3. CSS, Framework assets, logo и favicon загружаются без 404;
4. search dialog находит известную страницу;
5. ссылка на конкретный heading fragment прокручивает к нему;
6. light, dark и system themes читаемы;
7. нет redirect за пределы настроенного `base_url`.

Smoke проверяет именно staging bytes с совпавшим digest.

## 6. Переключите атомарно

Если hosting поддерживает release directories, переключите один pointer или
symlink на новый versioned каталог. Если нет, используйте provider-native
atomic deploy. Не очищайте предыдущую успешную версию до окончания smoke
после переключения.

Сразу повторите короткий smoke через публичный URL.

## 7. Rollback

При 404, пропавшем asset/search, неверном prefix или другом regression:

1. остановите дальнейшие изменения;
2. верните pointer на предыдущий проверенный release;
3. повторите root/nested/asset/search smoke;
4. зафиксируйте failed digest и симптом;
5. исправьте source и начните build → verify → staging заново.

Rollback не должен пересобирать старую версию: он возвращает уже принятый
immutable каталог.

## Что эта процедура не доказывает

Локальный build/verify и staged smoke не заменяют security, availability,
license, access и change-window gates конкретного production. Legacy
Node/Yarn workflow также не является доказательством готовности portable
deployment.
