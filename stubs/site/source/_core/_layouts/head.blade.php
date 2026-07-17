<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="description" content="{{ $page->description ?? $page->siteDescription }}">

    <meta property="og:site_name" content="{{ $page->siteName }}"/>
    <meta property="og:title" content="{{ $page->title ?  $page->title . ' | ' : '' }}{{ $page->siteName }}"/>
    <meta property="og:description" content="{{ $page->description ?? $page->siteDescription }}"/>
    <meta property="og:url" content="{{ $page->getUrl() }}"/>
    <meta property="og:image" content="/assets/build/img/logo.svg"/>
    <meta property="og:type" content="website"/>
    <meta name="turbo-refresh-method" content="morph">
    <meta name="twitter:image:alt" content="{{ $page->siteName }}">
    <meta name="twitter:card" content="summary_large_image">

    @if ($page->docsearchApiKey && $page->docsearchIndexName)
        <meta name="generator" content="tighten_jigsaw_doc">
    @endif

    <title>{{ $page->siteName }}{{ $page->title ? ' | ' . $page->title : '' }}</title>

    <link rel="home" href="{{ $page->baseUrl }}">
    <link rel="icon" href="/favicon.ico">

    @stack('meta')

    @if ($page->turbo)
        <script type="module" src="{{ vite('source/_core/_assets/js/turbo.js') }}"></script>
    @endif

    @include('_core._layouts.core')
    @php
    $jsTranslation = $page->getJsTranslations();
    @endphp

    @if ($jsTranslation)
        <script>
        window.sfJsLang = {!! json_encode($jsTranslation, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!};
        </script>
    @endif
    {!! vite_refresh() !!}
    <link rel="stylesheet" href="{{ vite('source/_core/_assets/css/main.scss') }}">
    <script>
        window.getCookie = function (name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }
    </script>
    <script type="module" data-turbo-permanent src="{{ vite('source/_core/_assets/js/main.js') }}"></script>
</head>
