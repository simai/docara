<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $page ? 'Edit page' : 'Create page' }}</title></head>
<body><main><h1>{{ $page ? 'Edit page' : 'Create page' }}</h1>
<form method="post" action="{{ $page ? route('larena.docara.admin.pages.update', ['slug' => $page->slug]) : route('larena.docara.admin.pages.store') }}">
@csrf @if ($page) @method('PUT') @endif
<label>Title <input name="title" value="{{ old('title', $page?->title) }}" required></label>
<label>Slug <input name="slug" value="{{ old('slug', $page?->slug) }}" required></label>
<label>Body <textarea name="body" required>{{ old('body', $page?->body) }}</textarea></label>
<label>Status <select name="status">@foreach (['draft','review','archived'] as $status)<option value="{{ $status }}" @selected(old('status', $page?->publication->status->value ?? 'draft') === $status)>{{ $status }}</option>@endforeach</select></label>
<button type="submit">Save</button></form>
@if ($page && $page->publication->status->value !== 'published')<form method="post" action="{{ route('larena.docara.admin.pages.publish', ['slug' => $page->slug]) }}">@csrf<button type="submit">Publish</button></form>@endif
</main></body></html>
