# Разработка Docara

Этот раздел предназначен для maintainers движка. Пользователю переносимого
сайта не нужно устанавливать Node.js или изменять тему.

:::ui.alert
{"type":"warning","variant":"outlined","title":"Разделяйте два контура","supporting-text":"Vite развивает ассеты темы, а переносимая пользовательская сборка остаётся PHP-only.","closable":false,"aria-label":"Разделяйте контур разработки и пользовательскую сборку"}

:::

## Руководства

- [Архитектура](/development/architecture/)
- [Vite и ассеты](/development/vite-assets/)
- [Framework-компоненты](/development/framework-components/)
- [Starter и template-зеркало](/development/starter-mirror/)
- [Тестирование](/development/testing/)

Изменения компонентов не должны обходить manifest, exact revision, props
validation и browser acceptance.
