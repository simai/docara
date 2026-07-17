@php
    $hasSha = $page->sha ?? 'latest';
    $locale = $page->locale();
    $distPath = "/distr/"
@endphp
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<script>
    window.SF_BOOT_CONFIG = {
        icons: {
            accumulate: true,
        },
    };
    window.sfPath = "{{$distPath}}";
    window.currentLocale = `{{$locale}}`
</script>
<script src="{{'/distr/core/js/core.js'}}"></script>
<link rel="preload" as="style"  href="{{'/distr/core/css/core.css'}}">
<link rel="stylesheet" href="{{'/distr/core/css/core.css'}}"/>
