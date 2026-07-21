<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class LocaleUrlProjector
{
    public function __construct(
        private string $baseUrl,
        private LocaleRegistry $registry,
        public LocaleRoutingPolicy $policy,
    ) {}

    /** @return array{url:string,output:string} */
    public function page(string $locale, string $slug): array
    {
        $prefix = $this->registry->get($locale)->publicPrefix;
        $relative = implode('/', array_filter([
            trim($prefix, '/'),
            trim($slug, '/'),
        ], static fn (string $part): bool => $part !== ''));

        return [
            'url' => $this->url($relative),
            'output' => $relative === '' ? 'index.html' : $relative . '/index.html',
        ];
    }

    public function home(string $locale): string
    {
        return $this->page($locale, '')['url'];
    }

    public function output(string $locale, string $relativeOutput): string
    {
        $prefix = trim($this->registry->get($locale)->publicPrefix, '/');
        $relativeOutput = ltrim($relativeOutput, '/');

        return implode('/', array_filter([$prefix, $relativeOutput], static fn (string $part): bool => $part !== ''));
    }

    public function rootUrl(): string
    {
        return $this->url('');
    }

    public function defaultLocaleUrl(): string
    {
        return $this->home($this->registry->default()->tag->value());
    }

    public function unprefixed(string $locale, string $canonicalUrl): string
    {
        $home = $this->home($locale);
        if (! str_starts_with($canonicalUrl, $home)) {
            throw new PortableConfigurationException(
                'LOCALE_CANONICAL_URL_MISMATCH',
                "Canonical URL [$canonicalUrl] does not belong to locale [$locale].",
            );
        }

        return $this->deploymentBase() . substr($canonicalUrl, strlen($home));
    }

    public function deploymentBase(): string
    {
        $base = trim($this->baseUrl, '/');

        return $base === '' ? '/' : '/' . $base . '/';
    }

    private function url(string $relative): string
    {
        $path = implode('/', array_filter([
            trim($this->baseUrl, '/'),
            trim($relative, '/'),
        ], static fn (string $part): bool => $part !== ''));

        return $path === '' ? '/' : '/' . $path . '/';
    }
}
