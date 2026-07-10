@extends('larena-admin::layouts.app')

@php($editing = $page !== null)
@section('title', ($editing ? 'Edit page' : 'Create page').' · Larena')
@section('eyebrow', 'Content / Pages')
@section('heading', $editing ? 'Edit page' : 'Create page')
@section('description', $editing ? 'Update the page content and publication state.' : 'Add a page with a clear title, URL slug and body.')
@section('actions')
    <a class="larena-button" href="{{ route('larena.docara.admin.pages.index') }}">Back to pages</a>
@endsection

@section('content')
    <section class="larena-panel">
        <form class="larena-form" method="post" action="{{ $editing ? route('larena.docara.admin.pages.update', ['slug' => $page->slug]) : route('larena.docara.admin.pages.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="larena-form-grid">
                <div class="larena-field">
                    <label for="page-title">Title</label>
                    <input id="page-title" name="title" value="{{ old('title', $page?->title) }}" maxlength="255" required @error('title') aria-invalid="true" aria-describedby="page-title-error" @enderror>
                    @error('title')<span id="page-title-error" class="larena-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="larena-field">
                    <label for="page-slug">Slug</label>
                    <input id="page-slug" name="slug" value="{{ old('slug', $page?->slug) }}" maxlength="255" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required @error('slug') aria-invalid="true" aria-describedby="page-slug-error" @enderror>
                    @error('slug')<span id="page-slug-error" class="larena-field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="larena-field">
                <label for="page-body">Body</label>
                <textarea id="page-body" name="body" required @error('body') aria-invalid="true" aria-describedby="page-body-error" @enderror>{{ old('body', $page?->body) }}</textarea>
                @error('body')<span id="page-body-error" class="larena-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="larena-field">
                <label for="page-status">Status</label>
                <select id="page-status" name="status">
                    @foreach (['draft' => 'Draft', 'review' => 'In review', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $page?->publication->status->value ?? 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="larena-form-actions">
                <button class="larena-button larena-button-primary" type="submit">Save page</button>
            </div>
        </form>
    </section>

    @if ($editing && $page->publication->status->value !== 'published')
        <form class="larena-secondary-action" method="post" action="{{ route('larena.docara.admin.pages.publish', ['slug' => $page->slug]) }}">
            @csrf
            <button class="larena-button" type="submit">Publish page</button>
        </form>
    @endif
@endsection
