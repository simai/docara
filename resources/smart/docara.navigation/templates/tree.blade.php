<nav class="docara-navigation docara-navigation--tree" aria-label="{{ $view->label }}" data-docara-smart="docara.navigation" data-docara-view="tree" data-docara-maximum-depth="{{ $view->maximumDepth }}" data-docara-expand-label="{{ $view->expandLabel }}" data-docara-collapse-label="{{ $view->collapseLabel }}" data-docara-contains-current-label="{{ $view->containsCurrentLabel }}">
    <ul class="sf-menu flex flex-col">{!! $view->itemsHtml !!}</ul>
</nav>
