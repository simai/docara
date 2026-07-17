<article class="container--main flex flex-col break-words p-2 md:p-4">
    @includeWhen(layout_enabled($page, 'main.tabs'), '_core._components.main.tabs')
    @includeWhen(layout_enabled($page, 'main.features'), '_core._components.main.features')
    @includeWhen(layout_enabled($page, 'main.content'), '_core._components.main.content')
</article>
