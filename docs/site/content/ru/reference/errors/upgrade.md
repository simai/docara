---
description: Коды ошибок команды docara upgrade — выбор версии, проверка кандидата, применение плана и откат — с причиной и способом исправления.
---

# Ошибки обновления

Эти коды выдаёт команда `docara upgrade`. Пока проверка не прошла, файлы
проекта не меняются, а при ошибке на этапе применения Docara возвращает
прежние `composer.lock`, `vendor/` и сборку. Порядок обновления описан на
странице [Обновление Docara](/build/update/).

## Выбор версии

| Код | Причина и что сделать |
| --- | --- |
| `UPGRADE_ACTION_CONFLICT` | Указано больше одного действия: `--check`, `--dry-run`, `--apply`, `--rollback`. **Что сделать:** Оставьте одно действие. |
| `UPGRADE_TARGET_VERSION_INVALID` | `--to` — не точная стабильная версия `X.Y.Z`, а ветка, диапазон, `latest` или предварительная версия. **Что сделать:** Укажите точную версию, например `--to=3.7.0`. |
| `UPGRADE_CURRENT_VERSION_INVALID` | Установленная версия Docara в `composer.lock` не распознана. **Что сделать:** Выполните `composer install` и повторите. |
| `MAJOR_UPGRADE_REQUIRED` | Запрошена другая основная версия, например 3.x для проекта на 2.x. Автоматически она не ставится. **Что сделать:** Перейдите вручную по инструкции [Переход с 2.x на 3.0](/ru/build/update/#переход-с-2-x-на-3-0). |
| `UPGRADE_DOWNGRADE_FORBIDDEN` | Запрошена версия старше установленной. **Что сделать:** Для возврата используйте `docara upgrade --rollback=latest`. |
| `UPGRADE_TARGET_RESOLUTION_MISMATCH` | Composer выбрал не ту версию, что запрошена, обычно из-за ограничения в `composer.json`. **Что сделать:** Проверьте ограничение `simai/docara` в `composer.json`. |
| `UPGRADE_ENGINE_ADOPTION_REQUIRED` | Проект создан до появления файла владения движком (`.docara/engine`). **Что сделать:** Один раз выполните `docara update --dry-run --adopt --json`, проверьте план, затем `docara update --apply --json` и повторите `upgrade`. |

## Проект и Composer

| Код | Причина и что сделать |
| --- | --- |
| `UPGRADE_PROJECT_UNSAFE`, `UPGRADE_PROJECT_INVALID` | Каталог проекта не найден, является ссылкой или в нём нет обязательного файла проекта, например `docara.json`. **Что сделать:** Запустите команду из каталога сайта. |
| `PROJECT_LOCAL_RUNTIME_REQUIRED` | В проекте нет своих `composer.json`, `composer.lock` и `vendor/`: Docara установлена не в проект. **Что сделать:** Выполните `composer require simai/docara:^3.0` в каталоге сайта и повторите. Старый движок не трогается. |
| `UPGRADE_PROJECT_PATH_UNSAFE` | Каталог `.docara/upgrades` — символическая ссылка. **Что сделать:** Удалите ссылку. |
| `UPGRADE_COMPOSER_MISSING` | Команда `composer` не найдена. **Что сделать:** Установите Composer или добавьте его в `PATH`. |
| `UPGRADE_COMPOSER_INVALID`, `UPGRADE_COMPOSER_LOCK_INVALID` | `composer.json` или `composer.lock` повреждён. **Что сделать:** Восстановите файл и выполните `composer install`. |
| `UPGRADE_COMPOSER_FAILED` | Composer не смог поставить новую версию: нет сети, конфликт зависимостей. Текст ошибки Composer есть в сообщении. **Что сделать:** Исправьте причину из вывода Composer и повторите. |

## Проверка кандидата

Перед заменой Docara собирает и проверяет сайт на копии проекта.

| Код | Причина и что сделать |
| --- | --- |
| `UPGRADE_CANDIDATE_CHECK_FAILED` | На новой версии не прошла проверка: `doctor`, `validate`, сборка или `verify-static`. Подробности есть в сообщении. **Что сделать:** Исправьте найденную ошибку в проекте или останьтесь на текущей версии. |
| `UPGRADE_CANDIDATE_INCOMPLETE`, `UPGRADE_CANDIDATE_INVALID` | Копия проекта с новой версией собрана не полностью. **Что сделать:** Повторите `docara upgrade`. |
| `UPGRADE_FAILED` | Общая ошибка обновления без отдельного кода. **Что сделать:** Прочитайте сообщение; файлы проекта при этом восстановлены. |

## План и применение

| Код | Причина и что сделать |
| --- | --- |
| `UPGRADE_PLAN_ID_INVALID` | `--apply` получил не тот идентификатор, что выдал `--dry-run`. **Что сделать:** Скопируйте `plan_sha256` из результата `--dry-run`. |
| `UPGRADE_PLAN_NOT_FOUND`, `UPGRADE_PLAN_INVALID`, `UPGRADE_PLAN_HASH_MISMATCH` | План не найден или изменён. **Что сделать:** Запустите `--dry-run` заново. |
| `UPGRADE_PLAN_STALE` | Файлы проекта изменились после `--dry-run`. **Что сделать:** Запустите `--dry-run` заново и примените новый план. |
| `UPGRADE_INCOMPLETE` | Предыдущее обновление прервано. **Что сделать:** Выполните `docara upgrade --rollback=latest` и повторите. |
| `UPGRADE_PROMOTION_FAILED`, `UPGRADE_COMPENSATION_FAILED` | Не удалось заменить или вернуть `vendor/`: нет прав или место на диске закончилось. **Что сделать:** Проверьте права на каталог проекта и место на диске. Затем выполните `composer install`. |
| `UPGRADE_STATE_COLLISION`, `UPGRADE_STATE_COPY_FAILED`, `UPGRADE_STATE_WRITE_FAILED`, `UPGRADE_STATE_CLEANUP_FAILED`, `UPGRADE_JOURNAL_INVALID` | Не удалось записать или прочитать служебные файлы в `.docara/upgrades/`. **Что сделать:** Проверьте права на каталог `.docara/`. |

## Откат

| Код | Причина и что сделать |
| --- | --- |
| `UPGRADE_ROLLBACK_ID_INVALID` | Неверный идентификатор отката. **Что сделать:** Укажите `latest` или `rollback_id` из результата `--apply`. |
| `UPGRADE_ROLLBACK_UNAVAILABLE` | Нет обновления, которое можно откатить. **Что сделать:** Откат не нужен: проект уже на прежней версии. |
| `UPGRADE_ROLLBACK_STALE` | После обновления `vendor/` изменился, и откат перезаписал бы эти изменения. **Что сделать:** Верните версию вручную: `composer require simai/docara:<версия>`. |
