@extends('larena-admin::layouts.app')

@section('title', __('larena-docara::admin.site_settings.title').' · Larena')
@section('eyebrow', __('larena-docara::admin.site_settings.eyebrow'))
@section('heading', __('larena-docara::admin.site_settings.heading'))
@section('description', __('larena-docara::admin.site_settings.description'))

@section('content')
    @if ($errors->any())
        <div class="larena-alert larena-alert-danger" role="alert">
            <strong>{{ __('larena-docara::admin.site_settings.validation_heading') }}</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @unless ($canWrite)
        <div class="larena-alert" role="status">{{ __('larena-docara::admin.site_settings.read_only') }}</div>
    @endunless

    <form class="larena-panel larena-form" method="post" action="{{ route('larena.docara.admin.site-settings.update') }}">
        @csrf
        @method('PUT')
        <fieldset @disabled(!$canWrite)>
            <legend>{{ __('larena-docara::admin.site_settings.identity') }}</legend>
            <div class="larena-form-grid">
                <label class="larena-field"><span>{{ __('larena-docara::admin.site_settings.fields.name_en') }}</span><input name="name_en" value="{{ old('name_en', $settings['name.en']) }}" maxlength="120" required></label>
                <label class="larena-field"><span>{{ __('larena-docara::admin.site_settings.fields.name_ru') }}</span><input name="name_ru" value="{{ old('name_ru', $settings['name.ru']) }}" maxlength="120" required></label>
                <label class="larena-field"><span>{{ __('larena-docara::admin.site_settings.fields.description_en') }}</span><textarea name="description_en" maxlength="500" rows="3">{{ old('description_en', $settings['description.en']) }}</textarea></label>
                <label class="larena-field"><span>{{ __('larena-docara::admin.site_settings.fields.description_ru') }}</span><textarea name="description_ru" maxlength="500" rows="3">{{ old('description_ru', $settings['description.ru']) }}</textarea></label>
            </div>
        </fieldset>

        <fieldset @disabled(!$canWrite)>
            <legend>{{ __('larena-docara::admin.site_settings.branding') }}</legend>
            <div class="larena-form-grid">
                @foreach (['logo_file_ref' => 'logo', 'favicon_file_ref' => 'favicon'] as $field => $label)
                    <label class="larena-field"><span>{{ __('larena-docara::admin.site_settings.fields.'.$label) }}</span>
                        <select name="{{ $field }}"><option value="">{{ __('larena-docara::admin.site_settings.no_image') }}</option>
                            @foreach ($images as $image)<option value="{{ $image['logical_ref'] }}" @selected(old($field, $settings[$field]) === $image['logical_ref'])>{{ $image['display_name'] }} · {{ $image['mime_type'] }}</option>@endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset @disabled(!$canWrite)>
            <legend>{{ __('larena-docara::admin.site_settings.homepage') }}</legend>
            <div class="larena-form-grid">
                <label class="larena-field"><span>{{ __('larena-docara::admin.site_settings.fields.default_locale') }}</span><select name="default_locale"><option value="en" @selected(old('default_locale', $settings['default_locale']) === 'en')>English</option><option value="ru" @selected(old('default_locale', $settings['default_locale']) === 'ru')>Русский</option></select></label>
                @foreach (['en' => ['homepage_en', 'homepage_page_ref_en'], 'ru' => ['homepage_ru', 'homepage_page_ref_ru']] as $locale => $homeField)
                    <label class="larena-field"><span>{{ __('larena-docara::admin.site_settings.fields.'.$homeField[0]) }}</span>
                        <select name="{{ $homeField[1] }}"><option value="">{{ __('larena-docara::admin.site_settings.no_page') }}</option>
                            @foreach ($pages as $page) @if ($page['locale'] === $locale)<option value="{{ $page['page_ref'] }}" @selected(old($homeField[1], $settings['homepage_page_ref.'.$locale]) === $page['page_ref'])>{{ $page['title'] }} · /{{ $page['slug'] }}</option>@endif @endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        </fieldset>

        @if ($canWrite)<button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.site_settings.save') }}</button>@endif
    </form>
@endsection
