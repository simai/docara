<nav aria-label="Documentation" data-docara-smart="docara.navigation" data-docara-maximum-depth="{{ $view->maximumDepth }}">
    <ol class="flex flex-col gap-1 list-none p-0 m-0">
        @foreach ($view->items as $item)
            <li
                class="{!! $item->indentationClass !!}"
                data-docara-navigation-key="{!! $item->key !!}"
                data-docara-navigation-depth="{{ $item->depth }}"
                data-docara-active-ancestor="{{ $item->activeAncestor ? 'true' : 'false' }}"
                data-docara-current-section="{{ $item->currentSection ? 'true' : 'false' }}"
                data-docara-open="{{ $item->open ? 'true' : 'false' }}"
                data-docara-has-children="{{ $item->hasChildren ? 'true' : 'false' }}"
            >
                @if ($item->url !== null)
                    <a
                        class="block p-1 radius-1{{ $item->active ? ' bg-primary text-on-primary' : '' }}"
                        href="{!! $item->url !!}"
                        @if ($item->active) aria-current="page" @endif
                    >{!! $item->title !!}</a>
                @else
                    <span class="block p-1 font-weight-600">{!! $item->title !!}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
