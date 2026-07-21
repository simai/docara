<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use JsonException;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortableRedirectPublisher
{
    public function __construct(private Filesystem $files) {}

    /**
     * @param  array<string, mixed>  $site
     * @param  list<array<string, mixed>>  $pages
     * @param  list<array{source: string, relative: string}>  $contentAssets
     * @return array{
     *     receipt: array<string, mixed>|null,
     *     pages: list<array{output: string, bytes: string}>
     * }
     */
    public function plan(
        string $root,
        array $site,
        array $pages,
        array $contentAssets,
        string $locale,
        string $documentationVersion,
        array $copy,
        string $direction,
    ): array {
        $source = $site['redirects_file'] ?? null;
        if ($source === null) {
            return ['receipt' => null, 'pages' => []];
        }
        if (! is_string($source)) {
            throw new PortableConfigurationException(
                'PORTABLE_REDIRECT_SOURCE_INVALID',
                'redirects_file must be a safe relative path.',
            );
        }

        $sourceBytes = $this->source($root, $source);
        try {
            $document = json_decode($sourceBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'PORTABLE_REDIRECT_JSON_INVALID',
                "Redirect source [$source] is not valid JSON.",
                $exception,
            );
        }
        (new SchemaRepository)->assertValid($document, 'redirects.schema.json');
        if (! is_array($document)) {
            throw new PortableConfigurationException(
                'PORTABLE_REDIRECT_DOCUMENT_INVALID',
                "Redirect source [$source] must contain an object.",
            );
        }

        $baseUrl = $this->deploymentBase((string) ($site['base_url'] ?? '/'));
        $pageOutputs = [];
        $pageUrls = [];
        foreach ($pages as $page) {
            $output = $page['output'] ?? null;
            $url = $page['url'] ?? null;
            if (! is_string($output) || ! is_string($url)) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_PAGE_IDENTITY_INVALID',
                    'Generated pages must expose exact output and URL identities before redirects are planned.',
                );
            }
            $pageOutputs[$output] = true;
            $pageUrls[$output] = $url;
        }

        $assetOutputs = [];
        foreach ($contentAssets as $asset) {
            $relative = $asset['relative'] ?? null;
            if (is_string($relative)) {
                $assetOutputs[strtolower($relative)] = true;
            }
        }

        $configured = $document['redirects'] ?? [];
        if (! is_array($configured) || ! array_is_list($configured)) {
            throw new PortableConfigurationException(
                'PORTABLE_REDIRECT_DOCUMENT_INVALID',
                "Redirect source [$source] must contain a redirect list.",
            );
        }
        $fromSet = [];
        foreach ($configured as $index => $redirect) {
            if (! is_array($redirect)) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_RECORD_INVALID',
                    "Redirect record [$index] must contain an object.",
                );
            }
            $from = (string) ($redirect['from'] ?? '');
            if (isset($fromSet[$from])) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_SOURCE_DUPLICATED',
                    "Redirect source route [$from] is duplicated.",
                );
            }
            $fromSet[$from] = true;
        }
        usort(
            $configured,
            static fn (array $left, array $right): int => [
                (string) ($left['from'] ?? ''),
                (string) ($left['to'] ?? ''),
            ] <=> [
                (string) ($right['from'] ?? ''),
                (string) ($right['to'] ?? ''),
            ],
        );
        $canonicalSource = $document;
        $canonicalSource['redirects'] = $configured;

        $records = [];
        foreach ($configured as $redirect) {
            $from = (string) $redirect['from'];
            $to = (string) $redirect['to'];
            if ($from === $to || isset($fromSet[$to])) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_CHAIN_FORBIDDEN',
                    "Redirect [$from] targets another redirect source [$to].",
                );
            }

            $output = $from . '/index.html';
            $targetOutput = $to === '' ? 'index.html' : $to . '/index.html';
            if (! isset($pageOutputs[$targetOutput])) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_TARGET_NOT_FOUND',
                    "Redirect [$from] target [$to] is not an exact generated page route.",
                );
            }
            $targetUrl = $to === '' ? $baseUrl : $baseUrl . $to . '/';
            if (($pageUrls[$targetOutput] ?? null) !== $targetUrl) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_TARGET_IDENTITY_INVALID',
                    "Redirect [$from] target [$to] does not match one exact generated page URL and output.",
                );
            }
            if (isset($pageOutputs[$output])) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_PAGE_COLLISION',
                    "Redirect [$from] collides with generated page output [$output].",
                );
            }
            if ($this->collidesWithAssets($output, $assetOutputs)) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_ASSET_COLLISION',
                    "Redirect [$from] collides with a content asset.",
                );
            }

            $records[] = [
                'from' => $from,
                'to' => $to,
                'url' => $baseUrl . $from . '/',
                'target_url' => $targetUrl,
                'output' => $output,
            ];
        }
        usort(
            $records,
            static fn (array $left, array $right): int => strcmp($left['from'], $right['from']),
        );

        $receipt = [
            'schema' => 'docara.redirect_receipt.v1',
            'version' => 1,
            'source' => $source,
            'source_sha256' => hash('sha256', CanonicalJson::encode($canonicalSource)),
            'base_url' => $baseUrl,
            'locale' => $locale,
            'documentation_version' => $documentationVersion,
            'direction' => $direction,
            'copy' => [
                'redirect.title' => (string) ($copy['redirect.title'] ?? ''),
                'redirect.message' => (string) ($copy['redirect.message'] ?? ''),
                'redirect.link' => (string) ($copy['redirect.link'] ?? ''),
            ],
            'content_sha256' => hash('sha256', CanonicalJson::encode($records)),
            'redirects' => $records,
        ];
        (new SchemaRepository)->assertValid($receipt, 'redirect-receipt.schema.json');

        return [
            'receipt' => $receipt,
            'pages' => array_map(
                static fn (array $record): array => [
                    'output' => $record['output'],
                    'bytes' => self::renderPage($record, $locale, $documentationVersion, $copy, $direction),
                ],
                $records,
            ),
        ];
    }

    /**
     * @param  array{from: string, to: string, url: string, target_url: string, output: string}  $record
     */
    public static function renderPage(
        array $record,
        string $locale,
        string $documentationVersion,
        array $copy,
        string $direction,
    ): string {
        $target = self::escape($record['target_url']);
        $lang = self::escape($locale);
        $version = self::escape($documentationVersion);
        $title = (string) ($copy['redirect.title'] ?? 'redirect.title');
        $message = (string) ($copy['redirect.message'] ?? 'redirect.message');
        $link = (string) ($copy['redirect.link'] ?? 'redirect.link');
        $direction = in_array($direction, ['ltr', 'rtl'], true) ? $direction : 'ltr';

        return '<!doctype html>' . "\n"
            . '<html lang="' . $lang . '" dir="' . $direction . '" data-docara-documentation-version="' . $version . '">' . "\n"
            . '<head>' . "\n"
            . '<meta charset="utf-8">' . "\n"
            . '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
            . '<meta name="robots" content="noindex,follow">' . "\n"
            . '<meta name="docara:documentation-version" content="' . $version . '">' . "\n"
            . '<link rel="canonical" href="' . $target . '">' . "\n"
            . '<meta http-equiv="refresh" content="0; url=' . $target . '">' . "\n"
            . '<title>' . self::escape($title) . '</title>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . '<main>' . "\n"
            . '<h1>' . self::escape($title) . '</h1>' . "\n"
            . '<p>' . self::escape($message) . ' <a href="' . $target . '">'
            . self::escape($link) . '</a>.</p>' . "\n"
            . '</main>' . "\n"
            . '</body>' . "\n"
            . '</html>' . "\n";
    }

    /**
     * @param  array{
     *     receipt: array<string, mixed>|null,
     *     pages: list<array{output: string, bytes: string}>
     * }  $plan
     */
    public function publish(array $plan, string $destination): void
    {
        $receipt = $plan['receipt'];
        if ($receipt === null) {
            return;
        }

        foreach ($plan['pages'] as $page) {
            $target = rtrim($destination, '/\\') . '/' . $page['output'];
            $this->files->ensureDirectoryExists(dirname($target));
            if ($this->files->put($target, $page['bytes']) === false
                || ! hash_equals(hash('sha256', $page['bytes']), (string) hash_file('sha256', $target))
            ) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_PUBLICATION_FAILED',
                    "Redirect output [{$page['output']}] could not be published deterministically.",
                );
            }
        }

        $receiptPath = rtrim($destination, '/\\') . '/.docara/redirects.json';
        $this->files->ensureDirectoryExists(dirname($receiptPath));
        $bytes = CanonicalJson::encodePretty($receipt);
        if ($this->files->put($receiptPath, $bytes) === false
            || ! hash_equals(hash('sha256', $bytes), (string) hash_file('sha256', $receiptPath))
        ) {
            throw new PortableConfigurationException(
                'PORTABLE_REDIRECT_RECEIPT_PUBLICATION_FAILED',
                'The redirect receipt could not be published deterministically.',
            );
        }
    }

    private function source(string $root, string $relative): string
    {
        $candidate = $root;
        foreach (explode('/', $relative) as $segment) {
            $candidate .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($candidate)) {
                throw new PortableConfigurationException(
                    'PORTABLE_REDIRECT_SOURCE_SYMLINK_FORBIDDEN',
                    "Redirect source [$relative] traverses a symbolic link.",
                );
            }
        }
        $real = realpath($candidate);
        $stat = @lstat($candidate);
        if ($real === false
            || ! is_file($real)
            || ! FilesystemPath::isWithin($real, $root, false)
            || ! is_array($stat)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 0) !== 1
        ) {
            throw new PortableConfigurationException(
                'PORTABLE_REDIRECT_SOURCE_UNSAFE',
                "Redirect source [$relative] is missing or unsafe.",
            );
        }
        $bytes = file_get_contents($real);
        if (! is_string($bytes)) {
            throw new PortableConfigurationException(
                'PORTABLE_REDIRECT_SOURCE_READ_FAILED',
                "Redirect source [$relative] could not be read.",
            );
        }

        return $bytes;
    }

    /** @param array<string, true> $assetOutputs */
    private function collidesWithAssets(string $output, array $assetOutputs): bool
    {
        $output = strtolower($output);
        foreach (array_keys($assetOutputs) as $asset) {
            if ($asset === $output
                || str_starts_with($asset, $output . '/')
                || str_starts_with($output, $asset . '/')
            ) {
                return true;
            }
        }

        return false;
    }

    private function deploymentBase(string $baseUrl): string
    {
        return $baseUrl === '/' ? '/' : '/' . trim($baseUrl, '/') . '/';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
