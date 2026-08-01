<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Illuminate\Support\Collection;
use JsonException;
use Simai\Docara\ComponentCatalog\AuthoredComponentPageIndex;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\Content\PageSource;
use Simai\Docara\Content\PageSourceLocator;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\DeclarativePipeline;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\I18n\LanguagePackRepository;
use Simai\Docara\I18n\LocaleInternalLinkProjector;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleRoutingPolicy;
use Simai\Docara\I18n\LocaleUrlProjector;
use Simai\Docara\I18n\Translator;
use Simai\Docara\I18n\UiCopy;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\Preferences\ReaderPreferenceCompiler;
use Symfony\Component\Process\Process;

final readonly class PortableSiteBuilder
{
    private PortablePagePublisher $publisher;

    public function __construct(
        private Filesystem $files,
        private PortableMarkdownRenderer $markdown,
        ?PortablePagePublisher $publisher = null,
    ) {
        $this->publisher = $publisher ?? new DeclarativePortablePagePublisher;
    }

    /** @return Collection<string, array<string, mixed>> */
    public function build(string $root, string $destination, ?string $onlyPage = null): Collection
    {
        // Validate the caller's lexical root before realpath normalization so
        // link, link/ and link/. cannot hide the same symbolic-link root.
        $loader = new PortableConfigurationLoader($root);
        $root = $this->realDirectory($root, 'PORTABLE_ROOT_INVALID');
        $site = $this->siteConfiguration($root);
        $explicitLocaleRegistry = is_array($site['locales'] ?? null) && $site['locales'] !== [];
        $localeRegistry = LocaleRegistry::fromSite($site);
        $localeRouting = LocaleRoutingPolicy::fromSite($site, $localeRegistry);
        $localeUrls = new LocaleUrlProjector(
            (string) ($site['base_url'] ?? '/'),
            $localeRegistry,
            $localeRouting,
        );
        $translator = new Translator($localeRegistry, new LanguagePackRepository($root));
        $uiCopy = new UiCopy($translator);
        $buildLocale = $localeRegistry->default()->tag->value();
        $documentationVersion = (string) ($site['documentation_version'] ?? 'current');
        $defaultLocale = $localeRegistry->default();
        $contentRoot = $defaultLocale->contentRoot;
        $contentPath = $this->confinedDirectory($root, $contentRoot);
        $contentContexts = [];
        $pagePaths = [];
        $pageSourceLocator = new PageSourceLocator($root, $localeRegistry);
        foreach ($localeRegistry->all() as $locale => $definition) {
            $localeContentPath = $this->confinedDirectory($root, $definition->contentRoot);
            $localePageSources = $pageSourceLocator->forLocale($locale);
            if ($localePageSources === []) {
                throw new PortableConfigurationException(
                    'PORTABLE_LOCALE_CONTENT_EMPTY',
                    "Portable content for locale [$locale] does not contain Markdown pages.",
                );
            }
            $contentContexts[$locale] = [
                'root' => $definition->contentRoot,
                'path' => $localeContentPath,
                'prefix' => $definition->publicPrefix,
            ];
            array_push(
                $pagePaths,
                ...array_map(static fn (PageSource $source): string => $source->path, $localePageSources),
            );
        }
        sort($pagePaths, SORT_STRING);
        $this->assertDestinationInputBoundary(
            $root,
            $destination,
            array_values(array_column($contentContexts, 'path')),
            $site,
        );
        $finalDestination = $destination;
        $destination = $this->candidateDestination($root, $finalDestination);
        $pages = [];
        $outputs = [];
        $frameworkLockCanonical = null;
        $runtime = null;
        $declarativePipeline = null;
        $pageBuilder = new PageBuilder($this->markdown);
        foreach ($pagePaths as $pagePath) {
            $plan = $loader->resolve($pagePath);
            $pageLocale = (string) ($plan->configuration['locale'] ?? $buildLocale);
            if (! $explicitLocaleRegistry && $pageLocale !== $buildLocale) {
                throw new PortableConfigurationException(
                    'PORTABLE_BUILD_LOCALE_MISMATCH',
                    "Page [$pagePath] locale [$pageLocale] does not match build locale [$buildLocale].",
                );
            }
            $localeDefinition = $localeRegistry->get($pageLocale);
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
            $pageResult = $pageBuilder->build(
                $plan,
                $root,
                $runtime,
                (int) data_get($plan->configuration, 'reading.toc_depth', 3),
            );
            $components = $pageResult->frameworkComponents;
            $outline = $pageResult->outline;
            $contentHtml = $pageResult->contentHtml;
            $route = $this->route(
                $plan,
                $localeDefinition->contentRoot,
                $localeUrls,
                $pageLocale,
            );
            if (isset($outputs[$route['output']])) {
                throw new PortableConfigurationException(
                    'PORTABLE_OUTPUT_COLLISION',
                    "Pages [{$outputs[$route['output']]}] and [$pagePath] resolve to [{$route['output']}].",
                );
            }
            $outputs[$route['output']] = $pagePath;
            $title = $this->pageTitle($plan);
            $pages[] = [
                'plan' => $plan,
                'page_path' => $pagePath,
                'page_source_kind' => 'authored_markdown',
                'title' => $title,
                'description' => $this->pageDescription($plan),
                'locale' => $pageLocale,
                'direction' => $localeDefinition->direction,
                'translation_key' => $this->translationKey($plan->page, $localeDefinition->contentRoot),
                'documentation_version' => $documentationVersion,
                'preset' => (string) ($plan->configuration['preset'] ?? 'docs'),
                'theme' => (string) data_get($plan->configuration, 'settings.theme', 'system'),
                'modal_blur' => (string) data_get($plan->configuration, 'settings.modal_blur', 'large'),
                'reader_preferences' => is_array($plan->configuration['reader_preferences'] ?? null)
                    ? $plan->configuration['reader_preferences']
                    : ReaderPreferenceCompiler::defaultConfiguration(),
                'reader_preferences_storage_key' => ReaderPreferenceCompiler::storageKey($plan->configuration),
                'container_max' => (int) data_get($plan->configuration, 'layout.container.max', 7),
                'scrollbar_preset' => (string) data_get($plan->configuration, 'layout.scrollbar.preset', 'overlay'),
                'content_gap' => (int) data_get($plan->configuration, 'layout.content.gap', 0),
                'navigation_hidden' => (bool) data_get($plan->configuration, 'navigation.hidden', false),
                'navigation_order' => data_get($plan->configuration, 'navigation.order'),
                'search_enabled' => (bool) data_get($plan->configuration, 'search.enabled', false),
                'search_indexed' => (bool) data_get($plan->configuration, 'search.indexed', true),
                'reading_breadcrumbs' => (bool) data_get($plan->configuration, 'reading.breadcrumbs', true),
                'reading_toc' => (bool) data_get($plan->configuration, 'reading.toc', true),
                'reading_mobile_toc' => (string) data_get($plan->configuration, 'reading.mobile_toc', 'auto'),
                'reading_previous_next' => (bool) data_get($plan->configuration, 'reading.previous_next', true),
                'outline' => $outline['items'],
                'url' => $route['url'],
                'output' => $route['output'],
                'home_url' => $localeUrls->home($pageLocale),
                'content_html' => $contentHtml,
                'components' => $components,
                'component_calls' => $components->normalizedCalls,
            ];
        }

        $authoredPages = $pages;
        $catalogBasePlan = $loader->resolveGeneratedBase(
            $contentRoot . '/components/index.md',
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
        $componentCatalogProjector = new PortableComponentCatalogProjector(
            $this->markdown,
            translator: $translator,
        );
        $componentCatalogProjections = [];
        foreach ($localeRegistry->all() as $locale => $definition) {
            $localeCatalogBasePlan = $locale === $buildLocale
                ? $catalogBasePlan
                : $loader->resolveGeneratedBase($definition->contentRoot . '/components/index.md');
            if (CanonicalJson::encode($localeCatalogBasePlan->frameworkLock) !== $frameworkLockCanonical) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_LOCK_CHANGED_DURING_BUILD',
                    'The Framework lock changed while a localized component catalogue was being resolved.',
                );
            }
            $localeAuthoredPages = array_values(array_filter(
                $authoredPages,
                static fn (array $page): bool => ($page['locale'] ?? null) === $locale,
            ));
            $authoredComponents = AuthoredComponentPageIndex::build(
                $effectiveComponentCatalog,
                $localeAuthoredPages,
                $definition->publicPrefix,
            );
            $componentCatalogProjection = $componentCatalogProjector->project(
                catalog: $effectiveComponentCatalog,
                runtime: $runtime,
                basePlan: $localeCatalogBasePlan,
                contentRoot: $definition->contentRoot,
                baseUrl: $localeUrls->home($locale),
                homeUrl: $localeUrls->home($locale),
                outputPrefix: $definition->publicPrefix,
                reservedDocumentIds: PortableDocumentIds::reserved(),
                authoredComponents: $authoredComponents,
            );
            $componentCatalogProjections[$locale] = $componentCatalogProjection;
            foreach ($componentCatalogProjection['pages'] as $catalogPage) {
                if (isset($outputs[$catalogPage['output']])) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_ROUTE_COLLISION',
                        "Authored page [{$outputs[$catalogPage['output']]}] shadows generated component catalogue route [{$catalogPage['output']}].",
                    );
                }
                $outputs[$catalogPage['output']] = '@docara/component-catalog/' . $locale;
                $catalogPage['documentation_version'] = $documentationVersion;
                $catalogPage['translation_key'] = ($catalogPage['component_catalog_kind'] ?? null) === 'detail'
                    ? '@catalog/' . (string) $catalogPage['component_catalog_id']
                    : '@catalog/index';
                $pages[] = $catalogPage;
            }
        }

        $declarativeExampleProjector = new PortableDeclarativeExampleProjector(translator: $translator);
        $declarativeExampleProjection = null;
        if ($declarativeExampleProjector->exists($root)) {
            $exampleBasePlan = $loader->resolveGeneratedBase(
                $contentRoot . '/examples/index.md',
            );
            if (CanonicalJson::encode($exampleBasePlan->frameworkLock) !== $frameworkLockCanonical) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_LOCK_CHANGED_DURING_BUILD',
                    'The Framework lock changed while declarative examples were being resolved.',
                );
            }
            $declarativeExampleProjection = $declarativeExampleProjector->project(
                root: $root,
                authoredPages: $authoredPages,
                runtime: $runtime,
                basePlan: $exampleBasePlan,
                contentRoot: $contentRoot,
                baseUrl: $localeUrls->home($buildLocale),
                homeUrl: $localeUrls->home($buildLocale),
                outputPrefix: $localeRegistry->get($buildLocale)->publicPrefix,
                reservedDocumentIds: PortableDocumentIds::reserved(),
            );
            foreach ($declarativeExampleProjection['pages'] as $examplePage) {
                if (isset($outputs[$examplePage['output']])) {
                    throw new PortableConfigurationException(
                        'DECLARATIVE_EXAMPLE_ROUTE_COLLISION',
                        "Page [{$outputs[$examplePage['output']]}] shadows declarative example route [{$examplePage['output']}].",
                    );
                }
                $outputs[$examplePage['output']] = '@docara/declarative-examples';
                $examplePage['documentation_version'] = $documentationVersion;
                $pages[] = $examplePage;
            }
        }

        if (! $explicitLocaleRegistry) {
            foreach ($pages as $page) {
                if (($page['locale'] ?? null) !== $buildLocale) {
                    throw new PortableConfigurationException(
                        'PORTABLE_BUILD_LOCALE_MISMATCH',
                        "Page [{$page['page_path']}] locale [{$page['locale']}] does not match build locale [$buildLocale].",
                    );
                }
            }
        }
        $translations = [];
        foreach ($pages as $page) {
            $translations[(string) ($page['translation_key'] ?? $page['page_path'])][(string) $page['locale']] = [
                'url' => (string) $page['url'],
                'label' => $localeRegistry->get((string) $page['locale'])->label,
            ];
        }
        foreach ($pages as &$page) {
            $pageLocale = (string) $page['locale'];
            $page['direction'] = $localeRegistry->get($pageLocale)->direction;
            $page['ui_copy'] = $uiCopy->forLocale($pageLocale);
            $page['canonical_url'] = (string) $page['url'];
            $available = $translations[(string) ($page['translation_key'] ?? $page['page_path'])] ?? [];
            $page['alternates'] = [];
            $page['language_options'] = [];
            foreach ($localeRegistry->all() as $candidateLocale => $definition) {
                if (! isset($available[$candidateLocale])) {
                    continue;
                }
                $page['alternates'][] = [
                    'locale' => $candidateLocale,
                    'url' => $available[$candidateLocale]['url'],
                ];
                $page['language_options'][] = [
                    'locale' => $candidateLocale,
                    'label' => $definition->label,
                    'url' => $available[$candidateLocale]['url'],
                    'current' => $candidateLocale === $pageLocale,
                ];
            }
            $page['alternates'][] = [
                'locale' => 'x-default',
                'url' => $localeUrls->rootUrl(),
            ];
        }
        unset($page);
        $pages = (new PortableBacklinkHydrator)->hydrate($pages);
        $localeLinkRoutes = [];
        foreach ($pages as $page) {
            $pageLocale = (string) $page['locale'];
            $canonicalUrl = (string) $page['url'];
            $legacyUrl = $localeUrls->unprefixed($pageLocale, $canonicalUrl);
            if ($legacyUrl === $canonicalUrl) {
                continue;
            }
            $localeLinkRoutes[$pageLocale][$legacyUrl] = $canonicalUrl;
            if ($legacyUrl !== '/') {
                $localeLinkRoutes[$pageLocale][rtrim($legacyUrl, '/')] = rtrim($canonicalUrl, '/');
            }
        }
        $localeLinkProjectors = [];
        foreach ($localeRegistry->all() as $locale => $_definition) {
            $localeLinkProjectors[$locale] = new LocaleInternalLinkProjector(
                $localeLinkRoutes[$locale] ?? [],
            );
        }
        $navigationBuilder = new PortableNavigationBuilder;
        $topologies = [];
        $navigations = [];
        $topology = [];
        $contentAssets = [];
        foreach ($contentContexts as $locale => $context) {
            $localePages = array_values(array_filter(
                $pages,
                static fn (array $page): bool => ($page['locale'] ?? null) === $locale,
            ));
            $topologies[$locale] = $navigationBuilder->build($localePages, $context['root'], $context['path']);
            $navigations[$locale] = $navigationBuilder->visible($topologies[$locale]);
            array_push($topology, ...$topologies[$locale]);
            array_push(
                $contentAssets,
                ...$this->contentAssets($context['path'], array_keys($outputs), $context['prefix']),
            );
        }
        $redirectPublisher = new PortableRedirectPublisher($this->files);
        $redirectPlan = $redirectPublisher->plan(
            $root,
            $site,
            $pages,
            $contentAssets,
            $buildLocale,
            $documentationVersion,
            $uiCopy->forLocale($buildLocale),
            $localeRegistry->default()->direction,
        );
        $localeRoutePublisher = new PortableLocaleRoutePublisher($this->files);
        $localeRoutePlan = $localeRoutePublisher->plan(
            $pages,
            $localeRegistry,
            $localeUrls,
            $documentationVersion,
            $uiCopy->forLocale($buildLocale),
        );
        $siteTitle = (string) ($site['title'] ?? 'Docara');
        $brandPublisher = new PortableBrandAssetPlanner($this->files);
        $brandPlan = $brandPublisher->plan(
            $root,
            $pages,
            (string) ($site['base_url'] ?? '/'),
            $siteTitle,
        );
        foreach ($pages as &$page) {
            $componentKeys = array_values(array_unique(array_map(
                static fn (array $call): string => (string) ($call['id'] ?? ''),
                $page['components']->normalizedCalls,
            )));
            $shellRuntimeTags = ['sf-icon'];
            if ($page['search_enabled'] === true) {
                array_push($shellRuntimeTags, 'sf-button', 'sf-modal');
            }
            $page['components'] = $page['components']->withAssetPlan(
                $runtime->planAssets($componentKeys, $shellRuntimeTags),
            );
        }
        unset($page);
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
                $localeUrls->rootUrl(),
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

        $pagesToRender = $pages;
        $selectedPageUrl = null;
        if ($onlyPage !== null) {
            $selectedPageUrl = $this->normalizePageSelector($onlyPage);
            $pagesToRender = array_filter(
                $pages,
                static fn (array $page): bool => (string) $page['url'] === $selectedPageUrl,
            );
            if (count($pagesToRender) !== 1) {
                throw new PortableConfigurationException(
                    'PORTABLE_PAGE_NOT_FOUND',
                    "No existing Docara page resolves to [$selectedPageUrl]. Run a full build after structural changes.",
                );
            }
            if (is_link($finalDestination) || ! $this->files->isDirectory($finalDestination)) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_BASE_MISSING',
                    'A single-page build requires an existing complete build. Run a full build first.',
                );
            }
            if ($searchPlan instanceof PortableSearchPlan) {
                $existingSearchIndexPath = rtrim($finalDestination, '/\\')
                    . '/_docara/search-index.json';
                $existingSearchIndex = json_decode(
                    (string) $this->files->get($existingSearchIndexPath),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
                $existingSearchHash = is_array($existingSearchIndex)
                    ? ($existingSearchIndex['content_sha256'] ?? null)
                    : null;
                if (! is_string($existingSearchHash)
                    || preg_match('/\A[a-f0-9]{64}\z/D', $existingSearchHash) !== 1
                ) {
                    throw new PortableConfigurationException(
                        'PORTABLE_INCREMENTAL_SEARCH_BASE_INVALID',
                        'The complete build has no valid search revision for an isolated page rebuild.',
                    );
                }
                $existingSearchUrl = preg_replace(
                    '/docara_v=[a-f0-9]{64}\z/D',
                    'docara_v=' . $existingSearchHash,
                    $searchPlan->indexUrl,
                );
                foreach ($pagesToRender as &$pageToRender) {
                    if (($pageToRender['search_enabled'] ?? false) === true) {
                        $pageToRender['search_index_url'] = $existingSearchUrl;
                    }
                }
                unset($pageToRender);
            }
        }

        $this->prepareDestination($root, $destination);
        if ($selectedPageUrl !== null && ! $this->files->copyDirectory($finalDestination, $destination)) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_BASE_COPY_FAILED',
                'The existing complete build could not be copied into the atomic candidate.',
            );
        }
        try {
            $result = collect();
            $diagnosticsByUrl = $selectedPageUrl === null
                ? []
                : $this->existingDiagnosticsByUrl($destination);
            $docaraOutputDirectory = rtrim($destination, '/\\') . '/_docara';
            $this->files->ensureDirectoryExists($docaraOutputDirectory);
            $this->files->put($docaraOutputDirectory . '/component-catalog.json', $componentCatalogJson);
            $this->files->put(
                $docaraOutputDirectory . '/page-metadata.json',
                $this->prettyCanonicalJson($this->pageMetadata($pages, $root, $documentationVersion)),
            );
            $localeDestinations = [$destination];
            foreach ($localeRegistry->all() as $definition) {
                if ($definition->publicPrefix !== '') {
                    $localeDestinations[] = rtrim($destination, '/\\') . '/' . $definition->publicPrefix;
                }
            }
            $localeDestinations = array_values(array_unique($localeDestinations));
            $catalogReceiptPath = rtrim($destination, '/\\') . '/.docara/component-catalog-pages.json';
            $this->files->ensureDirectoryExists(dirname($catalogReceiptPath));
            $this->files->put(
                $catalogReceiptPath,
                $this->prettyCanonicalJson($componentCatalogProjections[$buildLocale]['receipt']),
            );
            foreach ($componentCatalogProjections as $locale => $projection) {
                $localizedReceiptPath = rtrim($destination, '/\\')
                    . '/.docara/component-catalog-pages/' . rawurlencode($locale) . '.json';
                $this->files->ensureDirectoryExists(dirname($localizedReceiptPath));
                $this->files->put($localizedReceiptPath, $this->prettyCanonicalJson($projection['receipt']));
            }
            if (is_array($declarativeExampleProjection)) {
                $exampleReceipt = $this->prettyCanonicalJson($declarativeExampleProjection['receipt']);
                $this->files->put(
                    rtrim($destination, '/\\') . '/.docara/declarative-example-pages.json',
                    $exampleReceipt,
                );
                $this->files->put(
                    $docaraOutputDirectory . '/declarative-examples.json',
                    $exampleReceipt,
                );
            }
            foreach ($localeDestinations as $localeDestination) {
                foreach ($componentCatalogProjector->assets() as $relative => $bytes) {
                    $assetPath = rtrim($localeDestination, '/\\') . '/' . $relative;
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
            }

            if ($searchPlan instanceof PortableSearchPlan && $selectedPageUrl === null) {
                foreach ($localeDestinations as $localeDestination) {
                    $localizedDocaraDirectory = rtrim($localeDestination, '/\\') . '/_docara';
                    $this->files->ensureDirectoryExists($localizedDocaraDirectory);
                    $this->files->put($localizedDocaraDirectory . '/search-index.json', $searchPlan->indexJson);
                    $this->files->put($localizedDocaraDirectory . '/search.js', $searchPlan->runtime);
                }
            }

            foreach ($pagesToRender as $pageIndex => $page) {
                $declarative = null;
                $pageLocale = (string) $page['locale'];
                $page['branding'] = $brandPlan['pages'][$pageIndex];
                $pageTopology = $topologies[(string) $page['locale']] ?? [];
                $pageNavigation = $navigations[(string) $page['locale']] ?? [];
                $readingContext = $navigationBuilder->readingContextForUrl($pageTopology, (string) $page['url']);
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
                if (isset($page['declarative_example_kind'])) {
                    $page['breadcrumbs'] = $page['reading_breadcrumbs'] === true
                        ? $page['declarative_example_breadcrumbs']
                        : [];
                    $page['previous'] = $page['reading_previous_next'] === true
                        ? $page['declarative_example_previous']
                        : null;
                    $page['next'] = $page['reading_previous_next'] === true
                        ? $page['declarative_example_next']
                        : null;
                }
                if ($page['reading_toc'] !== true) {
                    $page['outline'] = [];
                }
                $activeNavigation = $navigationBuilder->activate(
                    $pageNavigation,
                    ($page['component_catalog_kind'] ?? null) === 'detail'
                        && ($page['navigation_hidden'] ?? false) === true
                        ? (string) $page['component_catalog_index_url']
                        : (($page['declarative_example_kind'] ?? null) === 'detail'
                            ? (string) $page['declarative_example_index_url']
                            : (string) $page['url']),
                );
                /** @var ResolvedPagePlan $declarativePlan */
                $declarativePlan = $page['plan'];
                $composition = PageCompositionContext::fromBuilder(
                    $page['branding'],
                    (string) $page['home_url'],
                    $activeNavigation,
                    $page['outline'],
                    is_array($page['ui_copy'] ?? null) ? $page['ui_copy'] : [],
                    is_array($declarativePlan->configuration['header_navigation'] ?? null)
                        ? $declarativePlan->configuration['header_navigation']
                        : [],
                    (string) $page['url'],
                );
                $page['header_navigation'] = $composition->headerNavigation;
                $declarativePipeline ??= DeclarativePipeline::bundled(
                    $declarativePlan->frameworkLock,
                    $this->markdown,
                    PortableDocumentIds::reserved(),
                );
                $outlineDepth = (int) data_get($declarativePlan->configuration, 'reading.toc_depth', 3);
                $layoutConfiguration = is_array($declarativePlan->configuration['layout'] ?? null)
                    ? $declarativePlan->configuration['layout']
                    : [];
                $generatedProjection = isset($page['component_catalog_kind'])
                    || isset($page['declarative_example_kind']);
                $declarative = $generatedProjection
                    ? $declarativePipeline->buildGenerated(
                        $declarativePlan->markdown,
                        $declarativePlan->page,
                        (string) $page['output'],
                        (string) $page['title'],
                        $outlineDepth,
                        (string) $page['content_html'],
                        $composition,
                        $layoutConfiguration,
                        $declarativePlan->provenance,
                    )
                    : $declarativePipeline->build(
                        $declarativePlan->markdown,
                        $declarativePlan->page,
                        (string) $page['output'],
                        (string) $page['title'],
                        $outlineDepth,
                        $composition,
                        $layoutConfiguration,
                        $declarativePlan->provenance,
                    );
                if ($generatedProjection) {
                    $generatedHash = hash('sha256', (string) $page['content_html']);
                    $declarativeHash = hash('sha256', (string) ($declarative->artifact->hydration['regions']['main'] ?? ''));
                    if (! hash_equals($generatedHash, $declarativeHash)) {
                        throw new PortableConfigurationException(
                            'DECLARATIVE_GENERATED_CONTENT_PARITY_FAILED',
                            "Generated page [{$page['url']}] changed during declarative projection.",
                        );
                    }
                }
                $page['declarative_pipeline'] = [
                    'status' => 'published',
                    'plan_hash' => $declarative->plan->canonicalHash(),
                    'assets' => $declarative->artifact->assets,
                ];
                $outputPath = rtrim($destination, '/\\') . '/' . $page['output'];
                $this->files->ensureDirectoryExists(dirname($outputPath));
                $rendered = $this->publisher->render(
                    $page,
                    $activeNavigation,
                    $siteTitle,
                    $page['components']->assetPlan,
                    $declarative,
                );
                $rendered = $localeLinkProjectors[$pageLocale]->project($rendered);
                $this->files->put($outputPath, $rendered);

                /** @var ResolvedPagePlan $plan */
                $plan = $page['plan'];
                $record = [
                    'canonical_hash' => $plan->canonicalHash(),
                    'page_path' => $page['page_path'],
                    'page_source_kind' => $page['page_source_kind'] ?? 'generated_projection',
                    'title' => $page['title'],
                    'description' => $page['description'],
                    'locale' => $page['locale'],
                    'output' => $page['output'],
                    'url' => $page['url'],
                    'resolved_page_plan' => $plan->toArray(),
                    'component_runtime' => $page['components']->toArray(),
                    'publisher' => [
                        'id' => $this->publisher->id(),
                        'html_sha256' => hash('sha256', $rendered),
                    ],
                    'declarative_pipeline' => $page['declarative_pipeline'],
                ];
                $diagnosticsByUrl[(string) $page['url']] = $record;
                $result->put((string) $page['url'], $record);
            }
            $redirectPublisher->publish($redirectPlan, $destination);
            $localeRoutePublisher->publish($localeRoutePlan, $destination);
            $this->copyContentAssets($contentAssets, $destination);
            $brandPublisher->publish($brandPlan['assets'], $destination);
            foreach ($localeDestinations as $localeDestination) {
                $this->publishFrameworkAssets($catalogBasePlan->frameworkLock, $localeDestination);
                (new PortablePublisherAssetPublisher($this->files))->publish($localeDestination);
            }
            $diagnosticPath = rtrim($destination, '/\\') . '/.docara/resolved-page-plans.json';
            $this->files->ensureDirectoryExists(dirname($diagnosticPath));
            $this->files->put($diagnosticPath, $this->prettyCanonicalJson([
                'schema' => 'docara.resolved_page_plans.v1',
                'build' => [
                    'locale' => $buildLocale,
                    'documentation_version' => $documentationVersion,
                ],
                'pages' => $this->orderedDiagnostics($pages, $diagnosticsByUrl),
            ]));
            $this->promoteCandidate($root, $destination, $finalDestination);
        } catch (\Throwable $exception) {
            if ($this->files->isDirectory($destination) && ! is_link($destination)) {
                $this->files->deleteDirectory($destination);
            }
            throw $exception;
        }

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

        return FilesystemPath::normalize($real);
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
        if (! FilesystemPath::isWithin($real, $root)) {
            throw new PortableConfigurationException('PATH_ESCAPE_FORBIDDEN', 'content_root escapes the portable site root.');
        }

        return $real;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, mixed>
     */
    private function pageMetadata(array $pages, string $root, string $documentationVersion): array
    {
        $records = [];
        foreach ($pages as $page) {
            $source = (string) ($page['page_path'] ?? '');
            $record = [
                'url' => (string) ($page['url'] ?? '/'),
                'title' => (string) ($page['title'] ?? ''),
                'locale' => (string) ($page['locale'] ?? ''),
                'source' => $source,
                'documentation_version' => $documentationVersion,
                'updated_at' => null,
                'revision' => null,
                'author' => null,
            ];
            $sourcePath = $source !== '' && ! str_starts_with($source, '@') ? $root . '/' . ltrim($source, '/') : null;
            if ($sourcePath !== null && is_file($sourcePath)) {
                $record['updated_at'] = gmdate(DATE_ATOM, (int) filemtime($sourcePath));
                try {
                    $process = new Process(['git', '-C', $root, 'log', '-1', '--format=%cI%x00%h%x00%an', '--', $source]);
                    $process->setTimeout(5);
                    $process->run();
                    if ($process->isSuccessful()) {
                        $parts = explode("\0", trim($process->getOutput()), 3);
                        if (count($parts) === 3) {
                            [$record['updated_at'], $record['revision'], $record['author']] = $parts;
                        }
                    }
                } catch (\Throwable) {
                    // A portable source tree may intentionally be outside Git.
                }
            }
            $records[] = $record;
        }
        usort($records, static fn (array $left, array $right): int => strcmp($left['url'], $right['url']));

        return [
            'schema' => 'docara.page_metadata.v1',
            'documentation_version' => $documentationVersion,
            'pages' => $records,
        ];
    }

    /** @return array{url:string,output:string} */
    private function route(
        ResolvedPagePlan $plan,
        string $contentRoot,
        LocaleUrlProjector $urls,
        string $locale,
    ): array {
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

        return $urls->page($locale, $encoded);
    }

    private function translationKey(string $page, string $contentRoot): string
    {
        return substr($page, strlen(rtrim($contentRoot, '/') . '/'));
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

    private function pageDescription(ResolvedPagePlan $plan): string
    {
        $configured = trim((string) ($plan->configuration['description'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        $paragraph = [];
        $insideFence = false;
        foreach (preg_split('/\R/u', $plan->markdown) ?: [] as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '```') || str_starts_with($trimmed, '~~~')) {
                $insideFence = ! $insideFence;

                continue;
            }
            if ($insideFence) {
                continue;
            }
            if ($trimmed === '') {
                if ($paragraph !== []) {
                    break;
                }

                continue;
            }
            if ($paragraph === [] && (
                str_starts_with($trimmed, '#')
                || str_starts_with($trimmed, ':::')
                || str_starts_with($trimmed, '<!--')
                || str_starts_with($trimmed, '|')
                || preg_match('/^(?:[-*+]\s|\d+[.)]\s|>\s)/u', $trimmed) === 1
            )) {
                continue;
            }
            $paragraph[] = $trimmed;
        }

        $description = trim(implode(' ', $paragraph));
        $description = preg_replace('/!\[([^]]*)]\([^)]*\)/u', '$1', $description) ?? $description;
        $description = preg_replace('/\[([^]]+)]\([^)]*\)/u', '$1', $description) ?? $description;
        $description = preg_replace('/[*_`~]+/u', '', $description) ?? $description;

        return trim($description);
    }

    private function homeUrl(string $baseUrl): string
    {
        $base = trim($baseUrl, '/');

        return $base === '' ? '/' : '/' . $base . '/';
    }

    private function localeHomeUrl(string $baseUrl, string $publicPrefix): string
    {
        $path = implode('/', array_filter([
            trim($baseUrl, '/'),
            trim($publicPrefix, '/'),
        ], static fn (string $part): bool => $part !== ''));

        return $path === '' ? '/' : '/' . $path . '/';
    }

    private function normalizePageSelector(string $selector): string
    {
        $selector = trim($selector);
        $path = parse_url($selector, PHP_URL_PATH);
        if (! is_string($path) || $path === '' || str_contains($path, '\\')) {
            throw new PortableConfigurationException(
                'PORTABLE_PAGE_SELECTOR_INVALID',
                'The page selector must be a public URL such as [/ru/components/badge/].',
            );
        }

        $segments = array_values(array_filter(
            explode('/', trim($path, '/')),
            static fn (string $segment): bool => $segment !== '',
        ));
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new PortableConfigurationException(
                    'PORTABLE_PAGE_SELECTOR_INVALID',
                    'The page selector contains a forbidden path segment.',
                );
            }
        }

        return $segments === [] ? '/' : '/' . implode('/', $segments) . '/';
    }

    /** @return array<string, array<string, mixed>> */
    private function existingDiagnosticsByUrl(string $destination): array
    {
        $path = rtrim($destination, '/\\') . '/.docara/resolved-page-plans.json';
        try {
            $document = json_decode((string) $this->files->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_DIAGNOSTICS_INVALID',
                'The existing build does not contain valid complete diagnostics. Run a full build first.',
                $exception,
            );
        }
        if (! is_array($document) || ! is_array($document['pages'] ?? null)) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_DIAGNOSTICS_INVALID',
                'The existing build diagnostics are incomplete. Run a full build first.',
            );
        }

        $indexed = [];
        foreach ($document['pages'] as $record) {
            if (! is_array($record) || ! is_string($record['url'] ?? null)) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_DIAGNOSTICS_INVALID',
                    'The existing build diagnostics contain an invalid page record. Run a full build first.',
                );
            }
            $indexed[$record['url']] = $record;
        }

        return $indexed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @param  array<string, array<string, mixed>>  $diagnosticsByUrl
     * @return array<int, array<string, mixed>>
     */
    private function orderedDiagnostics(array $pages, array $diagnosticsByUrl): array
    {
        $ordered = [];
        foreach ($pages as $page) {
            $url = (string) $page['url'];
            if (! isset($diagnosticsByUrl[$url])) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_DIAGNOSTICS_INCOMPLETE',
                    "The existing build has no diagnostic record for [$url]. Run a full build first.",
                );
            }
            $ordered[] = $diagnosticsByUrl[$url];
        }

        return $ordered;
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

    private function candidateDestination(string $root, string $destination): string
    {
        $this->assertDestinationShape($root, $destination);
        $candidate = rtrim($destination, '/\\') . '.docara-candidate';
        $this->assertDestinationShape($root, $candidate);

        return $candidate;
    }

    private function promoteCandidate(string $root, string $candidate, string $destination): void
    {
        $this->assertDestinationShape($root, $candidate);
        $this->assertDestinationShape($root, $destination);
        if (is_link($candidate) || ! $this->files->isDirectory($candidate)) {
            throw new PortableConfigurationException(
                'DESTINATION_CANDIDATE_INVALID',
                'The completed portable build candidate is missing or unsafe.',
            );
        }

        $rollback = rtrim($destination, '/\\') . '.docara-rollback';
        $this->assertDestinationShape($root, $rollback);
        if (is_link($rollback)) {
            throw new PortableConfigurationException(
                'DESTINATION_ROLLBACK_SYMLINK_FORBIDDEN',
                'Portable builds refuse to replace a symbolic-link rollback directory.',
            );
        }
        if ($this->files->isDirectory($rollback) && ! $this->files->deleteDirectory($rollback)) {
            throw new PortableConfigurationException(
                'DESTINATION_ROLLBACK_CLEANUP_FAILED',
                'A stale portable build rollback directory could not be removed.',
            );
        }

        $hasCurrent = $this->files->isDirectory($destination);
        if ($hasCurrent && ! @rename($destination, $rollback)) {
            throw new PortableConfigurationException(
                'DESTINATION_ROLLBACK_PREPARE_FAILED',
                'The current portable build could not be moved to the rollback directory.',
            );
        }
        if (@rename($candidate, $destination)) {
            if ($hasCurrent && ! $this->files->deleteDirectory($rollback)) {
                throw new PortableConfigurationException(
                    'DESTINATION_ROLLBACK_CLEANUP_FAILED',
                    'The accepted portable build was published, but its rollback directory could not be removed.',
                );
            }

            return;
        }

        if ($hasCurrent && ! @rename($rollback, $destination)) {
            throw new PortableConfigurationException(
                'DESTINATION_PROMOTION_AND_RESTORE_FAILED',
                'The portable candidate could not be published and the previous build could not be restored.',
            );
        }
        throw new PortableConfigurationException(
            'DESTINATION_PROMOTION_FAILED',
            'The portable candidate could not be published; the previous build was restored.',
        );
    }

    private function assertDestinationShape(string $root, string $destination): void
    {
        $normalizedRoot = FilesystemPath::normalize($root);
        $normalizedDestination = FilesystemPath::normalize($destination);
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
        array $contentPaths,
        array $site,
    ): void {
        $this->assertDestinationShape($root, $destination);
        $normalizedDestination = FilesystemPath::normalize($destination);
        $frameworkLock = (string) ($site['framework_lock'] ?? '');
        $inputs = [
            ...$contentPaths,
            $root . '/' . $frameworkLock,
            $root . '/docara.json',
        ];
        if (is_string($site['redirects_file'] ?? null)) {
            $inputs[] = $root . '/' . $site['redirects_file'];
        }

        foreach ($inputs as $input) {
            $normalizedInput = FilesystemPath::normalize($input);
            if ($normalizedDestination === $normalizedInput
                || str_starts_with($normalizedInput, $normalizedDestination . DIRECTORY_SEPARATOR)
                || str_starts_with($normalizedDestination, $normalizedInput . DIRECTORY_SEPARATOR)
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
    private function contentAssets(
        string $contentPath,
        array $generatedOutputs,
        string $publicPrefix = '',
    ): array {
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
                || $name === 'lang.json'
                || str_ends_with($name, '.page.json')
            ) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($contentPath))), '/');
            $publishedRelative = implode('/', array_filter([
                trim($publicPrefix, '/'),
                $relative,
            ], static fn (string $part): bool => $part !== ''));
            $normalizedRelative = strtolower($publishedRelative);
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
                'relative' => $publishedRelative,
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
