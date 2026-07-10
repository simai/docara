@extends('larena-admin::layouts.app')

@section('title', ($editing ? __('larena-docara::admin.form.edit_title') : __('larena-docara::admin.form.create_title')).' · Larena')
@section('eyebrow', __('larena-docara::admin.form.eyebrow'))
@section('heading', $editing ? __('larena-docara::admin.form.edit_heading') : __('larena-docara::admin.form.create_heading'))
@section('description', $editing ? __('larena-docara::admin.form.edit_description') : __('larena-docara::admin.form.create_description'))
@section('actions')
    <a class="larena-button" href="{{ route('larena.docara.admin.pages.index') }}">{{ __('larena-docara::admin.actions.back') }}</a>
    @if ($editing)
        <a class="larena-button" href="{{ route('larena.docara.admin.pages.preview', ['slug' => $page->slug]) }}">{{ __('larena-docara::admin.actions.preview') }}</a>
    @endif
@endsection

@section('content')
    @if ($editing)
        <div class="larena-notice" aria-label="{{ __('larena-docara::admin.form.publication_status') }}">
            {{ __('larena-docara::admin.form.current_status') }}
            <span class="larena-status larena-status-{{ $page->publication->status->value }}">{{ __('larena-docara::admin.statuses.'.$page->publication->status->value) }}</span>
        </div>
    @endif

    <section class="larena-panel">
        <form class="larena-form" method="post" action="{{ $editing ? route('larena.docara.admin.pages.update', ['slug' => $page->slug]) : route('larena.docara.admin.pages.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="larena-form-grid">
                <div class="larena-field">
                    <label for="page-title">{{ __('larena-docara::admin.fields.title') }}</label>
                    <input id="page-title" name="title" value="{{ old('title', $page?->title) }}" maxlength="255" required @error('title') aria-invalid="true" aria-describedby="page-title-error" @enderror>
                    @error('title')<span id="page-title-error" class="larena-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="larena-field">
                    <label for="page-slug">{{ __('larena-docara::admin.fields.slug') }}</label>
                    <input id="page-slug" name="slug" value="{{ old('slug', $page?->slug) }}" maxlength="255" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required @error('slug') aria-invalid="true" aria-describedby="page-slug-error" @enderror>
                    @error('slug')<span id="page-slug-error" class="larena-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="larena-field">
                <label for="page-body">{{ __('larena-docara::admin.fields.body') }}</label>
                <textarea id="page-body" name="body" required @error('body') aria-invalid="true" aria-describedby="page-body-error" @enderror>{{ old('body', $page?->body) }}</textarea>
                @error('body')<span id="page-body-error" class="larena-field-error">{{ $message }}</span>@enderror
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
                <button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.actions.save') }}</button>
            </div>
        </form>
    </section>

    @if ($editing)
        <div class="larena-form-actions larena-secondary-action" aria-label="{{ __('larena-docara::admin.form.publication_actions') }}">
            @if ($page->publication->status->value === 'published')
                <a class="larena-button" href="{{ route('larena.docara.public.show', ['slug' => $page->slug]) }}">{{ __('larena-docara::admin.actions.view_live') }}</a>
                @if ($canPublish)
                    <form method="post" action="{{ route('larena.docara.admin.pages.unpublish', ['slug' => $page->slug]) }}">
                        @csrf
                        <button class="larena-button" type="submit">{{ __('larena-docara::admin.actions.unpublish') }}</button>
                    </form>
                @endif
            @elseif ($canPublish)
                <form method="post" action="{{ route('larena.docara.admin.pages.publish', ['slug' => $page->slug]) }}">
                    @csrf
                    <button class="larena-button larena-button-primary" type="submit">{{ __('larena-docara::admin.actions.publish') }}</button>
                </form>
            @endif
        </div>
    @endif
@endsection
