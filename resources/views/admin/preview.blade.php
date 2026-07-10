@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.preview.title', ['title' => $page->title]).' · Larena')
@section('eyebrow', __('larena-docara::admin.preview.eyebrow'))
@section('heading', $page->title)
@section('description', __('larena-docara::admin.preview.description'))
@section('actions')
    @if ($canWrite)
        <a class="larena-button" href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page->slug]) }}">{{ __('larena-docara::admin.actions.back_to_edit') }}</a>
    @endif
    @if ($page->publication->status->value === 'published')
        <a class="larena-button" href="{{ route('larena.docara.public.show', ['slug' => $page->slug]) }}">{{ __('larena-docara::admin.actions.view_live') }}</a>
    @endif
@endsection

@section('content')
    <div class="larena-notice" role="status">
        {{ __('larena-docara::admin.preview.status') }}
        <span class="larena-status larena-status-{{ $page->publication->status->value }}">{{ __('larena-docara::admin.statuses.'.$page->publication->status->value) }}</span>
    </div>
    <article class="larena-panel larena-form" data-larena-page-preview="protected">
        <h2>{{ $page->title }}</h2>
        <div class="larena-preformatted-content">{{ $page->body }}</div>
    </article>
@endsection
