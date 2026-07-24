# План повторного аудита

## Этап 1. Exact target и поверхность репозитория

- SHA, branch, status, история кандидата и closure;
- tracked/untracked, package metadata, CI, release и secret hygiene;
- поиск старого Jigsaw/Mix/runtime-кода и двусмысленных путей продукта.

## Этап 2. Чистая автоматическая приёмка

- exact `git archive`/checkout;
- clean Composer install;
- formatter/static checks и полный PHPUnit suite;
- проверка source install и Composer distribution install;
- init, `init --update`, build, verify, determinism и отрицательные сценарии;
- повторная сборка документации и проверка ссылок/ассетов.

## Этап 3. Browser QA

- локальный disposable runtime собранного сайта;
- desktop light/dark и mobile;
- меню, breadcrumbs, outline, поиск, подсветка запроса, modal, theme/settings,
  previous/next и отсутствие console errors;
- базовая семантика, keyboard path и адаптивность.

## Этап 4. Архитектура, документация и control plane

- соответствие «один CLI, один builder, один starter»;
- разделение content/data/composition/templates/assets;
- документация пользователя и разработчика против реального кода;
- canonical и установленный Docara skill;
- active workflow, project memory и process resolver.

## Этап 5. Verdict

- findings с severity и доказательствами;
- readiness matrix;
- `PASS`, `PASS_WITH_NOTES`, `CORRECTION_REQUIRED` или `BLOCKED`;
- отдельный bounded fix handoff при наличии дефектов.

