<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

use DOMDocument;
use DOMElement;
use DOMXPath;
use JsonException;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableSiteBuilder;

final readonly class PreviewKernel
{
    public function __construct(
        private PortableSiteBuilder $builder,
        private Filesystem $files,
    ) {}

    public function render(
        string $projectRoot,
        string $page,
        PreviewTarget $target,
        ?string $selector = null,
    ): PreviewArtifact {
        $root = $this->realRoot($projectRoot);
        $page = $this->page($page);
        $selector = $this->selector($target, $selector);
        $cache = $root . '/build_preview-cache';
        $receipt = $cache . '/.docara/resolved-page-plans.json';
        if (! is_file($receipt)) {
            $this->builder->build($root, $cache);
            $this->files->put($cache . '/.docara-preview-cache.json', '{"accepted_build_receipt":false,"purpose":"preview-cache"}');
        } else {
            $this->builder->build($root, $cache, $page);
        }

        $record = $this->record($receipt, $page);
        $output = $record['output'] ?? null;
        if (! is_string($output) || ! $this->safeRelative($output)) {
            throw new PortableConfigurationException(
                'PREVIEW_PAGE_OUTPUT_INVALID',
                "Preview page [$page] has no safe production output.",
            );
        }
        $htmlPath = $cache . '/' . $output;
        $html = is_file($htmlPath) ? file_get_contents($htmlPath) : false;
        if (! is_string($html)) {
            throw new PortableConfigurationException('PREVIEW_PAGE_MISSING', "Preview page [$page] was not built.");
        }

        $artifactHtml = $target === PreviewTarget::Page
            ? $html
            : $this->extract($html, $target, $selector);
        $assets = $record['declarative_pipeline']['assets'] ?? [];
        if (! is_array($assets) || ! array_is_list($assets) || count(array_filter($assets, 'is_string')) !== count($assets)) {
            throw new PortableConfigurationException('PREVIEW_ASSET_PROVENANCE_INVALID', 'Preview asset provenance is invalid.');
        }
        sort($assets, SORT_STRING);

        return new PreviewArtifact(
            $target,
            $page,
            $selector,
            $artifactHtml,
            $assets,
            $this->dependencies($root, $record),
            [
                'runtime' => 'portable_site_builder',
                'renderer_path' => 'markdown>typed-ir>registry>gateway>layout-composer>page-builder',
                'production_output' => $output,
                'plan_hash' => $record['declarative_pipeline']['plan_hash'] ?? null,
                'source_kind' => $record['page_source_kind'] ?? null,
            ],
        );
    }

    private function realRoot(string $root): string
    {
        $real = realpath($root);
        if ($real === false || ! is_dir($real) || is_link($root)) {
            throw new PortableConfigurationException('PREVIEW_ROOT_INVALID', 'Preview root must be a real project directory.');
        }

        return rtrim($real, '/\\');
    }

    private function page(string $page): string
    {
        $page = '/' . trim($page, '/') . '/';
        if (preg_match('~^/(?:[a-z0-9][a-z0-9._-]*/)*$~D', $page) !== 1 || str_contains($page, '..')) {
            throw new PortableConfigurationException('PREVIEW_PAGE_INVALID', 'Preview page must be a safe public route.');
        }

        return $page;
    }

    private function selector(PreviewTarget $target, ?string $selector): ?string
    {
        if (in_array($target, [PreviewTarget::Page, PreviewTarget::Layout], true)) {
            if ($selector !== null && trim($selector) !== '') {
                throw new PortableConfigurationException('PREVIEW_SELECTOR_FORBIDDEN', "Preview target [{$target->value}] does not accept a selector.");
            }

            return null;
        }
        if (! is_string($selector) || preg_match('/^[a-z][a-z0-9_.-]+$/D', $selector) !== 1) {
            throw new PortableConfigurationException('PREVIEW_SELECTOR_INVALID', "Preview target [{$target->value}] requires a safe selector.");
        }

        return $selector;
    }

    /** @return array<string, mixed> */
    private function record(string $receipt, string $page): array
    {
        try {
            $decoded = json_decode((string) file_get_contents($receipt), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException('PREVIEW_RECEIPT_INVALID', 'Preview production receipt is invalid.', $exception);
        }
        foreach ($decoded['pages'] ?? [] as $record) {
            if (is_array($record) && ($record['url'] ?? null) === $page) {
                return $record;
            }
        }

        throw new PortableConfigurationException('PREVIEW_PAGE_UNKNOWN', "Preview page [$page] is not a public route.");
    }

    private function extract(string $html, PreviewTarget $target, ?string $selector): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($loaded !== true) {
            throw new PortableConfigurationException('PREVIEW_HTML_INVALID', 'Production HTML could not be parsed for preview extraction.');
        }
        $xpath = new DOMXPath($document);
        $query = match ($target) {
            PreviewTarget::Layout => '//body[1]',
            PreviewTarget::Region => '//*[@data-docara-region=' . $this->xpathLiteral((string) $selector) . '][1]',
            PreviewTarget::Smart => '//*[@data-docara-smart=' . $this->xpathLiteral((string) $selector)
                . ' or @data-docara-block=' . $this->xpathLiteral((string) preg_replace('/^.*\./', '', (string) $selector)) . '][1]',
            PreviewTarget::Page => throw new \LogicException('Page extraction is not required.'),
        };
        $node = $xpath->query($query)?->item(0);
        if (! $node instanceof DOMElement) {
            throw new PortableConfigurationException(
                'PREVIEW_TARGET_NOT_FOUND',
                "Preview target [{$target->value}:{$selector}] does not exist on the production page.",
            );
        }

        return (string) $document->saveHTML($node);
    }

    private function xpathLiteral(string $value): string
    {
        return "'" . str_replace("'", '&apos;', $value) . "'";
    }

    /** @param array<string, mixed> $record @return list<string> */
    private function dependencies(string $root, array $record): array
    {
        $dependencies = [];
        foreach ($record['input_chain']['trace'] ?? [] as $trace) {
            $source = is_array($trace) ? ($trace['source'] ?? null) : null;
            if (is_string($source) && $this->safeRelative($source) && is_file($root . '/' . $source)) {
                $dependencies[$source] = true;
            }
        }
        $pagePath = $record['page_path'] ?? null;
        if (is_string($pagePath) && $this->safeRelative($pagePath)) {
            $segments = explode('/', $pagePath);
            if (count($segments) >= 3) {
                $localeRoot = $segments[0] . '/' . $segments[1];
                $lang = $localeRoot . '/lang.json';
                if (is_file($root . '/' . $lang)) {
                    $dependencies[$lang] = true;
                }
                $localeAssets = $localeRoot . '/assets';
                if (is_dir($root . '/' . $localeAssets)) {
                    $this->collectDirectoryDependencies($root, $localeAssets, $dependencies);
                }
            }
        }
        foreach (['design', 'smart', 'assets'] as $directory) {
            $path = $root . '/' . $directory;
            if (! is_dir($path) || is_link($path)) {
                continue;
            }
            $this->collectDirectoryDependencies($root, $directory, $dependencies);
        }
        ksort($dependencies, SORT_STRING);

        return array_keys($dependencies);
    }

    /** @param array<string, bool> $dependencies */
    private function collectDirectoryDependencies(string $root, string $relative, array &$dependencies): void
    {
        $path = $root . '/' . $relative;
        if (is_link($path)) {
            throw new PortableConfigurationException('PREVIEW_DEPENDENCY_PATH_INVALID', 'Preview dependency root cannot be a symlink.');
        }
        foreach ($this->files->allFiles($path) as $file) {
            $real = $file->getRealPath();
            if ($real === false || is_link($real) || ! str_starts_with($real, $path . DIRECTORY_SEPARATOR)) {
                throw new PortableConfigurationException('PREVIEW_DEPENDENCY_PATH_INVALID', 'Preview dependency escapes its project root.');
            }
            $dependencies[str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($root) + 1))] = true;
        }
    }

    private function safeRelative(string $path): bool
    {
        return $path !== ''
            && ! str_contains($path, "\0")
            && ! str_contains($path, '\\')
            && ! str_starts_with($path, '/')
            && ! in_array('..', explode('/', $path), true);
    }
}
