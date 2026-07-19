# Тестирование

Перед передачей изменения запустите проверки от узкой к полной.

## Portable-контур

```bash
vendor/bin/phpunit --do-not-cache-result \
  tests/Unit/PortableConfigurationTest.php \
  tests/Unit/PortableDocumentOutlineBuilderTest.php \
  tests/Unit/PortableMarkdownRendererTest.php \
  tests/Unit/PortableNavigationBuilderTest.php \
  tests/Unit/StaticBuildVerifierTest.php \
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

Для контекста чтения обязательны отрицательные проверки глубины 1/7,
повторяющихся HTML `id`, отсутствующего fragment и неправильной
percent-кодировки. Browser matrix должна подтверждать правое оглавление на
широком экране, native details на tablet/mobile, нативные ссылки, focus-visible
и складывание previous/next без горизонтального overflow.

Длинные тесты этого репозитория следует запускать последовательно: часть
legacy-suite использует общий временный fixture-каталог.
