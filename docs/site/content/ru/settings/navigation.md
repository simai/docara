# Навигация

Navigation topology выводится из Markdown routes и section/page metadata. `navigation.hidden/order` управляет участием страницы; `header_navigation.items` задаёт проверенные label/href. Presentation выбирает один registered binding.

`docara.navigation` имеет `header`, `tree` и `compact` через один Gateway/composer path. Config не выбирает renderer/class/template и не подменяет binding-owned props.

`navigation.scope=site` показывает в боковой панели всё дерево сайта.
`navigation.scope=section` оставляет только дочерние страницы активного
раздела первого уровня. Верхнее меню, хлебные крошки, поиск и переходы между
страницами продолжают использовать полную каноническую топологию.

Пункт `header_navigation` отмечен на своей странице (`aria-current="page"`) и
на страницах своего раздела (`aria-current="true"`). Раздел вычисляется при
сборке по цепочке разделов страницы в навигации, а при её отсутствии — по
самому длинному совпадающему началу адреса. Пункт главной страницы отмечается
только на ней самой.

После add/rename/delete route, изменения order/hidden или header items выполните full build: меню, search, breadcrumbs и previous/next — общие derived views.
