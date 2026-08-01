<?php

declare(strict_types=1);

namespace Simai\Docara\Content;

use JsonException;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class LegacyPublicSourceAllowlist
{
    /** @var array<string, true> */
    private array $generatedRoutes;

    /** @var array<string, int> */
    private array $languagePackComponentMaxima;

    public function __construct(?string $path = null)
    {
        $path ??= __DIR__ . '/../../resources/legacy-public-source-allowlist.json';
        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'LEGACY_PUBLIC_SOURCE_ALLOWLIST_INVALID',
                "Legacy public-source allowlist [$path] is not valid JSON.",
                $exception,
            );
        }
        if (! is_array($decoded) || ($decoded['schema'] ?? null) !== 'docara.legacy_public_sources.v1') {
            throw new PortableConfigurationException(
                'LEGACY_PUBLIC_SOURCE_ALLOWLIST_INVALID',
                "Legacy public-source allowlist [$path] has an invalid contract.",
            );
        }

        $routes = $decoded['generated_routes'] ?? null;
        $maxima = $decoded['language_pack_component_maxima'] ?? null;
        if (! is_array($routes) || ! array_is_list($routes) || ! is_array($maxima)) {
            throw new PortableConfigurationException(
                'LEGACY_PUBLIC_SOURCE_ALLOWLIST_INVALID',
                'Legacy generated routes and language-pack maxima must be explicit collections.',
            );
        }
        $this->generatedRoutes = array_fill_keys($routes, true);
        $this->languagePackComponentMaxima = array_map(static fn (mixed $count): int => (int) $count, $maxima);
    }

    /** @param list<array<string, mixed>> $pages */
    public function assertGeneratedPages(array $pages): void
    {
        foreach ($pages as $page) {
            if (($page['page_source_kind'] ?? null) !== 'generated_projection') {
                continue;
            }
            $url = (string) ($page['url'] ?? '');
            if (! isset($this->generatedRoutes[$url])) {
                throw new PortableConfigurationException(
                    'LEGACY_GENERATED_ROUTE_GROWTH_FORBIDDEN',
                    "Generated public route [$url] is outside the finite migration allowlist.",
                );
            }
        }
    }

    /** @param array<string, int> $counts */
    public function assertLanguagePackComponentCounts(array $counts): void
    {
        foreach ($counts as $locale => $count) {
            $maximum = $this->languagePackComponentMaxima[$locale] ?? null;
            if ($maximum === null || $count > $maximum) {
                throw new PortableConfigurationException(
                    'LEGACY_LANGUAGE_PACK_PROSE_GROWTH_FORBIDDEN',
                    "Language pack [$locale] has [$count] component prose records; maximum is [" . ($maximum ?? 0) . '].',
                );
            }
        }
    }

    /** @return list<string> */
    public function generatedRoutes(): array
    {
        return array_keys($this->generatedRoutes);
    }
}
