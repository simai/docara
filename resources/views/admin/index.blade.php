@extends('larena-admin::layouts.app')

@section('title', 'Pages · Larena')
@section('eyebrow', 'Content')
@section('heading', 'Pages')
@section('description', 'Create, review and publish the pages available on your Larena site.')
@section('actions')
    <a class="larena-button larena-button-primary" href="{{ route('larena.docara.admin.pages.create') }}">Create page</a>
@endsection

@section('content')
    <section class="larena-panel" aria-label="Pages">
        @if ($pages === [])
            <div class="larena-empty">
                <h2>No pages yet</h2>
                <p>Create the first page to start building your site.</p>
                <a class="larena-button larena-button-primary" href="{{ route('larena.docara.admin.pages.create') }}">Create first page</a>
            </div>
        @else
            <table class="larena-table">
                <thead><tr><th>Page</th><th>Slug</th><th>Status</th><th><span class="larena-visually-hidden">Action</span></th></tr></thead>
                <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td><a class="larena-table-title" href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page['slug']]) }}">{{ $page['title'] }}</a></td>
                        <td><code>/{{ $page['slug'] }}</code></td>
                        <td><span class="larena-status larena-status-{{ $page['status'] }}">{{ $page['status'] }}</span></td>
                        <td><a href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page['slug']]) }}">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
