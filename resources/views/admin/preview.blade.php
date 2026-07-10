@extends('larena-admin::layouts.app')

@section('title', 'Preview: '.$page->title.' · Larena')
@section('eyebrow', 'Content / Pages / Preview')
@section('heading', $page->title)
@section('description', 'Protected preview of the saved page. This preview is not the public URL.')
@section('actions')
    <a class="larena-button" href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page->slug]) }}">Back to edit</a>
    @if ($page->publication->status->value === 'published')
        <a class="larena-button" href="{{ route('larena.docara.public.show', ['slug' => $page->slug]) }}">View live page</a>
    @endif
@endsection

@section('content')
    <div class="larena-notice" role="status">
        Protected preview · Current status:
        <span class="larena-status larena-status-{{ $page->publication->status->value }}">{{ $page->publication->status->value }}</span>
    </div>
    <article class="larena-panel larena-form" data-larena-page-preview="protected">
        <h2>{{ $page->title }}</h2>
        <div>{!! nl2br(e($page->body)) !!}</div>
    </article>
@endsection
