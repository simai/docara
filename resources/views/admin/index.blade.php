<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Docara pages</title></head>
<body><main><h1>Docara pages</h1><a href="{{ route('larena.docara.admin.pages.create') }}">Create page</a>
<ul>@foreach ($pages as $page)<li><a href="{{ route('larena.docara.admin.pages.edit', ['slug' => $page['slug']]) }}">{{ $page['title'] }}</a> — {{ $page['status'] }}</li>@endforeach</ul>
</main></body></html>
