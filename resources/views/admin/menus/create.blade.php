@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.menus.create_title').' · Larena')
@section('eyebrow', __('larena-docara::admin.menus.eyebrow'))
@section('heading', __('larena-docara::admin.menus.create_heading'))
@section('description', __('larena-docara::admin.menus.create_description'))

@section('content')
<section class="larena-panel larena-form-panel">
    @if ($errors->any())<div class="larena-alert larena-alert-error" role="alert"><strong>{{ __('larena-docara::admin.menus.validation_heading') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ route('larena.docara.admin.menus.store') }}" class="larena-form">@csrf
        <div class="larena-field"><label for="menu-name">{{ __('larena-docara::admin.menus.fields.name') }}</label><input id="menu-name" name="name" value="{{ old('name') }}" maxlength="255" required></div>
        <div class="larena-field"><label for="menu-code">{{ __('larena-docara::admin.menus.fields.code') }}</label><input id="menu-code" name="code" value="{{ old('code', 'main') }}" maxlength="80" pattern="[a-z][a-z0-9_-]*" required><small>{{ __('larena-docara::admin.menus.fields.code_help') }}</small></div>
        <div class="larena-field"><label for="menu-locale">{{ __('larena-docara::admin.menus.fields.locale') }}</label><select id="menu-locale" name="locale" required><option value="en" @selected(old('locale') === 'en')>English</option><option value="ru" @selected(old('locale') === 'ru')>Русский</option></select></div>
        <label class="larena-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> {{ __('larena-docara::admin.menus.fields.active') }}</label>
        <div class="larena-form-actions"><button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.menus.actions.create') }}</button><a href="{{ route('larena.docara.admin.menus.index') }}">{{ __('larena-docara::admin.menus.actions.cancel') }}</a></div>
    </form>
</section>
@endsection
