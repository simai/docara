# Tabs

Tabs временно недоступен в переносимом Markdown.

:::ui.alert
{"type":"warning","variant":"outlined","title":"Компонент не подключён","supporting-text":"Текущий exact Framework lock не содержит принятую manifest и asset/slot projection для Tabs.","closable":false,"aria-label":"Tabs временно недоступен"}

:::

## Почему нет временного синтаксиса

Вкладкам нужен подтверждённый контракт содержимого панелей, активного элемента,
клавиатурного управления, событий и ассетов. Придуманный shortcode создал бы
второй несовместимый API.

Страница будет дополнена только после того, как Framework workstream предоставит:

1. точный идентификатор и manifest;
2. slot/content contract;
3. immutable asset projection;
4. доступный keyboard и browser сценарий;
5. положительные и отрицательные тесты Docara.

До этого используйте обычные заголовки и последовательные разделы Markdown.
