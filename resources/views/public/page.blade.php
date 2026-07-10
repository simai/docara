@extends('larena-docara::public.layout')

@section('title', $page->title)

@section('content')
    <article class="larena-public-article" data-larena-public-page="published">
        <p class="larena-public-eyebrow">{{ __('larena-docara::public.published_page') }}</p>
        <h1>{{ $page->title }}</h1>
        <div class="larena-public-body">{{ $page->body }}</div>
    </article>
@endsection
