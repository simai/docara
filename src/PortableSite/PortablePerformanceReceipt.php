<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortablePerformanceReceipt
{
    public function __construct(private Filesystem $files) {}

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, mixed>
     */
    public function publish(string $destination, string $baseUrl, array $pages): array
    {
        $receipt = $this->build($destination, $baseUrl, $pages);
        $path = FilesystemPath::normalize($destination) . '/.docara/performance.json';
        $this->files->ensureDirectoryExists(dirname($path));
        $bytes = CanonicalJson::encodePretty($receipt);
        if ($this->files->put($path, $bytes) === false
            || ! hash_equals(hash('sha256', $bytes), (string) hash_file('sha256', $path))
        ) {
            throw new PortableConfigurationException(
                'PORTABLE_PERFORMANCE_RECEIPT_WRITE_FAILED',
                'The performance receipt could not be published deterministically.',
            );
        }

        return $receipt;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, mixed>
     */
    public function build(string $destination, string $baseUrl, array $pages): array
    {
        $destination = FilesystemPath::normalize($destination);
        $deploymentBase = $this->deploymentBase($baseUrl);
        $records = [];
        $siteResources = [];
        $sizeCache = [];

        foreach ($pages as $page) {
            $output = $page['output'] ?? null;
            $url = $page['url'] ?? null;
            if (! is_string($output) || ! is_string($url) || $output === '' || $url === '') {
                throw new PortableConfigurationException(
                    'PORTABLE_PERFORMANCE_PAGE_INVALID',
                    'Performance reporting requires exact page output and URL identities.',
                );
            }
            $pagePath = $destination . '/' . $output;
            $html = $this->safeBytes($destination, $pagePath, 'PORTABLE_PERFORMANCE_PAGE_UNSAFE');
            $resources = $this->resources($destination, $deploymentBase, $output, $html, $sizeCache);
            foreach ($resources as $resource) {
                if (($resource['local'] ?? false) === true) {
                    $siteResources[(string) $resource['output']] = $resource;
                }
            }
            $inline = $this->inlineBytes($html);
            $records[] = [
                'output' => $output,
                'url' => $url,
                'html_bytes' => strlen($html),
                'initial_requests' => count($resources),
                'initial_local_bytes' => array_sum(array_column(
                    array_values(array_filter($resources, static fn (array $resource): bool => $resource['local'] === true)),
                    'bytes',
                )),
                'inline_css_bytes' => $inline['css'],
                'inline_javascript_bytes' => $inline['javascript'],
                'resources' => $resources,
            ];
        }

        usort($records, static fn (array $left, array $right): int => strcmp($left['output'], $right['output']));
        ksort($siteResources, SORT_STRING);
        $siteResources = array_values($siteResources);
        $core = [
            'schema' => 'docara.performance_receipt.v1',
            'version' => 1,
            'pages' => $records,
            'site' => [
                'page_count' => count($records),
                'unique_initial_local_resources' => count($siteResources),
                'unique_initial_local_bytes' => array_sum(array_column($siteResources, 'bytes')),
                'largest_initial_local_resources' => $this->largest($siteResources),
            ],
        ];
        $receipt = [
            ...$core,
            'content_sha256' => hash('sha256', CanonicalJson::encode($core)),
        ];
        (new SchemaRepository)->assertValid($receipt, 'performance-receipt.schema.json');

        return $receipt;
    }

    /**
     * @param  array<string, array{bytes:int,sha256:string}>  $sizeCache
     * @return list<array<string, mixed>>
     */
    private function resources(
        string $destination,
        string $deploymentBase,
        string $pageOutput,
        string $html,
        array &$sizeCache,
    ): array {
        $document = $this->document($html);
        $xpath = new DOMXPath($document);
        $discovered = [];
        foreach ([
            ['//link[contains(concat(" ", normalize-space(@rel), " "), " stylesheet ")][@href]', 'href', 'css'],
            ['//link[contains(concat(" ", normalize-space(@rel), " "), " preload ")][@as="font"][@href]', 'href', 'font'],
            ['//script[@src]', 'src', 'javascript'],
        ] as [$query, $attribute, $kind]) {
            foreach ($xpath->query($query) ?: [] as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }
                $url = html_entity_decode(trim($node->getAttribute($attribute)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($url === '') {
                    continue;
                }
                $key = $url;
                $discovered[$key] ??= ['url' => $url, 'kinds' => []];
                $discovered[$key]['kinds'][$kind] = true;
            }
        }

        ksort($discovered, SORT_STRING);
        $resources = [];
        foreach ($discovered as $record) {
            $url = $record['url'];
            $kinds = array_keys($record['kinds']);
            sort($kinds, SORT_STRING);
            $relative = $this->localOutput($url, $deploymentBase, $pageOutput);
            if ($relative === null) {
                $resources[] = [
                    'url' => $url,
                    'kinds' => $kinds,
                    'local' => false,
                ];

                continue;
            }
            $target = $destination . '/' . $relative;
            if (! isset($sizeCache[$relative])) {
                $bytes = $this->safeBytes($destination, $target, 'PORTABLE_PERFORMANCE_RESOURCE_UNSAFE');
                $sizeCache[$relative] = [
                    'bytes' => strlen($bytes),
                    'sha256' => hash('sha256', $bytes),
                ];
            }
            $resources[] = [
                'url' => $url,
                'kinds' => $kinds,
                'local' => true,
                'output' => $relative,
                ...$sizeCache[$relative],
            ];
        }

        return $resources;
    }

    /** @return array{css:int,javascript:int} */
    private function inlineBytes(string $html): array
    {
        $xpath = new DOMXPath($this->document($html));
        $css = 0;
        $javascript = 0;
        foreach ($xpath->query('//style') ?: [] as $node) {
            $css += strlen((string) $node->textContent);
        }
        foreach ($xpath->query('//script[not(@src)]') ?: [] as $node) {
            $javascript += strlen((string) $node->textContent);
        }

        return ['css' => $css, 'javascript' => $javascript];
    }

    private function document(string $html): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new PortableConfigurationException(
                'PORTABLE_PERFORMANCE_HTML_INVALID',
                'A generated page could not be inspected for its initial resources.',
            );
        }

        return $document;
    }

    private function localOutput(string $url, string $deploymentBase, string $pageOutput): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || isset($parts['host']) || isset($parts['scheme']) || str_starts_with($url, '//')) {
            return null;
        }
        $path = rawurldecode((string) ($parts['path'] ?? ''));
        if ($path === '' || str_contains($path, "\0") || preg_match('//u', $path) !== 1) {
            throw new PortableConfigurationException('PORTABLE_PERFORMANCE_RESOURCE_URL_INVALID', $url);
        }
        if (str_starts_with($path, '/')) {
            if ($deploymentBase !== '/' && ! str_starts_with($path, $deploymentBase)) {
                throw new PortableConfigurationException('PORTABLE_PERFORMANCE_RESOURCE_OUTSIDE_BASE', $url);
            }
            $candidate = $deploymentBase === '/'
                ? ltrim($path, '/')
                : ltrim(substr($path, strlen($deploymentBase)), '/');
        } else {
            $candidate = dirname($pageOutput) . '/' . $path;
        }

        return $this->normalizeRelative($candidate, $url);
    }

    private function normalizeRelative(string $path, string $source): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    throw new PortableConfigurationException('PORTABLE_PERFORMANCE_RESOURCE_TRAVERSAL', $source);
                }
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }
        if ($segments === []) {
            throw new PortableConfigurationException('PORTABLE_PERFORMANCE_RESOURCE_URL_INVALID', $source);
        }

        return implode('/', $segments);
    }

    private function safeBytes(string $root, string $path, string $code): string
    {
        $stat = @lstat($path);
        $realRoot = realpath($root);
        $real = realpath($path);
        if (! is_array($stat)
            || is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || $realRoot === false
            || $real === false
            || ! FilesystemPath::isWithin($real, $realRoot)
        ) {
            throw new PortableConfigurationException($code, $path);
        }
        $bytes = @file_get_contents($real);
        if (! is_string($bytes)) {
            throw new PortableConfigurationException($code, $path);
        }

        return $bytes;
    }

    /**
     * @param  list<array<string, mixed>>  $resources
     * @return list<array{output:string,bytes:int,sha256:string}>
     */
    private function largest(array $resources): array
    {
        usort($resources, static fn (array $left, array $right): int => ($right['bytes'] <=> $left['bytes']) ?: strcmp($left['output'], $right['output']));

        return array_map(
            static fn (array $resource): array => [
                'output' => $resource['output'],
                'bytes' => $resource['bytes'],
                'sha256' => $resource['sha256'],
            ],
            array_slice($resources, 0, 10),
        );
    }

    private function deploymentBase(string $baseUrl): string
    {
        $base = trim($baseUrl, '/');

        return $base === '' ? '/' : '/' . $base . '/';
    }
}
