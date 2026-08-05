# Навигация

Navigation topology выводится из Markdown routes и section/page metadata. `navigation.hidden/order` управляет участием страницы; `header_navigation.items` задаёт проверенные label/href. Presentation выбирает один registered binding.

`docara.navigation` имеет `header`, `tree` и `compact` через один Gateway/composer path. Config не выбирает renderer/class/template и не подменяет binding-owned props.

После add/rename/delete route, изменения order/hidden или header items выполните full build: меню, search, breadcrumbs и previous/next — общие derived views.

