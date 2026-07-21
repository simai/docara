# Разработка Docara

Этот раздел предназначен для maintainers движка. Пользователю переносимого
сайта не нужно устанавливать Node.js или изменять тему.

:::ui.alert
{"type":"warning","variant":"outlined","title":"Разделяйте два контура","supporting-text":"Vite развивает ассеты темы, а переносимая пользовательская сборка остаётся PHP-only.","closable":false,"aria-label":"Разделяйте контур разработки и пользовательскую сборку"}

:::

## Руководства

- [Начало работы с repository](/development/getting-started/)
- [Архитектура](/development/architecture/)
- [Декларативный preview](/development/declarative-preview/)
- [Расширение Layout, Section, Block, View Tree и Smart](/development/composition-extensions/)
- [Развитие возможностей](/development/extensions/)
- [Vite и ассеты](/development/vite-assets/)
- [Framework-компоненты](/development/framework-components/)
- [Starter и template-зеркало](/development/starter-mirror/)
- [Тестирование](/development/testing/)

Начинайте с самого простого уровня расширения: native Markdown, затем
typed-компонент Docara, затем Smart-компонент из exact Framework lock. Если
исполняемого контракта ещё нет, добавьте requirement с явной безопасной
заменой. Изменения не должны обходить schema, manifest, exact revision,
negative tests и browser acceptance.
