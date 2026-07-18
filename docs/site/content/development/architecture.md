# Архитектура

Основной пакет `simai/docara` владеет CLI, JSON schemas, разрешением
наследования, Markdown renderer, Framework adapter, starter и статической
сборкой.

Portable режим определяется наличием `docara.json` со schema
`docara.site.v1`. Он изолирован от legacy RuleLoader и `.settings.php` pipeline,
поэтому существующий Blade/Jigsaw-проект продолжает использовать старый путь.

Поток portable-сборки:

:::steps
1. Загрузить и проверить site, section, page и Framework lock.
2. Разрешить наследование и provenance каждой страницы.
3. Проверить Markdown-директивы и props по manifest.
4. Построить маршруты, меню и asset plan.
5. Записать HTML, ассеты и resolved page plans.

:::

Файлы являются источником истины; база данных, runtime CRUD, роли и workflow не
входят в standalone Docara.
