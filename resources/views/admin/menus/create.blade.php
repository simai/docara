@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.menus.create_title').' · Larena')
@section('eyebrow', __('larena-docara::admin.menus.eyebrow'))
@section('heading', __('larena-docara::admin.menus.create_heading'))
@section('description', __('larena-docara::admin.menus.create_description'))

@section('content')
<section class="larena-panel larena-form-panel">
    {!! $components['validation'] !!}
    <form method="post" action="{{ route('larena.docara.admin.menus.store') }}" class="larena-form">@csrf
        {!! $components['name'] !!}{!! $components['code'] !!}{!! $components['locale'] !!}{!! $components['active'] !!}
        <div class="larena-form-actions">{!! $components['submit'] !!}<a href="{{ route('larena.docara.admin.menus.index') }}">{{ __('larena-docara::admin.menus.actions.cancel') }}</a></div>
    </form>
</section>
@endsection
