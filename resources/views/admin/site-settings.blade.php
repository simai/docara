@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.site_settings.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.site_settings.eyebrow'))
@section('heading', __('larena-docara::admin.site_settings.heading'))
@section('description', __('larena-docara::admin.site_settings.description'))

@section('content')
    {!! $formComponents['validation'] !!}
    {!! $formComponents['read_only'] !!}

    <form class="larena-panel larena-form" method="post" action="{{ route('larena.docara.admin.site_settings.update') }}">
        @csrf
        @method('PUT')
        <fieldset @disabled(!$canWrite)>
            <legend>{{ __('larena-docara::admin.site_settings.identity') }}</legend>
            <div class="larena-form-grid">
                {!! $formComponents['name_en'] !!}
                {!! $formComponents['name_ru'] !!}
                {!! $formComponents['description_en'] !!}
                {!! $formComponents['description_ru'] !!}
            </div>
        </fieldset>

        <fieldset @disabled(!$canWrite)>
            <legend>{{ __('larena-docara::admin.site_settings.branding') }}</legend>
            <div class="larena-form-grid">
                {!! $formComponents['logo_file_ref'] !!}
                {!! $formComponents['favicon_file_ref'] !!}
            </div>
        </fieldset>

        <fieldset @disabled(!$canWrite)>
            <legend>{{ __('larena-docara::admin.site_settings.homepage') }}</legend>
            <div class="larena-form-grid">
                {!! $formComponents['default_locale'] !!}
                {!! $formComponents['homepage_page_ref_en'] !!}
                {!! $formComponents['homepage_page_ref_ru'] !!}
            </div>
        </fieldset>

        {!! $formComponents['save'] !!}
    </form>
@endsection
