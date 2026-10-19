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
     * @param  array<string, string>  $copy
     * @param  list<array{locale:string,url:string}>  $alternates
     * @param  list<array{locale:string,label:string,url:string,current:bool}>  $languageOptions
     * @param  array<string, string>  $chrome
     * @param  list<string>  $shellCssUrls
     * @param  string|null  $themeCssUrl  the site theme stylesheet (docara.json `theme`), escaped
     * @param  string|null  $socialMeta  Open Graph and Twitter card tags (site_url set), already escaped HTML
     * @param  string|null  $robots  the robots meta value (noindex pages and held-back translations), escaped
     * @param  array{url:string,label:string}|null  $portalHome  the link back to the portal home of a portal build, escaped
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
        public string $containerClass,
        public string $scrollbarPreset,
        public int $contentGap,
        public string $mobileTocState,
        public bool $searchEnabled,
        public ?string $searchRuntimeUrl,
        public ?string $searchIndexUrl,
        public array $shellCssUrls,
        public string $shellRuntimeUrl,
        public array $regions,
        public array $breadcrumbs,
        public ?array $previous,
        public ?array $next,
        public array $copy,
        public string $canonicalUrl,
        public array $alternates,
        public array $languageOptions,
        public string $runtimeCopyJson,
        public array $chrome,
        public ?string $themeCssUrl = null,
        public ?string $robots = null,
        public ?string $socialMeta = null,
        public ?array $portalHome = null,
    ) {}
}
