<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;

final class ProjectExampleRepository
{
    private const SOURCE_LIMIT = 1048576;

    private const ASSET_LIMIT = 10485760;

    private const TOTAL_ASSET_LIMIT = 52428800;

    /** @var list<string> */
    private const ASSET_EXTENSIONS = [
        'avif', 'css', 'gif', 'ico', 'jpeg', 'jpg', 'js', 'json', 'mp3', 'mp4',
        'otf', 'pdf', 'png', 'svg', 'ttf', 'txt', 'wav', 'webm', 'webmanifest',
        'webp', 'woff', 'woff2',
    ];

    /** @var array<string,array<string,mixed>> */
    private array $examples = [];

    /** @var array<string,array<string,true>> */
    private array $consumers = [];

    /** @var array<string,array<string,mixed>> */
    private array $previews = [];

    public function __construct(
        private readonly string $root,
        private readonly string $baseUrl = '/',
    ) {}

    /** @return array{sources:array<string,string>,base_href:string} */
    public function load(string $id, ?string $consumer = null): array
    {
        $id = $this->assertId($id);
        $record = $this->examples[$id] ??= $this->inspect($id);
        if (is_string($consumer) && $consumer !== '') {
            $relative = str_replace('\\', '/', $consumer);
            $prefix = rtrim(FilesystemPath::normalize($this->root), '/') . '/';
            if (str_starts_with(FilesystemPath::normalize($consumer), $prefix)) {
                $relative = substr(FilesystemPath::normalize($consumer), strlen($prefix));
            }
            $this->consumers[$id][$relative] = true;
        }

        return [
            'sources' => $record['source_contents'],
            'base_href' => $this->publicBase($id),
        ];
    }

    /** @return list<array{source:string,relative:string}> */
    public function publishedAssets(): array
    {
        $assets = [];
        foreach (array_keys($this->consumers) as $id) {
            $record = $this->examples[$id] ??= $this->inspect($id);
            foreach ($record['assets'] as $asset) {
                $assets[] = [
                    'source' => $asset['absolute'],
                    'relative' => '_docara/examples/' . $id . '/' . $asset['path'],
                ];
            }
        }
        usort($assets, static fn (array $left, array $right): int => strcmp($left['relative'], $right['relative']));

        return $assets;
    }

    public function recordPreview(
        string $id,
        string $consumer,
        string $requested,
        string $resolved,
        string $reason,
        string $sourceSha256,
    ): void {
        $consumer = $this->relativeConsumer($consumer);
        $key = $consumer . "\0" . $id;
        $this->previews[$key] = [
            'id' => $id,
            'consumer' => $consumer,
            'requested_preview' => $requested,
            'resolved_preview' => $resolved,
            'reason' => $reason,
            'source_sha256' => $sourceSha256,
        ];
    }

    /** @param array<string,mixed>|null $existing */
    public function receipt(?array $existing = null, ?string $replacedConsumer = null): array
    {
        $consumerMap = [];
        foreach (($existing['examples'] ?? []) as $record) {
            if (! is_array($record) || ! is_string($record['id'] ?? null)) {
                continue;
            }
            foreach (($record['consumers'] ?? []) as $consumer) {
                if (is_string($consumer) && $consumer !== $replacedConsumer) {
                    $consumerMap[$record['id']][$consumer] = true;
                }
            }
        }
        foreach ($this->consumers as $id => $consumers) {
            foreach ($consumers as $consumer => $_used) {
                $consumerMap[$id][$consumer] = true;
            }
        }

        $previews = [];
        foreach (($existing['previews'] ?? []) as $preview) {
            if (! is_array($preview)
                || ! is_string($preview['id'] ?? null)
                || ! is_string($preview['consumer'] ?? null)
                || $preview['consumer'] === $replacedConsumer
            ) {
                continue;
            }
            $previews[$preview['consumer'] . "\0" . $preview['id']] = $preview;
        }
        foreach ($this->previews as $key => $preview) {
            $previews[$key] = $preview;
        }
        ksort($previews, SORT_STRING);

        $ids = array_values(array_unique([...array_keys($consumerMap), ...array_keys($this->consumers)]));
        sort($ids, SORT_STRING);
        $records = [];
        foreach ($ids as $id) {
            $example = $this->examples[$id] ??= $this->inspect($id);
            $consumers = array_keys($consumerMap[$id] ?? []);
            sort($consumers, SORT_STRING);
            if ($consumers === []) {
                continue;
            }
            $records[] = [
                'id' => $id,
                'content_sha256' => $example['content_sha256'],
                'sources' => $example['sources'],
                'assets' => array_map(
                    static fn (array $asset): array => [
                        'path' => $asset['path'],
                        'output' => '_docara/examples/' . $id . '/' . $asset['path'],
                        'size' => $asset['size'],
                        'sha256' => $asset['sha256'],
                    ],
                    $example['assets'],
                ),
                'consumers' => $consumers,
            ];
        }
        $core = [
            'schema' => 'docara.example_receipt.v1',
            'examples' => $records,
            'previews' => array_values($previews),
        ];

        return $core + ['content_sha256' => hash('sha256', CanonicalJson::encode($core))];
    }

    /** @param array<string,mixed> $existing */
    public function assertIncrementalCompatible(array $existing): void
    {
        foreach (($existing['examples'] ?? []) as $record) {
            if (! is_array($record) || ! is_string($record['id'] ?? null) || ! is_string($record['content_sha256'] ?? null)) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_EXAMPLE_BASE_INVALID',
                    'The complete build has no valid project example receipt. Run a full build.',
                );
            }
            $current = $this->examples[$record['id']] ??= $this->inspect($record['id']);
            if (! hash_equals($record['content_sha256'], $current['content_sha256'])) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_EXAMPLE_CHANGED',
                    "Project example [{$record['id']}] changed after the complete build. Run a full build.",
                );
            }
        }
    }

    private function assertId(string $id): string
    {
        $id = trim($id);
        if (preg_match('#\A[a-z0-9][a-z0-9._-]*(?:/[a-z0-9][a-z0-9._-]*)*\z#D', $id) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_ID_INVALID',
                'Example id must be a lowercase slash-separated path inside examples/.',
            );
        }

        return $id;
    }

    private function relativeConsumer(string $consumer): string
    {
        $relative = str_replace('\\', '/', $consumer);
        $prefix = rtrim(FilesystemPath::normalize($this->root), '/') . '/';
        if (str_starts_with(FilesystemPath::normalize($consumer), $prefix)) {
            $relative = substr(FilesystemPath::normalize($consumer), strlen($prefix));
        }

        return $relative;
    }

    /** @return array<string,mixed> */
    private function inspect(string $id): array
    {
        $examplesRoot = rtrim($this->root, '/\\') . '/examples';
        $this->assertDirectory($examplesRoot, 'PROJECT_EXAMPLES_ROOT_MISSING');
        $directory = $examplesRoot;
        foreach (explode('/', $id) as $segment) {
            $this->assertCase($directory, $segment);
            $directory .= '/' . $segment;
            $this->assertDirectory($directory, 'PROJECT_EXAMPLE_NOT_FOUND');
        }
        $realRoot = FilesystemPath::normalize((string) realpath($examplesRoot));
        $realDirectory = FilesystemPath::normalize((string) realpath($directory));
        if (! FilesystemPath::isWithin($realDirectory, $realRoot)) {
            throw new PortableConfigurationException('PROJECT_EXAMPLE_PATH_ESCAPE', "Example [$id] escapes examples/.");
        }
        $this->assertKnownRootEntries($directory);

        $sources = [];
        $sourceContents = [];
        foreach (['index.html' => 'HTML', 'index.css' => 'CSS', 'index.js' => 'JavaScript'] as $name => $language) {
            $path = $directory . '/' . $name;
            if ($name === 'index.html' || file_exists($path) || is_link($path)) {
                $contents = $this->readFile($path, self::SOURCE_LIMIT, true, 'PROJECT_EXAMPLE_SOURCE_INVALID');
                $sources[] = [
                    'path' => 'examples/' . $id . '/' . $name,
                    'language' => $language,
                    'size' => strlen($contents),
                    'sha256' => hash('sha256', $contents),
                ];
                $sourceContents[$language] = rtrim(str_replace(["\r\n", "\r"], "\n", $contents));
            }
        }

        $assets = [];
        $assetRoot = $directory . '/assets';
        if (file_exists($assetRoot) || is_link($assetRoot)) {
            $this->assertDirectory($assetRoot, 'PROJECT_EXAMPLE_ASSET_ROOT_INVALID');
            $total = 0;
            $case = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($assetRoot, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
            );
            foreach ($iterator as $file) {
                if ($file->isLink()) {
                    throw new PortableConfigurationException('PROJECT_EXAMPLE_ASSET_INVALID', "Example asset [{$file->getPathname()}] is unsafe.");
                }
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
                $folded = strtolower($relative);
                if (isset($case[$folded]) && $case[$folded] !== $relative) {
                    throw new PortableConfigurationException('PROJECT_EXAMPLE_CASE_COLLISION', "Example assets [{$case[$folded]}] and [$relative] collide by case.");
                }
                $case[$folded] = $relative;
                if ($file->isDir()) {
                    continue;
                }
                if (! $file->isFile()) {
                    throw new PortableConfigurationException('PROJECT_EXAMPLE_ASSET_INVALID', "Example asset [$relative] is unsafe.");
                }
                $extension = strtolower($file->getExtension());
                if (! in_array($extension, self::ASSET_EXTENSIONS, true)) {
                    throw new PortableConfigurationException('PROJECT_EXAMPLE_ASSET_TYPE_FORBIDDEN', "Example asset [$relative] has an unsupported type.");
                }
                $contents = $this->readFile($file->getPathname(), self::ASSET_LIMIT, in_array($extension, ['css', 'js', 'json', 'svg', 'txt', 'webmanifest'], true), 'PROJECT_EXAMPLE_ASSET_INVALID');
                $total += strlen($contents);
                if ($total > self::TOTAL_ASSET_LIMIT) {
                    throw new PortableConfigurationException('PROJECT_EXAMPLE_ASSET_TOTAL_LIMIT', "Example [$id] assets exceed the 50 MiB limit.");
                }
                $assets[] = [
                    'path' => $relative,
                    'absolute' => $file->getPathname(),
                    'size' => strlen($contents),
                    'sha256' => hash('sha256', $contents),
                ];
            }
            usort($assets, static fn (array $left, array $right): int => strcmp($left['path'], $right['path']));
        }

        $hashInput = ['sources' => $sources, 'assets' => array_map(
            static fn (array $asset): array => array_diff_key($asset, ['absolute' => true]),
            $assets,
        )];

        return [
            'source_contents' => $sourceContents,
            'sources' => $sources,
            'assets' => $assets,
            'content_sha256' => hash('sha256', CanonicalJson::encode($hashInput)),
        ];
    }

    private function readFile(string $path, int $limit, bool $utf8, string $code): string
    {
        $stat = @lstat($path);
        $real = realpath($path);
        if (! is_array($stat) || $real === false || is_link($path) || ! is_file($real)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000 || ($stat['nlink'] ?? 1) !== 1
            || ! FilesystemPath::isWithin(FilesystemPath::normalize($real), FilesystemPath::normalize($this->root))
        ) {
            throw new PortableConfigurationException($code, "Example file [$path] is missing or unsafe.");
        }
        if (($stat['size'] ?? $limit + 1) > $limit) {
            throw new PortableConfigurationException($code, "Example file [$path] exceeds its size limit.");
        }
        $contents = file_get_contents($real);
        if (! is_string($contents) || ($utf8 && preg_match('//u', $contents) !== 1)) {
            throw new PortableConfigurationException($code, "Example file [$path] must contain valid data.");
        }

        return $contents;
    }

    private function assertDirectory(string $path, string $code): void
    {
        $stat = @lstat($path);
        if (! is_array($stat) || is_link($path) || ! is_dir($path) || (($stat['mode'] ?? 0) & 0170000) !== 0040000) {
            throw new PortableConfigurationException($code, "Example directory [$path] is missing or unsafe.");
        }
    }

    private function assertCase(string $parent, string $segment): void
    {
        $entries = scandir($parent);
        if (! is_array($entries)) {
            throw new PortableConfigurationException('PROJECT_EXAMPLE_PATH_INVALID', "Example parent [$parent] cannot be inspected.");
        }
        foreach ($entries as $entry) {
            if ($entry !== $segment && strcasecmp($entry, $segment) === 0) {
                throw new PortableConfigurationException('PROJECT_EXAMPLE_CASE_COLLISION', "Example path [$segment] collides with [$entry].");
            }
        }
    }

    private function assertKnownRootEntries(string $directory): void
    {
        $entries = scandir($directory);
        if (! is_array($entries)) {
            throw new PortableConfigurationException('PROJECT_EXAMPLE_PATH_INVALID', "Example directory [$directory] cannot be inspected.");
        }
        $allowed = ['index.html', 'index.css', 'index.js', 'assets'];
        $case = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $folded = strtolower($entry);
            if (isset($case[$folded]) && $case[$folded] !== $entry) {
                throw new PortableConfigurationException('PROJECT_EXAMPLE_CASE_COLLISION', "Example entries [{$case[$folded]}] and [$entry] collide by case.");
            }
            $case[$folded] = $entry;
            if (! in_array($entry, $allowed, true)) {
                throw new PortableConfigurationException('PROJECT_EXAMPLE_FILE_FORBIDDEN', "Example entry [$entry] is not part of the project example contract.");
            }
        }
    }

    private function publicBase(string $id): string
    {
        $base = rtrim($this->baseUrl, '/');

        return ($base === '' ? '' : $base) . '/_docara/examples/' . $id . '/';
    }
}
