@php
    $locale = $page->locale();
    $topMenu = $page->configurator->getTopMenu($locale);
@endphp

<div class="sf-menu-container items-cross-center flex relative overflow-hidden">
    <button onclick="menuScroll(this, false)" class="sf-menu-scroll left absolute hidden" type="button">
        <i class="sf-icon">chevron_left</i>
    </button>
    <div id="top_menu" class="sf-menu truncate">
        @foreach($topMenu as $key => $item)
        <div class="sf-menu-item">
            <a href="{{$item['path']}}">{{$item['title']}}</a>
        </div>
        @endforeach
    </div>
    <button onclick="menuScroll(this, true)" class="sf-menu-scroll right absolute" type="button">
        <i class="sf-icon">chevron_right</i>
    </button>
</div>
