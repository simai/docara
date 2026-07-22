# Разработка Docara

Этот раздел предназначен для maintainers движка. Пользователю переносимого
сайта не нужно устанавливать Node.js или изменять тему.

## Руководства

- [Начало работы с repository](/development/getting-started/)
- [Архитектура](/development/architecture/)
- [Расширение Layout, Section, Block, View Tree и Smart](/development/composition-extensions/)
- [Архитектура Smart-компонентов Docara](/development/smart-components/)
- [Развитие возможностей](/development/extensions/)
- [Framework-компоненты](/development/framework-components/)
- [Тестирование](/development/testing/)

Начинайте с самого простого уровня расширения: native Markdown, затем
typed-компонент Docara, затем Smart-компонент из exact Framework lock. Если
исполняемого контракта ещё нет, добавьте requirement с явной безопасной
заменой. Изменения не должны обходить schema, manifest, exact revision,
negative tests и browser acceptance.
