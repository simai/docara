@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.menus.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.menus.eyebrow'))
@section('heading', __('larena-docara::admin.menus.heading'))
@section('description', __('larena-docara::admin.menus.description'))
@section('actions')
    @if ($canWrite)<a class="larena-button larena-button-primary" href="{{ route('larena.docara.admin.menus.create') }}">{{ __('larena-docara::admin.menus.actions.create') }}</a>@endif
@endsection

@section('content')
<section class="larena-panel" aria-label="{{ __('larena-docara::admin.menus.aria_label') }}">
    @if ($menus->isEmpty())
        <div class="larena-empty"><h2>{{ __('larena-docara::admin.menus.empty_title') }}</h2><p>{{ __('larena-docara::admin.menus.empty_text') }}</p></div>
    @else
        <table class="larena-table larena-table-stack"><thead><tr><th>{{ __('larena-docara::admin.menus.columns.menu') }}</th><th>{{ __('larena-docara::admin.menus.columns.code') }}</th><th>{{ __('larena-docara::admin.menus.columns.locale') }}</th><th>{{ __('larena-docara::admin.menus.columns.status') }}</th><th><span class="larena-visually-hidden">{{ __('larena-docara::admin.menus.columns.action') }}</span></th></tr></thead><tbody>
        @foreach ($menus as $menu)
            <tr><td data-label="{{ __('larena-docara::admin.menus.columns.menu') }}"><strong>{{ $menu->name }}</strong></td><td data-label="{{ __('larena-docara::admin.menus.columns.code') }}"><code>{{ $menu->code }}</code></td><td data-label="{{ __('larena-docara::admin.menus.columns.locale') }}">{{ strtoupper($menu->locale) }}</td><td data-label="{{ __('larena-docara::admin.menus.columns.status') }}">{{ $menu->is_active ? __('larena-docara::admin.menus.active') : __('larena-docara::admin.menus.inactive') }}</td><td data-label="{{ __('larena-docara::admin.menus.columns.action') }}"><a href="{{ route('larena.docara.admin.menus.edit', ['menu' => $menu->id]) }}">{{ $canWrite ? __('larena-docara::admin.menus.actions.edit') : __('larena-docara::admin.menus.actions.view') }}</a></td></tr>
        @endforeach
        </tbody></table>
    @endif
</section>
@endsection
