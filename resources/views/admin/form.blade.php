@extends('larena-admin::layouts.app')

@section('title', ($editing ? __('larena-docara::admin.form.edit_title') : __('larena-docara::admin.form.create_title')).' · Larena')
@section('eyebrow', __('larena-docara::admin.form.eyebrow'))
@section('heading', $editing ? __('larena-docara::admin.form.edit_heading') : __('larena-docara::admin.form.create_heading'))
@section('description', $editing ? __('larena-docara::admin.form.edit_description') : __('larena-docara::admin.form.create_description'))
@section('actions')
    <a class="larena-button" href="{{ route('larena.docara.admin.pages.index') }}">{{ __('larena-docara::admin.actions.back') }}</a>
    @if ($editing)
        <a class="larena-button" href="{{ route('larena.docara.admin.pages.preview', ['slug' => $page->slug, 'locale' => $page->locale]) }}">{{ __('larena-docara::admin.actions.preview') }}</a>
        <a class="larena-button" href="{{ route('larena.docara.admin.pages.blocks.edit', ['slug' => $page->slug, 'locale' => $page->locale]) }}">{{ __('larena-docara::admin.actions.compose_blocks') }}</a>
    @endif
@endsection

@section('content')
    @if ($editing)
        <div class="larena-notice" aria-label="{{ __('larena-docara::admin.form.publication_status') }}">
            {{ __('larena-docara::admin.form.current_status') }}
            {!! $formComponents['status'](__('larena-docara::admin.statuses.'.$page->publication->status->value), $page->publication->status->value) !!}
        </div>
    @endif

    <section class="larena-panel">
        <form class="larena-form" method="post" action="{{ $editing ? route('larena.docara.admin.pages.update', ['slug' => $page->slug, 'locale' => $page->locale]) : route('larena.docara.admin.pages.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="larena-form-grid">
                {!! $formComponents['title'] !!}
                {!! $formComponents['slug'] !!}
                <div class="larena-field">
                    <label for="page-locale">{{ __('larena-docara::admin.fields.locale') }}</label>
                    <select id="page-locale" name="locale" required @if($editing) disabled @endif>
                        <option value="en" @selected(old('locale', $page?->locale ?? 'en') === 'en')>English</option>
                        <option value="ru" @selected(old('locale', $page?->locale ?? 'en') === 'ru')>Русский</option>
                    </select>
                    @if($editing)<input type="hidden" name="locale" value="{{ $page->locale }}">@endif
                </div>
            </div>
            {!! $formComponents['body'] !!}
            <div class="larena-field">
                <label for="page-hero">{{ __('larena-docara::admin.fields.hero_file') }}</label>
                <select id="page-hero" name="hero_file_ref">
                    <option value="">{{ __('larena-docara::admin.form.no_hero_file') }}</option>
                    @foreach ($availableImages as $image)
                        <option value="{{ $image->logical_ref }}" @selected(old('hero_file_ref', data_get($page, 'assets.0.logicalFileRef', '')) === $image->logical_ref)>{{ $image->display_name }} · {{ $image->mime_type }}</option>
                    @endforeach
                </select>
                @error('hero_file_ref')<span class="larena-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="larena-field">
                <label for="page-status">{{ __('larena-docara::admin.fields.status') }}</label>
                @if ($editing && $page->publication->status->value === 'published')
                    <input type="hidden" name="status" value="published">
                    <select id="page-status" disabled><option selected>{{ __('larena-docara::admin.statuses.published') }}</option></select>
                    <span>{{ __('larena-docara::admin.form.unpublish_help') }}</span>
                @else
                    <select id="page-status" name="status" @error('status') aria-invalid="true" aria-describedby="page-status-error" @enderror>
                        @foreach (['draft', 'review', 'archived'] as $value)
                            <option value="{{ $value }}" @selected(old('status', $page?->publication->status->value ?? 'draft') === $value)>{{ __('larena-docara::admin.statuses.'.$value) }}</option>
                        @endforeach
                    </select>
                @endif
                @error('status')<span id="page-status-error" class="larena-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="larena-form-actions">
                {!! $formComponents['save'] !!}
            </div>
        </form>
    </section>

    @if ($editing)
        <div class="larena-form-actions larena-secondary-action" aria-label="{{ __('larena-docara::admin.form.publication_actions') }}">
            @if ($page->publication->status->value === 'published')
                <a class="larena-button" href="{{ route('larena.docara.public.show', ['slug' => $page->slug, 'locale' => $page->locale]) }}">{{ __('larena-docara::admin.actions.view_live') }}</a>
                @if ($canPublish)
                    <form method="post" action="{{ route('larena.docara.admin.pages.unpublish', ['slug' => $page->slug, 'locale' => $page->locale]) }}">
                        @csrf
                        {!! $formComponents['unpublish'] !!}
                    </form>
                @endif
            @elseif ($canPublish)
                <form method="post" action="{{ route('larena.docara.admin.pages.publish', ['slug' => $page->slug, 'locale' => $page->locale]) }}">
                    @csrf
                    {!! $formComponents['publish'] !!}
                </form>
            @endif
        </div>
    @endif
@endsection
