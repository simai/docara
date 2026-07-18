# Vite и ассеты темы

Vite — единственный frontend tool текущего canonical theme scaffold. Он
компилирует JavaScript и SCSS из `stubs/site/source/_core/_assets`, создаёт
manifest в `source/assets/build` и запускает Docara build для выбранного mode.

Vite 7.3.6 требует Node.js `^20.19.0 || >=22.12.0`; Docara проверяет версию
Node и Yarn до изменения scaffold. Основные maintainer-команды сгенерированного
legacy theme-проекта:

```bash
YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc install --frozen-lockfile --production=false --non-interactive
YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc dev
YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc watch
YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc prod
```

`packageManager`, `engines.node` и `yarn.lock` являются частью starter-контракта. Init
отказывается от установки без lockfile или при одновременном наличии
`yarn.lock` и `package-lock.json`. Старый npm-only проект также останавливается
до создания `.env`, очистки build-каталогов и копирования scaffold: переход на
Yarn должен быть отдельной проверяемой миграцией, а не побочным эффектом
`init --update`.
Команда `yarn --version` перед init должна вывести ровно `1.22.22`; способ
установки этой версии выбирает окружение разработчика. В CI используется
явный `npx --yes yarn@1.22.22`. Docara запускает Yarn с отключённым
`yarn-path` и default rc.

`yarn dev` наблюдает за Markdown, Blade, конфигурацией и frontend-ассетами.
`yarn watch` — только production-like watcher frontend и статических ассетов;
изменение содержания само по себе его не запускает.

При `init --update` Docara владеет `dependencies`, `devDependencies`,
`packageManager`, `engines.node`, lockfile и стандартными scripts. Отсутствующий
`engines.node` добавляется, точное canonical значение сохраняется, а отличный
или расширенный `engines` отклоняется. Имя, версия, описательные
поля, `config` и дополнительные scripts проекта сохраняются.
Проект с дополнительными npm-пакетами, `workspaces`, `resolutions` или
`overrides` останавливается до любых изменений: сначала нужен явный extension
contract, иначе frozen lock уже не доказывает воспроизводимость.
То же относится к `devEngines`, `installConfig`, `flat`,
`peerDependencies`, optional/bundled dependencies, OS/CPU constraints и
файлам `.yarnrc`, `.yarnrc.yml`, `.yarn/`, `.yarnclean`, `.npmrc`.
Автоматические lifecycle scripts (`preinstall`, `postinstall`, `prepare`, а
также `pre*`/`post*` для стандартных команд Docara) тоже отклоняются до
установки. Обычные явные scripts наподобие `validate:graph` сохраняются.

`DOCARA_SKIP_FRONTEND_INSTALL=true` пропускает только запуск Yarn. Package
contract, lockfile и scaffold всё равно проверяются и обновляются. Успешный
init с этим флагом не доказывает frontend readiness и не является завершённым
CI/deploy path: следом обязателен exact frozen install через
`YARN_IGNORE_PATH=1 npx --yes yarn@1.22.22 --no-default-rc install --frozen-lockfile --production=false --non-interactive`.

Если первый `php` в `PATH` отличается от PHP, которым установлен Composer,
передайте Vite точный бинарник:

```bash
DOCARA_PHP_BINARY=/absolute/path/to/php yarn prod
```

Это не пользовательский portable quick start. Сайт с `docara.json` использует
готовый Framework Core и проверяемую локальную Smart projection, поэтому
автору не нужен Vite.

Не добавляйте рядом esbuild, Mix или второй watcher: это создаст конкурирующие
пути копирования ассетов и запуска статической сборки.
