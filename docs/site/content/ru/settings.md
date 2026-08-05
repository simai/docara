# Настройки

Настройки Docara — это проверяемые JSON-данные с тремя уровнями inheritance. Начните с задачи, меняйте только разрешённый project-owned файл, затем проверяйте effective plan и provenance.

## Карта

- [Уровни и inheritance](/ru/settings/levels-and-inheritance/)
- [Сайт](/ru/settings/site/), [раздел](/ru/settings/section/), [страница](/ru/settings/page/)
- [Локали и routing](/ru/settings/locales-and-routing/)
- [Branding и тема](/ru/settings/branding-and-theme/)
- [Layout и regions](/ru/settings/layout-and-regions/)
- [Навигация](/ru/settings/navigation/)
- [Поиск и чтение](/ru/settings/search-and-reading/)
- [Настройки читателя](/ru/settings/reader-preferences/)
- [Framework lock и providers](/ru/settings/framework-lock-and-providers/)
- [Безопасность](/ru/settings/security/)
- [Диагностика и provenance](/ru/settings/diagnostics-and-provenance/)

## Безопасный workflow

1. Найдите поле в schema-derived reference ниже.
2. Проверьте scope и effective provenance.
3. Измените `docara.json`, ближайший `section.json`, page sidecar/front matter или locale `lang.json` — только согласно schema.
4. Выполните `docara doctor`, выбранный `build --page` и static verify; при изменении глобальной структуры сделайте full build.

## Общие presentation-поля

Таблица строится из `presentation.schema.json`; prose выше лишь объясняет задачу.

:::schema_reference {schema=presentation scope=shared}
:::

