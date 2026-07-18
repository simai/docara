# Добавление Framework-компонента

Новый `:::ui.*` вызов нельзя добавить только изменением Markdown parser.

Минимальный принимаемый набор:

1. source-backed `larena.ui.smart_manifest.v1` с props и host renderer;
2. immutable provider revision и SHA-256 manifest;
3. запись в component-call schema;
4. полный asset dependency plan в Framework lock;
5. локальные projected bytes с проверяемыми хэшами, если они не загружаются из exact Core;
6. positive и negative props/constraint tests;
7. browser-проверка поведения, темы, адаптивности и доступности;
8. обновлённая страница компонента в этой документации.

Компонент из `runtime.components` не считается доступным автоматически. Tabs,
например, заблокирован до принятого slot и asset contract.

Для простых семантических конструкций сначала проверьте, достаточно ли
обычного Markdown и утилит Framework, как в `card`, `steps`, code и table.
