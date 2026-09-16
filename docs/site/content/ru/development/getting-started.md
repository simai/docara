# Начало разработки

Этот путь предназначен для maintainer, который меняет движок Docara. Для
обычного сайта используйте [быстрый старт](/start/).

## Подготовьте рабочую копию

1. Получите repository и перейдите в его корень.
2. Убедитесь, что используется PHP 8.2 или новее.
3. Установите exact зависимости из lockfile.

```bash
composer install
composer validate --strict --no-check-publish
```

Для проверок сборки нужен Node.js и точная generated-поставка Framework.
Настройте `DOCARA_SIMAI_UI_ROOT` по [быстрому старту](/start/), используя
`resources/contracts/composition/runtime-lock.json` этого checkout.
Если Node.js отсутствует в `PATH`, задайте `DOCARA_NODE_BINARY`.
Отдельная установка npm-зависимостей сайта не требуется.

## Запустите узкие проверки

Выберите тесты владельца изменяемого контракта. Для portable Markdown,
компонентов и статического результата:

```bash
vendor/bin/phpunit --do-not-cache-result \
  tests/Unit/PortableMarkdownRendererTest.php \
  tests/Unit/EffectiveComponentCatalogTest.php \
  tests/Unit/StaticBuildVerifierTest.php \
  tests/PortableSiteBuilderTest.php
```

После узких тестов выполните полный последовательный набор:

```bash
vendor/bin/phpunit --do-not-cache-result
vendor/bin/pint --test
git diff --check
```

## Соберите документацию

```bash
cd docs/site
php ../../docara build production
php ../../docara verify-static build_production
php ../../docara serve production --host=localhost --port=8000 --no-build
```

Откройте сайт по HTTP, проверьте изменённую задачу с клавиатуры, на 1440, 768 и
390 px, в светлой и тёмной темах. Завершите preview через `Ctrl+C`.

## Подготовьте изменение к приёмке

- не смешивайте исправление с release, publish или Framework owner write;
- добавьте positive и negative tests до повышения lifecycle;
- не меняйте canonical JSON ради подгонки generated HTML;
- проверьте две чистые сборки на детерминированность;
- передавайте exact commit SHA и границы заявленного результата.

Зелёный тест сам по себе не означает production, public-release или readiness
всех компонентов.
