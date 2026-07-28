<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class PublisherChromeViewModel
{
    /**
     * @param  array<string, string>  $regions
     * @param  list<array{title:string,url:?string,current:bool}>  $breadcrumbs
     * @param  array{title:string,url:string}|null  $previous
     * @param  array{title:string,url:string}|null  $next
     * @param  array<string, string>  $copy
     * @param  list<array{locale:string,label:string,url:string,current:bool}>  $languageOptions
     */
    public function __construct(
        public string $preset,
        public string $direction,
        public bool $searchEnabled,
        public ?string $searchRuntimeUrl,
        public ?string $searchIndexUrl,
        public array $regions,
        public bool $mobileTocEnabled,
        public array $breadcrumbs,
        public ?array $previous,
        public ?array $next,
        public array $copy,
        public array $languageOptions,
        public bool $mobileNavigationEnabled,
        public bool $primaryNavigationEnabled,
        public bool $documentationNavigationEnabled,
        public bool $readerPreferencesEnabled,
        public string $readerPreferencesHtml,
    ) {}
}
