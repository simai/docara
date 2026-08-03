<?php

declare(strict_types=1);

namespace Simai\Docara\Preview;

use DOMDocument;
use DOMElement;
use DOMXPath;
use JsonException;
use Simai\Docara\File\Filesystem;
use Simai\Docara\File\ProjectFilesystemGuard;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\BuildPurpose;
use Simai\Docara\PortableSite\PortableSiteBuilder;
use Simai\Docara\Smart\SmartRegistry;

final readonly class PreviewKernel
{
    public function __construct(
        private PortableSiteBuilder $builder,
        private Filesystem $files,
        private ProjectFilesystemGuard $writes = new ProjectFilesystemGuard,
    ) {}

    public function render(
        string $projectRoot,
        string $page,
        PreviewTarget $target,
        ?string $selector = null,
    ): PreviewArtifact {
        $root = $this->writes->root($projectRoot);
        $page = $this->page($page);
        $selector = $this->selector($target, $selector);
        $cache = $root . '/build_preview-cache';
        foreach (['build_preview-cache', 'build_preview-cache.docara-candidate', 'build_preview-cache.docara-rollback'] as $generated) {
            $this->writes->directoryPath($root, $generated);
        }
        $receipt = $cache . '/.docara/resolved-page-plans.json';
        $buildMode = is_file($receipt) ? 'single_page' : 'full_site';
        if (! is_file($receipt)) {
            $this->builder->build($root, $cache, purpose: BuildPurpose::Preview);
        } else {
            $this->builder->build($root, $cache, $page, BuildPurpose::Preview);
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
            $html,
            $assets,
            $this->dependencies($root, $record, $html, $target, $selector),
            [
                'runtime' => 'portable_site_builder',
                'renderer_path' => 'markdown>typed-ir>registry>gateway>layout-composer>page-builder',
                'production_output' => $output,
                'plan_hash' => $record['declarative_pipeline']['plan_hash'] ?? null,
                'source_kind' => $record['page_source_kind'] ?? null,
                'layout_id' => $record['resolved_page_plan']['configuration']['layout']['key'] ?? null,
                'build_mode' => $buildMode,
                'dependency_scope' => 'selected_target',
            ],
            $cache,
        );
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
    private function dependencies(
        string $root,
        array $record,
        string $html,
        PreviewTarget $target,
        ?string $selector,
    ): array {
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
            }
        }

        $this->collectDesignDependencies($root, $record, $dependencies);
        $smartIds = $this->smartIds($root, $record, $html);
        if ($target === PreviewTarget::Smart && is_string($selector)) {
            $smartIds[] = $selector;
        }
        foreach (array_values(array_unique($smartIds)) as $smartId) {
            $project = 'smart/' . $smartId;
            $package = 'resources/smart/' . $smartId;
            if (is_dir($root . '/' . $project)) {
                $dependencies['@project-tree:' . $project] = true;
            } elseif (is_dir(dirname(__DIR__, 2) . '/' . $package)) {
                $dependencies['@package-tree:' . $package] = true;
            }
            $frameworkManifest = 'resources/framework/manifests/' . str_replace('.', '-', $smartId) . '.json';
            if (is_file(dirname(__DIR__, 2) . '/' . $frameworkManifest)) {
                $dependencies['@package-file:' . $frameworkManifest] = true;
            }
        }

        foreach (['resources/publisher', 'resources/schemas'] as $packageTree) {
            $dependencies['@package-tree:' . $packageTree] = true;
        }
        foreach (['src/Declarative', 'src/Design', 'src/PortableSite', 'src/Preview', 'src/Smart'] as $packageTree) {
            $dependencies['@package-tree:' . $packageTree] = true;
        }
        foreach (['declarative-shell.css', 'declarative-shell.js', 'search.js'] as $runtimeFile) {
            if (str_contains($html, '/' . $runtimeFile)) {
                $dependencies['@package-file:resources/portable/' . $runtimeFile] = true;
            }
        }
        if (str_contains($html, '/_docara/framework/')) {
            $dependencies['@package-tree:resources/framework/assets'] = true;
            $dependencies['@package-file:resources/framework/runtime-lock.json'] = true;
        }
        ksort($dependencies, SORT_STRING);

        return array_keys($dependencies);
    }

    /** @param array<string, bool> $dependencies @param array<string, mixed> $record */
    private function collectDesignDependencies(string $root, array $record, array &$dependencies): void
    {
        $configuration = $record['resolved_page_plan']['configuration'] ?? null;
        $layout = is_array($configuration) ? ($configuration['layout'] ?? null) : null;
        $layoutId = is_array($layout) ? ($layout['key'] ?? null) : null;
        if (! is_string($layoutId)) {
            return;
        }
        $layoutDefinition = $this->addDesignArtifact($root, 'layouts', $layoutId, $dependencies);
        $this->addDesignArtifact($root, 'views', 'layout.' . $layoutId, $dependencies);
        $sectionIds = [];
        foreach (($layout['regions'] ?? []) as $region) {
            foreach (is_array($region) ? ($region['sections'] ?? []) : [] as $section) {
                if (is_array($section) && is_string($section['section'] ?? null)) {
                    $sectionIds[$section['section']] = true;
                }
            }
        }
        if (is_array($layoutDefinition)) {
            $documentSection = $layoutDefinition['document']['section'] ?? null;
            if (is_string($documentSection)) {
                $sectionIds[$documentSection] = true;
            }
            foreach ($layoutDefinition['regions'] ?? [] as $region) {
                foreach (is_array($region) ? ($region['default_sections'] ?? []) : [] as $section) {
                    if (is_array($section) && is_string($section['section'] ?? null)) {
                        $sectionIds[$section['section']] = true;
                    }
                }
            }
        }
        foreach (array_keys($sectionIds) as $sectionId) {
            $section = $this->addDesignArtifact($root, 'sections', $sectionId, $dependencies);
            $this->addDesignArtifact($root, 'views', 'section.' . $sectionId, $dependencies);
            foreach (is_array($section) ? ($section['allowed_blocks'] ?? []) : [] as $blockId) {
                if (is_string($blockId)) {
                    $this->addDesignArtifact($root, 'blocks', $blockId, $dependencies);
                }
            }
        }
    }

    /** @param array<string, bool> $dependencies @return array<string, mixed>|null */
    private function addDesignArtifact(string $root, string $kind, string $id, array &$dependencies): ?array
    {
        foreach ([
            ['owner' => 'project', 'relative' => 'design/' . $kind . '/' . $id . '.json', 'base' => $root],
            ['owner' => 'package', 'relative' => 'resources/' . $kind . '/' . $id . '.json', 'base' => dirname(__DIR__, 2)],
        ] as $candidate) {
            $path = $candidate['base'] . '/' . $candidate['relative'];
            if (! is_file($path) || is_link($path)) {
                continue;
            }
            $key = $candidate['owner'] === 'project'
                ? $candidate['relative']
                : '@package-file:' . $candidate['relative'];
            $dependencies[$key] = true;
            $decoded = json_decode((string) file_get_contents($path), true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    /** @param array<string, mixed> $record @return list<string> */
    private function smartIds(string $root, array $record, string $html): array
    {
        preg_match_all('/\bdata-docara-smart="([a-z][a-z0-9_.-]+)"/', $html, $matches);
        $ids = $matches[1] ?? [];
        $registry = SmartRegistry::bundled();
        foreach ($registry->keys() as $smartId) {
            $definition = $registry->definition($smartId);
            $tag = $definition->portableManifest['frontend']['tag'] ?? null;
            if (! is_string($tag) && is_string($definition->root)) {
                $manifestPath = $definition->root . '/' . ($definition->manifest['path'] ?? '');
                $manifest = is_file($manifestPath)
                    ? json_decode((string) file_get_contents($manifestPath), true)
                    : null;
                $tag = is_array($manifest) ? ($manifest['frontend']['tag'] ?? null) : null;
            }
            if (is_string($tag) && preg_match('/<' . preg_quote($tag, '/') . '\b/i', $html) === 1) {
                $ids[] = $smartId;
            }
            $shortId = (string) preg_replace('/^.*\./', '', $smartId);
            if (preg_match('/\bdata-docara-block="' . preg_quote($shortId, '/') . '"/', $html) === 1) {
                $ids[] = $smartId;
            }
        }
        $source = $record['page_path'] ?? null;
        if (is_string($source) && $this->safeRelative($source) && is_file($root . '/' . $source)) {
            preg_match_all('/^:::\s*([a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*)\b/m', (string) file_get_contents($root . '/' . $source), $sourceMatches);
            array_push($ids, ...($sourceMatches[1] ?? []));
        }
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_STRING);

        return $ids;
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
