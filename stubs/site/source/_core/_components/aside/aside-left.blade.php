<aside id="main_menu" class="sf-nav-menu w-full sf-nav-menu--left flex flex-col content-main-between p-1 sm:p-2 lg:p-y-3 gap-2 fixed lg:sticky overflow-auto  border-outline-variant lg:border-inline-end-1">
    @includeWhen($section['menu']['enabled'], '_core._components._nav.left-menu')
    @includeWhen($section['tools']['enabled'], '_core._components.aside-tools-left')
</aside>
