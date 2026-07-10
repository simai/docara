<!doctype html>
<html lang="{{ $page->locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Larena</title>
    @foreach (($docaraPublicAssets['assets'] ?? []) as $asset)
        @if (($asset['kind'] ?? null) === 'css')
            <link
                rel="stylesheet"
                href="{{ $asset['final_path'] }}"
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
            <span class="larena-public-brand"><span aria-hidden="true">L</span> Larena</span>
            <span class="larena-public-context">{{ __('larena-docara::public.site_context') }}</span>
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
