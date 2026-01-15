@php
    $navigation = $page->getNavItems();
    $ordered = [];
    foreach (['prev', 'next'] as $k) {
        if (isset($navigation[$k])) {
            $ordered[$k] = $navigation[$k];
        }
    }
@endphp

<section>
    <div class="bottom--navigation flex">
        <div class="bottom--navigation-items w-full flex">
            @foreach($ordered as $key => $item)
                @php
                    $prev = $key === 'prev';
                    $text =  $page->translate($prev ? 'previous' : 'next');
                @endphp
                <button type="button" onclick="window.location.href='{{$item['path']}}'" class="sf-button flex sf-button--size-1 sf-button--on-surface sf-button--link items-cross-end bottom--navigation-item_{{$key}}">
                    @if($prev)
                        <i class="sf-icon">chevron_left</i>
                    @endif
                    <div class="sf-button-text-container sf-button-text">
                        <div class="flex bottom--navigation_text flex-col">
                            <div>{{$text}}</div>
                            <div>{{$item['label']}}</div>
                        </div>
                    </div>
                    @if(!$prev)
                        <i class="sf-icon">chevron_right</i>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</section>
