@php
    $breadcrumbs_array = $page->generateBreadcrumbs();
    $home =  $page->isHome();
@endphp
@if(!$home)
    <div class="sf-breadcrumb truncate block p-bottom-c2 w-full">
        @foreach($breadcrumbs_array as $key => $item)
            @if($key === 0)
                <div class="sf-breadcrumb-item text-middle inline-flex items-cross-center gap-1/2">
                    <a class="flex items-cross-center" href="{{$item['path']}}">
                        <i class="color-on-surface sf-icon sf-icon-medium">home</i>
                    </a>
                    <i class="sf-icon sf-icon-light">chevron_right</i>
                </div>
            @else
                <div class="sf-breadcrumb-item text-middle text-1/2 inline-flex items-cross-center gap-1/2">
                    @if(isset($item['path']) && !$loop->last)
                        <a class="inline-flex items-cross-center" href="{{$item['path']}}">{{$item['label']}}</a>
                    @else
                        <span class="inline-flex items-cross-center">{{$item['label']}}</span>
                    @endif

                    @if(!$loop->last)
                        <i class="sf-icon sf-icon-light">chevron_right</i>
                    @endif
                </div>
            @endif

        @endforeach
    </div>
@endif
