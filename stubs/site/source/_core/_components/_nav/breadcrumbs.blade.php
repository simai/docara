@php
    $breadcrumbs_array = $page->generateBreadcrumbs();
    $home =  $page->isHome();
@endphp
@if(!$home)
    <div class="sf-breadcrumb truncate">
        @foreach($breadcrumbs_array as $key => $item)
            @if($key === 0)
                <div class="sf-breadcrumb-item inline-flex">
                    <a class="flex" href="{{$item['path']}}">
                        <i class="color-on-surface sf-icon sf-icon-medium">home</i>
                    </a>
                    <i class="sf-icon sf-icon-light">chevron_right</i>
                </div>
            @else
                <div class="sf-breadcrumb-item text-1/2 ">
                    @if(isset($item['path']) && !$loop->last)
                        <a href="{{$item['path']}}">{{$item['label']}}</a>
                    @else
                        <span>{{$item['label']}}</span>
                    @endif

                    @if(!$loop->last)
                        <i class="sf-icon sf-icon-light">chevron_right</i>
                    @endif
                </div>
            @endif

        @endforeach
    </div>
@endif
