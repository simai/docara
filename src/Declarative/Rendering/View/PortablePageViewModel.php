<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class PortablePageViewModel
{
    /**
     * @param  array<string, string>  $regions
     * @param  list<array{title:string,url:?string,current:bool}>  $breadcrumbs
     * @param  array{title:string,url:string}|null  $previous
     * @param  array{title:string,url:string}|null  $next
     * @param  list<array{value:string,title:string,description:string,checked:bool}>  $themeOptions
     * @param  array<string, string>  $copy
     * @param  list<array{locale:string,url:string}>  $alternates
     * @param  list<array{locale:string,label:string,url:string,current:bool}>  $languageOptions
     * @param  array<string, string>  $chrome
     */
    public function __construct(
        public string $locale,
        public string $direction,
        public string $documentationVersion,
        public string $documentTitle,
        public ?string $description,
        public ?string $favicon,
        public ?string $faviconType,
        public string $headHtml,
        public string $themeBootstrap,
        public string $preset,
        public string $maxWidth,
        public string $mobileTocState,
        public bool $searchEnabled,
        public ?string $searchRuntimeUrl,
        public ?string $searchIndexUrl,
        public string $shellCssUrl,
        public string $shellRuntimeUrl,
        public array $regions,
        public array $breadcrumbs,
        public ?array $previous,
        public ?array $next,
        public array $themeOptions,
        public string $configuredTheme,
        public array $copy,
        public string $canonicalUrl,
        public array $alternates,
        public array $languageOptions,
        public string $runtimeCopyJson,
        public array $chrome,
    ) {}
}
