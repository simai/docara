# Framework lock

`simai-framework.lock.json` связывает exact Core revision, Smart revision,
framework registry, разрешённые manifests и хэши локальной asset projection.

Текущий проверяемый контур использует:

- Core commit `7e836d8a9414d5da553fb1ab0404721e5b48769a`;
- Smart commit `dd786bbae98391fb21df9b4e1e6cd402ead0614c`;
- pair ID `sf-v5.3.2-7e836d8a-dd786bba`;
- manifests только для `ui.alert` и `ui.button`.

`main`, `master`, `latest` и другие moving references запрещены. Локальные
Smart bytes проверяются по SHA-256 до публикации в `_docara/framework`.

Наличие компонента в общей секции `runtime.components` ещё не разрешает его в
Markdown. Нужны manifest, компонентный contract и asset projection Docara.

Текущий exact Core использует браузерное хранилище для своего кэша. Если
браузер полностью запрещает `localStorage`, Docara до запуска темы и Core
подключает ограниченный совместимый кэш только в памяти страницы. Он не
считается постоянным хранилищем, исчезает после перезагрузки и не подменяет
работающий нативный `Storage`. Это защитная граница интеграции с зафиксированным
Core, а не новая версия или отдельная реализация Simai Framework.

Этот lock доказывает ограниченный проверяемый набор, а не готовность всей
экосистемы или разрешение на публичное распространение всех upstream-файлов.
