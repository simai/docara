@php
    $footerContent = $page->footerContent ?? [];
    $footerText = $footerContent['text'] ?? 'Built with Docara';
    $footerUrl = $footerContent['url'] ?? null;
@endphp
<footer class="border-t-1 border-outline-variant bg-surface-0 sticky bottom-0 z-10" role="contentinfo">
    <div class="p-3 w-full">
        <div class="flex items-cross-center content-main-end container gap-1 px-b6 m-auto">
            <div>
                @if($footerUrl)
                    <a href="{{ $footerUrl }}" rel="noopener noreferrer">{{ $footerText }}</a>
                @else
                    {{ $footerText }}
                @endif
            </div>
        </div>
    </div>
</footer>
