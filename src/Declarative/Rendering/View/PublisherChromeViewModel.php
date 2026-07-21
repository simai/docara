<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Rendering\View;

final readonly class PublisherChromeViewModel
{
    /**
     * @param array<string, string> $regions
     * @param list<array{title:string,url:?string,current:bool}> $breadcrumbs
     * @param array{title:string,url:string}|null $previous
     * @param array{title:string,url:string}|null $next
     * @param list<array{value:string,title:string,description:string,checked:bool}> $themeOptions
     * @param array<string, string> $copy
     * @param list<array{locale:string,label:string,url:string,current:bool}> $languageOptions
     */
    public function __construct(
        public string $preset,
        public bool $searchEnabled,
        public ?string $searchRuntimeUrl,
        public ?string $searchIndexUrl,
        public array $regions,
        public bool $mobileTocEnabled,
        public array $breadcrumbs,
        public ?array $previous,
        public ?array $next,
        public array $themeOptions,
        public string $configuredTheme,
        public array $copy,
        public array $languageOptions,
    ) {}
}
