<!doctype html>
<html lang="{{ $page->locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $siteIdentity['name'] ?? 'Larena' }}</title>
    @if (!empty($siteIdentity['description']))<meta name="description" content="{{ $siteIdentity['description'] }}">@endif
    @if (!empty($siteIdentity['favicon_url']))<link rel="icon" href="{{ $siteIdentity['favicon_url'] }}">@endif
    @foreach (($docaraPublicAssets['assets'] ?? []) as $asset)
        @if (($asset['kind'] ?? null) === 'css')
            <link
                rel="stylesheet"
                href="{{ $asset['final_path'] }}?v={{ \Larena\Docara\Assets\DocumentationPageAssetManifest::ASSET_VERSION }}"
                data-larena-asset-key="{{ $asset['asset_key'] }}"
                data-larena-asset-owner="{{ $asset['activation_owner'] }}"
            >
        @endif
    @endforeach
</head>
<body class="larena-public">
    <a class="larena-public-skip" href="#content">{{ __('larena-docara::public.skip_to_content') }}</a>
    <header class="larena-public-header">
        <div class="larena-public-header__inner">
            <a class="larena-public-brand" href="{{ route('larena.docara.public.home', ['locale' => $page->locale]) }}">
                @if (!empty($siteIdentity['logo_url']))
                    <img src="{{ $siteIdentity['logo_url'] }}" alt="" width="34" height="34">
                @else
                    <span aria-hidden="true">L</span>
                @endif
                <span>{{ $siteIdentity['name'] ?? 'Larena' }}</span>
            </a>
            @if (($publicNavigation ?? []) !== [])
                <nav class="larena-public-nav" aria-label="{{ __('larena-docara::public.navigation_label') }}">
                    @include('larena-docara::public.navigation', ['items' => $publicNavigation, 'nested' => false])
                </nav>
            @else
                <span class="larena-public-context">{{ __('larena-docara::public.site_context') }}</span>
            @endif
        </div>
    </header>
    <main id="content" class="larena-public-main" tabindex="-1">
        @yield('content')
    </main>
    <footer class="larena-public-footer">
        <p>{{ __('larena-docara::public.footer') }}</p>
        <p>{{ __('larena-docara::public.non_claim') }}</p>
    </footer>
</body>
</html>
