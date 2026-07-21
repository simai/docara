<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Preview;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class DeclarativePreviewRouteMap
{
    public const OUTPUT_ROOT = '_docara/declarative-preview';

    /**
     * @param  array<string, string>  $urlsByLegacyUrl
     * @param  array<string, string>  $outputsByLegacyUrl
     */
    public function __construct(
        public string $indexUrl,
        public string $indexOutput,
        public string $receiptUrl,
        public string $receiptOutput,
        public array $urlsByLegacyUrl,
        public array $outputsByLegacyUrl,
    ) {
        if (! self::safeUrl($indexUrl)
            || ! self::safeOutput($indexOutput)
            || ! self::safeUrl($receiptUrl)
            || ! self::safeOutput($receiptOutput)
            || array_keys($urlsByLegacyUrl) !== array_keys($outputsByLegacyUrl)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PREVIEW_ROUTE_MAP_INVALID',
                'Declarative preview route map is invalid.',
            );
        }
        foreach ($urlsByLegacyUrl as $legacyUrl => $previewUrl) {
            if (! self::safeUrl($legacyUrl)
                || ! self::safeUrl($previewUrl)
                || ! self::safeOutput($outputsByLegacyUrl[$legacyUrl])
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PREVIEW_ROUTE_MAP_INVALID',
                    'Declarative preview route map contains an unsafe route.',
                );
            }
        }
    }

    /** @param list<array<string, mixed>> $pages */
    public static function fromPages(array $pages, string $outputPrefix = ''): self
    {
        $outputPrefix = trim($outputPrefix, '/');
        $outputRoot = ($outputPrefix === '' ? '' : $outputPrefix . '/') . self::OUTPUT_ROOT;
        $homeUrl = null;
        $urls = [];
        $outputs = [];
        foreach ($pages as $page) {
            if (! array_key_exists('declarative_supported', $page)) {
                continue;
            }
            $homeUrl ??= is_string($page['home_url'] ?? null) ? $page['home_url'] : null;
            if (($page['declarative_supported'] ?? false) !== true) {
                continue;
            }
            $legacyUrl = is_string($page['url'] ?? null) ? $page['url'] : '';
            $output = is_string($page['output'] ?? null) ? $page['output'] : '';
            if ($legacyUrl === '' || ! self::pageOutput($output)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PREVIEW_SOURCE_ROUTE_INVALID',
                    'A declarative preview source route is invalid.',
                );
            }
            $localizedOutput = $outputPrefix !== '' && str_starts_with($output, $outputPrefix . '/')
                ? substr($output, strlen($outputPrefix) + 1)
                : $output;
            $relative = $localizedOutput === 'index.html'
                ? ''
                : substr($localizedOutput, 0, -strlen('index.html'));
            $urls[$legacyUrl] = rtrim((string) $homeUrl, '/')
                . '/' . self::OUTPUT_ROOT . '/pages/' . $relative;
            $outputs[$legacyUrl] = $outputRoot . '/pages/' . $localizedOutput;
        }
        if (! is_string($homeUrl) || ! self::safeUrl($homeUrl)) {
            throw new PortableConfigurationException(
                'DECLARATIVE_PREVIEW_HOME_URL_REQUIRED',
                'Declarative preview requires a safe site home URL.',
            );
        }
        ksort($urls, SORT_STRING);
        $orderedOutputs = [];
        foreach (array_keys($urls) as $legacyUrl) {
            $orderedOutputs[$legacyUrl] = $outputs[$legacyUrl];
        }
        $base = rtrim($homeUrl, '/') . '/' . self::OUTPUT_ROOT . '/';

        return new self(
            $base,
            $outputRoot . '/index.html',
            $base . 'index.json',
            $outputRoot . '/index.json',
            $urls,
            $orderedOutputs,
        );
    }

    public function previewUrl(string $legacyUrl): ?string
    {
        return $this->urlsByLegacyUrl[$legacyUrl] ?? null;
    }

    public function previewOutput(string $legacyUrl): ?string
    {
        return $this->outputsByLegacyUrl[$legacyUrl] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'index_url' => $this->indexUrl,
            'index_output' => $this->indexOutput,
            'receipt_url' => $this->receiptUrl,
            'receipt_output' => $this->receiptOutput,
            'pages' => array_map(
                fn (string $url, string $legacyUrl): array => [
                    'legacy_url' => $legacyUrl,
                    'preview_url' => $url,
                    'preview_output' => $this->outputsByLegacyUrl[$legacyUrl],
                ],
                $this->urlsByLegacyUrl,
                array_keys($this->urlsByLegacyUrl),
            ),
        ];
    }

    private static function pageOutput(string $output): bool
    {
        return self::safeOutput($output)
            && ($output === 'index.html' || str_ends_with($output, '/index.html'));
    }

    private static function safeOutput(string $output): bool
    {
        return $output !== ''
            && ! str_starts_with($output, '/')
            && ! str_contains($output, '\\')
            && ! str_contains($output, "\0")
            && preg_match('#^(?:[A-Za-z0-9._-]+/)*[A-Za-z0-9._-]+$#D', $output) === 1
            && ! in_array('.', explode('/', $output), true)
            && ! in_array('..', explode('/', $output), true);
    }

    private static function safeUrl(string $url): bool
    {
        return $url !== ''
            && str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && preg_match('/[\x00-\x20"\'<>\\\\]/', $url) !== 1;
    }
}
