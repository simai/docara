# Starter и template-зеркало

Canonical portable starter хранится в `stubs/portable` основного репозитория.
`docara init --portable` копирует его в пустой каталог, а `--update` сохраняет
каждый существующий JSON и Markdown.

Отдельный `docara-template` не должен содержать самостоятельно редактируемую
копию содержания, конфигурации или build scripts. Если он нужен для кнопки
template/Codespaces, он создаётся автоматически из canonical starter:

- `stubs/portable`;
- сгенерированных README, `.gitignore`, manifest и workflow синхронизации.

Exporter требует точный commit SHA с единственным SemVer release tag, проверяет
связь tag → SHA и записывает в manifest SHA и точную версию пакета:

```bash
TEMP_ROOT="$(php -r 'echo realpath(sys_get_temp_dir());')"
MIRROR_DIR="$(mktemp -d "${TEMP_ROOT}/docara-template.XXXXXX")"
php scripts/export-template.php "$MIRROR_DIR" 0f10afde92b93dd39703823ab22a2920b450a15b
php scripts/verify-template.php "$MIRROR_DIR" 0f10afde92b93dd39703823ab22a2920b450a15b
```

`realpath(sys_get_temp_dir())` здесь намерен: на macOS короткий `/tmp` является
символической ссылкой на `/private/tmp`, а exporter запрещает любой symlink в
цепочке назначения, чтобы исключить запись за пределы проверенного каталога.

Node/Mix glue, `.settings.php`, `config.php`, `source/_core`, `vendor/`,
`composer.json`, `composer.lock` и старые sample pages не входят в portable
mirror. Composer создаёт свои файлы при установке Docara в пользовательский
проект; они не принадлежат starter.

Сгенерированный workflow принимает полный SHA и точный release tag, проверяет
их соответствие, затем через Composer metadata убеждается, что опубликованная
версия разрешается обратно в этот SHA, проверяет зеркало и
создаёт pull request только при фактическом drift. Сам exporter/verify не
требует Composer или `vendor`; Composer вызывается только отдельным read-only
publication gate. Все внешние Actions закреплены полными SHA, а право `contents: write`
доступно только отдельному job. Этот job не исполняет скачанный код: он
сверяет каждый файл и source path с Git-объектами точной revision, после чего
публикует стабильную для этой revision ветку и один pull request для review.
Повторный dispatch той же revision не создаёт дубликат, если содержимое уже
совпадает. Прямой push в default branch запрещён.
Ручной и repository-dispatch запуск одинаково требуют полный SHA и release tag.
README зеркала устанавливает `simai/docara:<exact-version>` из manifest;
commit без release tag и плавающая ссылка запрещены.
