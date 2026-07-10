@extends('larena-docara::public.layout')

@section('title', $page->title)

@section('content')
    <article class="larena-public-article" data-larena-public-page="published">
        <p class="larena-public-eyebrow">{{ __('larena-docara::public.published_page') }}</p>
        <h1>{{ $page->title }}</h1>
        @if($hero)<figure class="larena-public-hero"><img src="{{ $hero['url'] }}" alt="{{ $hero['alt'] }}" loading="eager"></figure>@endif
        <div class="larena-public-body">{{ $page->body }}</div>
    </article>
@endsection
