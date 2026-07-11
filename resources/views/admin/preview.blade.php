@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.preview.title', ['title' => $page->title]).' · Larena')
@section('eyebrow', __('larena-docara::admin.preview.eyebrow'))
@section('heading', $page->title)
@section('description', __('larena-docara::admin.preview.description'))
@section('actions')
    @if ($canWrite)
        {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.edit', ['slug' => $page->slug]), __('larena-docara::admin.actions.back_to_edit'))->html !!}
    @endif
    {!! \Larena\Ui\SfActionLink::render(route('larena.docara.admin.pages.blocks.edit', ['slug' => $page->slug, 'locale' => $page->locale]), __('larena-docara::admin.actions.compose_blocks'))->html !!}
    @if ($page->publication->status->value === 'published')
        {!! \Larena\Ui\SfActionLink::render(route('larena.docara.public.show', ['slug' => $page->slug]), __('larena-docara::admin.actions.view_live'))->html !!}
    @endif
@endsection

@section('content')
    <div class="larena-form-actions" role="status">
        {{ __('larena-docara::admin.preview.status') }}
        {!! \Larena\Ui\Smart::render('sf-badge', ['size' => '1/2', 'type' => 'tonal', 'scheme' => $page->publication->status->value === 'published' ? 'success' : 'neutral', 'text' => __('larena-docara::admin.statuses.'.$page->publication->status->value)])->html !!}
    </div>
    <link rel="stylesheet" href="{{ route('larena.docara.assets.show', ['assetKey' => 'docara.public.page.css']) }}">
    <article class="larena-panel larena-form larena-public-article" data-larena-page-preview="protected" data-composition-mode="{{ $compositionMode }}">
        <h2>{{ $page->title }}</h2>
        @if (($compositionBlocks ?? []) !== [])
            @include('larena-docara::blocks.index', ['blocks' => $compositionBlocks])
        @else
            <div class="larena-preformatted-content">{{ $page->body }}</div>
        @endif
    </article>
@endsection
