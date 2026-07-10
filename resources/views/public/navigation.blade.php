@if ($items !== [])
<ul class="larena-public-nav__list{{ $nested ? ' larena-public-nav__list--nested' : '' }}">
    @foreach ($items as $item)
        <li class="larena-public-nav__item">
            <a href="{{ $item['url'] }}" @if(url()->current() === strtok($item['url'], '?')) aria-current="page" @endif>{{ $item['label'] }}</a>
            @include('larena-docara::public.navigation', ['items' => $item['children'], 'nested' => true])
        </li>
    @endforeach
</ul>
@endif
