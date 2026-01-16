<header class="w-full flex z-8 sticky" role="banner">
    <div class="header--wrap flex items-cross-center container gap-1">
        @includeWhen($section['logo']['enabled'], '_core._components.header.logo')
        @if($page->category)
            @includeWhen($section['topMenu']['enabled'], '_core._components.header.top-menu')
        @endif
        <div class="flex flex-1 justify-end items-center text-right md:pl-10 gap-x-1">
            @includeWhen($section['search']['enabled'], '_core._components.header.search')
            @includeWhen($section['toolbar']['enabled'], '_core._components.header.tools',['section' => layout_section($page, 'toolbar.items')])
        </div>
    </div>
</header>
