# Тестирование

Перед передачей изменения запустите проверки от узкой к полной.

## Portable-контур

```bash
vendor/bin/phpunit --do-not-cache-result \
  tests/Unit/PortableConfigurationTest.php \
  tests/Unit/PortableMarkdownRendererTest.php \
  tests/Unit/FrameworkComponentRuntimeTest.php \
  tests/PortableSiteBuilderTest.php \
  tests/PortableInitCommandTest.php
```

## Репозиторий

```bash
vendor/bin/phpunit --do-not-cache-result
vendor/bin/pint --test
composer validate --strict --no-check-publish
git diff --check
```

Дополнительно проверяйте JSON parsing, clean init/build, детерминированность,
исходные и rendered links, отсутствие moving references и browser matrix для
обоих preset, тем и основных viewport.

Длинные тесты этого репозитория следует запускать последовательно: часть
legacy-suite использует общий временный fixture-каталог.
