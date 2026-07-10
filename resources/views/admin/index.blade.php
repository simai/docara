@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.pages.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.pages.eyebrow'))
@section('heading', __('larena-docara::admin.pages.heading'))
@section('description', __('larena-docara::admin.pages.description'))
@section('actions')
    @if ($canWrite)
        <a class="larena-button larena-button-primary" href="{{ route('larena.docara.admin.pages.create') }}">{{ __('larena-docara::admin.actions.create') }}</a>
    @endif
@endsection

@section('content')
    <section class="larena-panel" aria-label="{{ __('larena-docara::admin.pages.aria_label') }}">
        @if ($pages === [])
            <div class="larena-empty">
                <h2>{{ __('larena-docara::admin.empty.title') }}</h2>
                <p>{{ $canWrite ? __('larena-docara::admin.empty.writer_text') : __('larena-docara::admin.empty.reader_text') }}</p>
                @if ($canWrite)
                    <a class="larena-button larena-button-primary" href="{{ route('larena.docara.admin.pages.create') }}">{{ __('larena-docara::admin.actions.create_first') }}</a>
                @endif
            </div>
        @else
            <table class="larena-table larena-table-stack">
                <thead><tr><th>{{ __('larena-docara::admin.columns.page') }}</th><th>{{ __('larena-docara::admin.columns.slug') }}</th><th>{{ __('larena-docara::admin.columns.status') }}</th><th><span class="larena-visually-hidden">{{ __('larena-docara::admin.columns.action') }}</span></th></tr></thead>
                <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td data-label="{{ __('larena-docara::admin.columns.page') }}">
                            @if ($canWrite)
                                <a class="larena-table-title" href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page['slug']]) }}">{{ $page['title'] }}</a>
                            @else
                                <strong>{{ $page['title'] }}</strong>
                            @endif
                        </td>
                        <td data-label="{{ __('larena-docara::admin.columns.slug') }}"><code>/{{ $page['slug'] }}</code></td>
                        <td data-label="{{ __('larena-docara::admin.columns.status') }}"><span class="larena-status larena-status-{{ $page['status'] }}">{{ __('larena-docara::admin.statuses.'.$page['status']) }}</span></td>
                        <td data-label="{{ __('larena-docara::admin.columns.action') }}">
                            @if ($canWrite)
                                <a href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page['slug']]) }}">{{ __('larena-docara::admin.actions.edit') }}</a>
                            @else
                                <a href="{{ route('larena.docara.admin.pages.preview', ['slug' => $page['slug']]) }}">{{ __('larena-docara::admin.actions.preview') }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
