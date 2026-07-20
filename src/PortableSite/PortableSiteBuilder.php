<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Illuminate\Support\Collection;
use JsonException;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\Declarative\Adapter\LarenaContractAdapter;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\DeclarativePipeline;
use Simai\Docara\Declarative\Semantic\SemanticParityChecker;
use Simai\Docara\Declarative\Semantic\ShellStructuralParityChecker;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortableSiteBuilder
{
    public function __construct(
        private Filesystem $files,
        private PortableMarkdownRenderer $markdown,
        private PortableHtmlRenderer $html,
    ) {}

    /** @return Collection<string, array<string, mixed>> */
    public function build(string $root, string $destination): Collection
    {
        // Validate the caller's lexical root before realpath normalization so
        // link, link/ and link/. cannot hide the same symbolic-link root.
        $loader = new PortableConfigurationLoader($root);
        $root = $this->realDirectory($root, 'PORTABLE_ROOT_INVALID');
        $site = $this->siteConfiguration($root);
        $buildLocale = (string) ($site['default_locale'] ?? $site['locale'] ?? 'en');
        $documentationVersion = (string) ($site['documentation_version'] ?? 'current');
        $contentRoot = (string) ($site['content_root'] ?? 'content');
        $contentPath = $this->confinedDirectory($root, $contentRoot);
        $this->assertDestinationInputBoundary($root, $destination, $contentPath, $site);
        $pagePaths = $this->markdownFiles($root, $contentPath);
        if ($pagePaths === []) {
            throw new PortableConfigurationException('PORTABLE_CONTENT_EMPTY', 'Portable content does not contain Markdown pages.');
        }

        $pages = [];
        $outputs = [];
        $frameworkLockCanonical = null;
        $runtime = null;
        $declarativePipeline = null;
        foreach ($pagePaths as $pagePath) {
            $plan = $loader->resolve($pagePath);
            $currentFrameworkLock = CanonicalJson::encode($plan->frameworkLock);
            if ($frameworkLockCanonical !== null && $frameworkLockCanonical !== $currentFrameworkLock) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_LOCK_CHANGED_DURING_BUILD',
                    'The Framework lock changed while the portable build was resolving pages.',
                );
            }
            $frameworkLockCanonical ??= $currentFrameworkLock;
            $runtime ??= FrameworkComponentRuntime::fromLock(
                $plan->frameworkLock,
                $this->frameworkAssetBase($plan->frameworkLock, (string) ($site['base_url'] ?? '/')),
            );
            $components = $runtime->extract($plan->markdown, $plan->page);
            $outline = (new PortableDocumentOutlineBuilder)->build(
                $this->markdown->render($components->markdownWithPlaceholders),
                (int) data_get($plan->configuration, 'reading.toc_depth', 3),
                $this->html->reservedDocumentIds(),
            );
            $contentHtml = $components->hydrate($outline['html']);
            $route = $this->route($plan, $contentRoot, (string) ($site['base_url'] ?? '/'));
            if (isset($outputs[$route['output']])) {
                throw new PortableConfigurationException(
                    'PORTABLE_OUTPUT_COLLISION',
                    "Pages [{$outputs[$route['output']]}] and [$pagePath] resolve to [{$route['output']}].",
                );
            }
            $outputs[$route['output']] = $pagePath;
            $title = $this->pageTitle($plan);
            $componentIds = array_values(array_unique(array_map(
                static fn (array $call): string => (string) ($call['id'] ?? ''),
                $components->normalizedCalls,
            )));
            $unsupportedDeclarativeComponents = array_values(array_diff($componentIds, ['ui.alert']));
            sort($unsupportedDeclarativeComponents, SORT_STRING);

            $pages[] = [
                'plan' => $plan,
                'page_path' => $pagePath,
                'title' => $title,
                'description' => (string) ($plan->configuration['description'] ?? ''),
                'locale' => (string) ($plan->configuration['locale'] ?? $buildLocale),
                'documentation_version' => $documentationVersion,
                'preset' => (string) ($plan->configuration['preset'] ?? 'docs'),
                'theme' => (string) data_get($plan->configuration, 'settings.theme', 'system'),
                'max_width' => (string) data_get($plan->configuration, 'layout.max_width', 'normal'),
                'navigation_hidden' => (bool) data_get($plan->configuration, 'navigation.hidden', false),
                'navigation_order' => data_get($plan->configuration, 'navigation.order'),
                'search_enabled' => (bool) data_get($plan->configuration, 'search.enabled', false),
                'search_indexed' => (bool) data_get($plan->configuration, 'search.indexed', true),
                'reading_breadcrumbs' => (bool) data_get($plan->configuration, 'reading.breadcrumbs', true),
                'reading_toc' => (bool) data_get($plan->configuration, 'reading.toc', true),
                'reading_previous_next' => (bool) data_get($plan->configuration, 'reading.previous_next', true),
                'outline' => $outline['items'],
                'url' => $route['url'],
                'output' => $route['output'],
                'home_url' => $this->homeUrl((string) ($site['base_url'] ?? '/')),
                'content_html' => $contentHtml,
                'components' => $components,
                'component_calls' => $components->normalizedCalls,
                'declarative_supported' => $unsupportedDeclarativeComponents === [],
                'declarative_unsupported_components' => $unsupportedDeclarativeComponents,
            ];
        }

        $catalogBasePlan = $loader->resolveGeneratedBase(
            $contentRoot . '/components/catalog/index.md',
        );
        if (CanonicalJson::encode($catalogBasePlan->frameworkLock) !== $frameworkLockCanonical) {
            throw new PortableConfigurationException(
                'FRAMEWORK_LOCK_CHANGED_DURING_BUILD',
                'The Framework lock changed while the generated component catalogue was being resolved.',
            );
        }
        if (! $runtime instanceof FrameworkComponentRuntime) {
            throw new PortableConfigurationException(
                'FRAMEWORK_RUNTIME_MISSING',
                'The component runtime was not initialized for the portable build.',
            );
        }
        $effectiveComponentCatalog = EffectiveComponentCatalogBuilder::bundled(
            FrameworkLock::fromArray($catalogBasePlan->frameworkLock),
        )->build();
        $componentCatalogProjector = new PortableComponentCatalogProjector($this->markdown);
        $componentCatalogProjection = $componentCatalogProjector->project(
            catalog: $effectiveComponentCatalog,
            runtime: $runtime,
            basePlan: $catalogBasePlan,
            contentRoot: $contentRoot,
            baseUrl: (string) ($site['base_url'] ?? '/'),
            homeUrl: $this->homeUrl((string) ($site['base_url'] ?? '/')),
            reservedDocumentIds: $this->html->reservedDocumentIds(),
        );
        foreach ($componentCatalogProjection['pages'] as $catalogPage) {
            if (isset($outputs[$catalogPage['output']])) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ROUTE_COLLISION',
                    "Authored page [{$outputs[$catalogPage['output']]}] shadows generated component catalogue route [{$catalogPage['output']}].",
                );
            }
            $outputs[$catalogPage['output']] = '@docara/component-catalog';
            $catalogPage['documentation_version'] = $documentationVersion;
            $pages[] = $catalogPage;
        }

        foreach ($pages as $page) {
            if (($page['locale'] ?? null) !== $buildLocale) {
                throw new PortableConfigurationException(
                    'PORTABLE_BUILD_LOCALE_MISMATCH',
                    "Page [{$page['page_path']}] locale [{$page['locale']}] does not match build locale [$buildLocale].",
                );
            }
        }

        $navigationBuilder = new PortableNavigationBuilder;
        $topology = $navigationBuilder->build($pages, $contentRoot, $contentPath);
        $navigation = $navigationBuilder->visible($topology);
        $contentAssets = $this->contentAssets($contentPath, array_keys($outputs));
        $redirectPublisher = new PortableRedirectPublisher($this->files);
        $redirectPlan = $redirectPublisher->plan(
            $root,
            $site,
            $pages,
            $contentAssets,
            $buildLocale,
            $documentationVersion,
        );
        $siteTitle = (string) ($site['title'] ?? 'Docara');
        $brandPublisher = new PortableBrandAssetPlanner($this->files);
        $brandPlan = $brandPublisher->plan(
            $root,
            $pages,
            (string) ($site['base_url'] ?? '/'),
            $siteTitle,
        );
        $searchEnabled = false;
        foreach ($pages as $page) {
            if ($page['search_enabled'] === true) {
                $searchEnabled = true;
                break;
            }
        }
        $searchPlan = null;
        if ($searchEnabled) {
            $searchPlan = (new PortableSearchIndexBuilder)->plan(
                $pages,
                $topology,
                $this->homeUrl((string) ($site['base_url'] ?? '/')),
            );
            foreach ($pages as &$page) {
                if ($page['search_enabled'] === true) {
                    $page['search_index_url'] = $searchPlan->indexUrl;
                    $page['search_runtime_url'] = $searchPlan->runtimeUrl;
                }
            }
            unset($page);
        }
        $componentCatalogJson = CanonicalJson::encodePretty($effectiveComponentCatalog);

        $this->prepareDestination($root, $destination);
        $result = collect();
        $diagnostics = [];

        $docaraOutputDirectory = rtrim($destination, '/\\') . '/_docara';
        $this->files->ensureDirectoryExists($docaraOutputDirectory);
        $this->files->put($docaraOutputDirectory . '/component-catalog.json', $componentCatalogJson);
        $catalogReceiptPath = rtrim($destination, '/\\') . '/.docara/component-catalog-pages.json';
        $this->files->ensureDirectoryExists(dirname($catalogReceiptPath));
        $this->files->put(
            $catalogReceiptPath,
            $this->prettyCanonicalJson($componentCatalogProjection['receipt']),
        );
        foreach ($componentCatalogProjector->assets() as $relative => $bytes) {
            $assetPath = rtrim($destination, '/\\') . '/' . $relative;
            $this->files->ensureDirectoryExists(dirname($assetPath));
            if ($this->files->put($assetPath, $bytes) === false
                || ! hash_equals(hash('sha256', $bytes), (string) hash_file('sha256', $assetPath))
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ASSET_PUBLICATION_FAILED',
                    $relative,
                );
            }
        }

        if ($searchPlan instanceof PortableSearchPlan) {
            $this->files->put($docaraOutputDirectory . '/search-index.json', $searchPlan->indexJson);
            $this->files->put($docaraOutputDirectory . '/search.js', $searchPlan->runtime);
        }

        foreach ($pages as $pageIndex => $page) {
            $page['branding'] = $brandPlan['pages'][$pageIndex];
            $readingContext = $navigationBuilder->readingContextForUrl($topology, (string) $page['url']);
            $page['breadcrumbs'] = $page['reading_breadcrumbs'] === true
                ? $readingContext['breadcrumbs']
                : [];
            $page['previous'] = $page['reading_previous_next'] === true
                ? $readingContext['previous']
                : null;
            $page['next'] = $page['reading_previous_next'] === true
                ? $readingContext['next']
                : null;
            if (isset($page['component_catalog_kind'])) {
                $page['breadcrumbs'] = $page['reading_breadcrumbs'] === true
                    ? $page['component_catalog_breadcrumbs']
                    : [];
                $page['previous'] = $page['reading_previous_next'] === true
                    ? $page['component_catalog_previous']
                    : null;
                $page['next'] = $page['reading_previous_next'] === true
                    ? $page['component_catalog_next']
                    : null;
            }
            if ($page['reading_toc'] !== true) {
                $page['outline'] = [];
            }
            $activeNavigation = $navigationBuilder->activate(
                $navigation,
                ($page['component_catalog_kind'] ?? null) === 'detail'
                    ? (string) $page['component_catalog_index_url']
                    : (string) $page['url'],
            );
            if (($page['declarative_supported'] ?? false) === true) {
                /** @var ResolvedPagePlan $declarativePlan */
                $declarativePlan = $page['plan'];
                $composition = PageCompositionContext::fromBuilder(
                    $page['branding'],
                    (string) $page['home_url'],
                    $activeNavigation,
                    $page['outline'],
                );
                $declarativePipeline ??= DeclarativePipeline::bundled(
                    $declarativePlan->frameworkLock,
                    $this->markdown,
                    $this->html->reservedDocumentIds(),
                );
                $declarative = $declarativePipeline->build(
                    $declarativePlan->markdown,
                    $declarativePlan->page,
                    (string) $page['output'],
                    (string) $page['title'],
                    (int) data_get($declarativePlan->configuration, 'reading.toc_depth', 3),
                    $composition,
                );
                $parity = (new SemanticParityChecker)->assertEquivalent(
                    (string) $page['title'],
                    (string) $page['content_html'],
                    $page['components']->normalizedCalls,
                    $declarative,
                );
                $shellParity = (new ShellStructuralParityChecker)->assertEquivalent(
                    $composition,
                    $declarative->plan,
                );
                $larenaContract = (new LarenaContractAdapter)->adapt($declarative->plan);
                $page['declarative_pipeline'] = $declarative->toArray() + [
                    'semantic_parity' => $parity->toArray(),
                    'shell_structural_parity' => $shellParity->toArray(),
                    'larena_contract' => [
                        'schema' => $larenaContract->payload['schema'],
                        'canonical_hash' => $larenaContract->canonicalHash(),
                        'semantic_parity' => $larenaContract->semantics
                            === $declarative->plan->semanticProjection()
                            ? 'pass'
                            : 'fail',
                    ],
                ];
            } elseif (array_key_exists('declarative_unsupported_components', $page)) {
                $page['declarative_pipeline'] = [
                    'status' => 'not_in_vertical_slice',
                    'unsupported_components' => $page['declarative_unsupported_components'],
                    'supported_components' => ['ui.alert'],
                ];
            }
            $outputPath = rtrim($destination, '/\\') . '/' . $page['output'];
            $this->files->ensureDirectoryExists(dirname($outputPath));
            $rendered = $this->html->render($page, $activeNavigation, $siteTitle, $page['components']->assetPlan);
            $this->files->put($outputPath, $rendered);

            /** @var ResolvedPagePlan $plan */
            $plan = $page['plan'];
            $record = [
                'canonical_hash' => $plan->canonicalHash(),
                'output' => $page['output'],
                'url' => $page['url'],
                'resolved_page_plan' => $plan->toArray(),
                'component_runtime' => $page['components']->toArray(),
                'declarative_pipeline' => $page['declarative_pipeline'] ?? [
                    'status' => 'not_applicable_generated_page',
                ],
            ];
            $diagnostics[] = $record;
            $result->put((string) $page['url'], $record);
        }

        $redirectPublisher->publish($redirectPlan, $destination);
        $this->copyContentAssets($contentAssets, $destination);
        $brandPublisher->publish($brandPlan['assets'], $destination);
        $this->publishFrameworkAssets($catalogBasePlan->frameworkLock, $destination);
        $diagnosticPath = rtrim($destination, '/\\') . '/.docara/resolved-page-plans.json';
        $this->files->ensureDirectoryExists(dirname($diagnosticPath));
        $this->files->put($diagnosticPath, $this->prettyCanonicalJson([
            'schema' => 'docara.resolved_page_plans.v1',
            'build' => [
                'locale' => $buildLocale,
                'documentation_version' => $documentationVersion,
            ],
            'pages' => $diagnostics,
        ]));

        return $result;
    }

    /** @return array<string, mixed> */
    private function siteConfiguration(string $root): array
    {
        $path = $root . '/docara.json';
        try {
            $site = json_decode((string) @file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException('JSON_INVALID', 'docara.json is not valid JSON.', $exception);
        }
        (new SchemaRepository)->assertValid($site, 'site.schema.json');
        if (! is_array($site)) {
            throw new PortableConfigurationException('JSON_OBJECT_REQUIRED', 'docara.json must contain an object.');
        }

        return $site;
    }

    private function realDirectory(string $path, string $code): string
    {
        if (is_link($path) || ($real = realpath($path)) === false || ! is_dir($real)) {
            throw new PortableConfigurationException($code, "Directory [$path] is missing or unsafe.");
        }

        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    private function confinedDirectory(string $root, string $relative): string
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '\\')) {
            throw new PortableConfigurationException('CONTENT_ROOT_INVALID', 'content_root must be a safe relative directory.');
        }
        $candidate = $root;
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new PortableConfigurationException('CONTENT_ROOT_INVALID', 'content_root contains a forbidden segment.');
            }
            $candidate .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($candidate)) {
                throw new PortableConfigurationException('SYMLINK_FORBIDDEN', 'content_root traverses a symbolic link.');
            }
        }
        $real = $this->realDirectory($candidate, 'CONTENT_ROOT_NOT_FOUND');
        if (! str_starts_with($real . '/', $root . '/')) {
            throw new PortableConfigurationException('PATH_ESCAPE_FORBIDDEN', 'content_root escapes the portable site root.');
        }

        return $real;
    }

    /** @return list<string> */
    private function markdownFiles(string $root, string $contentPath): array
    {
        $pages = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($contentPath, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new PortableConfigurationException('SYMLINK_FORBIDDEN', 'Portable content cannot contain symbolic links.');
            }
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['md', 'markdown'], true)) {
                continue;
            }
            $pages[] = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        }
        sort($pages, SORT_STRING);

        return $pages;
    }

    /** @return array{url:string,output:string} */
    private function route(ResolvedPagePlan $plan, string $contentRoot, string $baseUrl): array
    {
        $slug = $plan->configuration['slug'] ?? null;
        if (! is_string($slug) || $slug === '') {
            $slug = substr($plan->page, strlen(rtrim($contentRoot, '/') . '/'));
            $slug = preg_replace('/\.(?:md|markdown)$/i', '', $slug) ?? $slug;
            if ($slug === 'index') {
                $slug = '';
            } elseif (str_ends_with($slug, '/index')) {
                $slug = substr($slug, 0, -strlen('/index'));
            }
        }
        $slug = trim(str_replace('\\', '/', $slug), '/');
        $segments = $slug === '' ? [] : explode('/', $slug);
        $firstSegment = strtolower($segments[0] ?? '');
        if (in_array($firstSegment, ['_docara', '.docara'], true)) {
            throw new PortableConfigurationException(
                'PAGE_SLUG_RESERVED',
                "Page [{$plan->page}] targets a reserved Docara namespace.",
            );
        }
        if ($slug !== ''
            && preg_match(
                '/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9_-])?(?:\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9_-])?)*$/D',
                $slug,
            ) !== 1
        ) {
            throw new PortableConfigurationException('PAGE_SLUG_INVALID', "Page [{$plan->page}] has an unsafe slug.");
        }
        $encoded = implode('/', array_map('rawurlencode', $slug === '' ? [] : explode('/', $slug)));
        $base = '/' . trim($baseUrl, '/');
        $base = $base === '/' ? '' : $base;
        $url = $base . '/' . ($encoded === '' ? '' : $encoded . '/');

        return [
            'url' => $url,
            'output' => $slug === '' ? 'index.html' : $slug . '/index.html',
        ];
    }

    private function pageTitle(ResolvedPagePlan $plan): string
    {
        $titleSource = $plan->provenance['/title'] ?? '';
        if (str_ends_with($titleSource, '.page.json') && is_string($plan->configuration['title'] ?? null)) {
            return $plan->configuration['title'];
        }
        if (preg_match('/^#\s+(.+)$/mu', $plan->markdown, $match) === 1) {
            return trim(preg_replace('/[*_`]+/', '', $match[1]) ?? $match[1]);
        }

        return (string) ($plan->configuration['title'] ?? pathinfo($plan->page, PATHINFO_FILENAME));
    }

    private function homeUrl(string $baseUrl): string
    {
        $base = trim($baseUrl, '/');

        return $base === '' ? '/' : '/' . $base . '/';
    }

    private function prepareDestination(string $root, string $destination): void
    {
        $this->assertDestinationShape($root, $destination);
        if ($this->files->isDirectory($destination)) {
            $this->files->cleanDirectory($destination);
        } else {
            $this->files->makeDirectory($destination, 0755, true);
        }
    }

    private function assertDestinationShape(string $root, string $destination): void
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedDestination = rtrim(str_replace('\\', '/', $destination), '/');
        $isDirectBuildDirectory = dirname($normalizedDestination) === $normalizedRoot
            && preg_match('/^build(?:_[A-Za-z0-9._-]+)?$/', basename($normalizedDestination)) === 1;
        if ($normalizedDestination === '' || ! $isDirectBuildDirectory) {
            throw new PortableConfigurationException(
                'DESTINATION_OUTSIDE_SITE_FORBIDDEN',
                'Portable builds may only clean a direct build or build_* directory inside the site root.',
            );
        }
        if (is_link($destination)) {
            throw new PortableConfigurationException(
                'DESTINATION_SYMLINK_FORBIDDEN',
                'Portable builds refuse to clean a symbolic-link destination.',
            );
        }
    }

    /** @param array<string, mixed> $site */
    private function assertDestinationInputBoundary(
        string $root,
        string $destination,
        string $contentPath,
        array $site,
    ): void {
        $this->assertDestinationShape($root, $destination);
        $normalizedDestination = rtrim(str_replace('\\', '/', $destination), '/');
        $frameworkLock = (string) ($site['framework_lock'] ?? '');
        $inputs = [
            $contentPath,
            $root . '/' . $frameworkLock,
            $root . '/docara.json',
        ];
        if (is_string($site['redirects_file'] ?? null)) {
            $inputs[] = $root . '/' . $site['redirects_file'];
        }

        foreach ($inputs as $input) {
            $normalizedInput = rtrim(str_replace('\\', '/', $input), '/');
            if ($normalizedDestination === $normalizedInput
                || str_starts_with($normalizedInput, $normalizedDestination . '/')
                || str_starts_with($normalizedDestination, $normalizedInput . '/')
            ) {
                throw new PortableConfigurationException(
                    'DESTINATION_INPUT_OVERLAP_FORBIDDEN',
                    "Build destination [$destination] overlaps portable input [$input].",
                );
            }
        }
    }

    /**
     * @param  list<string>  $generatedOutputs
     * @return list<array{source: string, relative: string}>
     */
    private function contentAssets(string $contentPath, array $generatedOutputs): array
    {
        $reservedOutputs = array_map('strtolower', $generatedOutputs);
        $assets = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($contentPath, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new PortableConfigurationException('SYMLINK_FORBIDDEN', 'Portable content cannot contain symbolic links.');
            }
            if (! $file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            $extension = strtolower($file->getExtension());
            if ($name === '_section.json') {
                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($contentPath))), '/');
                $canonical = ($file->getPath() === $contentPath ? '' : dirname($relative) . '/') . 'section.json';
                throw new PortableConfigurationException(
                    'SECTION_DESCRIPTOR_LEGACY_NAME',
                    "Rename portable section descriptor [$relative] to [$canonical].",
                );
            }
            if (in_array($extension, ['md', 'markdown'], true)
                || $name === 'section.json'
                || str_ends_with($name, '.page.json')
            ) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($contentPath))), '/');
            $normalizedRelative = strtolower($relative);
            $topLevel = explode('/', $normalizedRelative, 2)[0];
            $collidesWithGeneratedOutput = false;
            foreach ($reservedOutputs as $output) {
                if ($normalizedRelative === $output
                    || str_starts_with($normalizedRelative, $output . '/')
                    || str_starts_with($output, $normalizedRelative . '/')
                ) {
                    $collidesWithGeneratedOutput = true;

                    break;
                }
            }
            if ($collidesWithGeneratedOutput || in_array($topLevel, ['_docara', '.docara'], true)) {
                throw new PortableConfigurationException(
                    'PORTABLE_ASSET_OUTPUT_COLLISION',
                    "Content asset [$relative] collides with a generated or reserved output path.",
                );
            }
            $assets[] = [
                'source' => $file->getPathname(),
                'relative' => $relative,
            ];
        }

        return $assets;
    }

    /** @param list<array{source: string, relative: string}> $assets */
    private function copyContentAssets(array $assets, string $destination): void
    {
        foreach ($assets as $asset) {
            $target = rtrim($destination, '/\\') . '/' . $asset['relative'];
            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->copy($asset['source'], $target);
        }
    }

    /** @param array<string, mixed> $lock */
    private function frameworkAssetBase(array $lock, string $baseUrl): string
    {
        $projection = FrameworkLock::fromArray($lock)->assetProjection();
        $base = trim($baseUrl, '/');

        return '/' . ($base === '' ? '' : $base . '/') . (string) $projection['mount'];
    }

    /** @param array<string, mixed> $lock */
    private function publishFrameworkAssets(array $lock, string $destination): void
    {
        $frameworkLock = FrameworkLock::fromArray($lock);
        $repository = FrameworkManifestRepository::bundled($frameworkLock);
        $projection = $frameworkLock->assetProjection();
        $mount = (string) $projection['mount'];

        $relativePaths = array_keys($projection['files']);
        sort($relativePaths, SORT_STRING);
        foreach ($relativePaths as $relativePath) {
            $record = $projection['files'][$relativePath];
            if (! is_string($relativePath) || ! is_array($record) || ! is_string($record['sha256'] ?? null)) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_ASSET_PROJECTION_INVALID',
                    'The Framework asset projection contains an invalid record.',
                );
            }
            $bytes = $repository->bundledAsset($relativePath);
            $target = rtrim($destination, '/\\') . '/' . $mount . '/' . $relativePath;
            $this->files->ensureDirectoryExists(dirname($target));
            if ($this->files->put($target, $bytes) === false
                || ! hash_equals($record['sha256'], hash('sha256', (string) $this->files->get($target)))
            ) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_ASSET_PUBLICATION_FAILED',
                    "Framework asset [$relativePath] could not be published deterministically.",
                );
            }
        }
    }

    private function prettyCanonicalJson(mixed $value): string
    {
        return CanonicalJson::encodePretty($value);
    }
}
