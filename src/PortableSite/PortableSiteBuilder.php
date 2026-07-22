<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Illuminate\Support\Collection;
use JsonException;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\Declarative\Adapter\LarenaContractAdapter;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\DeclarativePipeline;
use Simai\Docara\Declarative\Preview\DeclarativePreviewLinkProjector;
use Simai\Docara\Declarative\Preview\DeclarativePreviewRenderer;
use Simai\Docara\Declarative\Preview\DeclarativePreviewRouteMap;
use Simai\Docara\Declarative\Semantic\SemanticParityChecker;
use Simai\Docara\Declarative\Semantic\ShellStructuralParityChecker;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkAssetPlan;
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
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\Portable\SchemaRepository;
use Simai\Docara\Smart\SmartRegistry;

final readonly class PortableSiteBuilder
{
    private PortablePagePublisher $publisher;

    public function __construct(
        private Filesystem $files,
        private PortableMarkdownRenderer $markdown,
        private PortableHtmlRenderer $html,
        ?PortablePagePublisher $publisher = null,
    ) {
        $rollback = getenv('DOCARA_PORTABLE_PUBLISHER');
        if ($publisher instanceof PortablePagePublisher) {
            $this->publisher = $publisher;
        } elseif ($rollback === false || $rollback === '' || $rollback === 'declarative') {
            $this->publisher = new DeclarativePortablePagePublisher;
        } elseif ($rollback === 'legacy') {
            $this->publisher = new LegacyPortablePagePublisher($html);
        } else {
            throw new PortableConfigurationException(
                'PORTABLE_PUBLISHER_INVALID',
                'DOCARA_PORTABLE_PUBLISHER must be [declarative] or [legacy].',
            );
        }
    }

    /** @return Collection<string, array<string, mixed>> */
    public function build(string $root, string $destination): Collection
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
        foreach ($localeRegistry->all() as $locale => $definition) {
            $localeContentPath = $this->confinedDirectory($root, $definition->contentRoot);
            $localePagePaths = $this->markdownFiles($root, $localeContentPath);
            if ($localePagePaths === []) {
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
            array_push($pagePaths, ...$localePagePaths);
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
            $components = $runtime->extract($plan->markdown, $plan->page);
            $outline = (new PortableDocumentOutlineBuilder)->build(
                $this->markdown->render($components->markdownWithPlaceholders),
                (int) data_get($plan->configuration, 'reading.toc_depth', 3),
                $this->html->reservedDocumentIds(),
            );
            $contentHtml = $components->hydrate($outline['html']);
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
            $componentIds = array_values(array_unique(array_map(
                static fn (array $call): string => (string) ($call['id'] ?? ''),
                $components->normalizedCalls,
            )));
            $unsupportedDeclarativeComponents = array_values(array_diff(
                $componentIds,
                ['ui.alert', 'ui.button'],
            ));
            sort($unsupportedDeclarativeComponents, SORT_STRING);

            $pages[] = [
                'plan' => $plan,
                'page_path' => $pagePath,
                'title' => $title,
                'description' => (string) ($plan->configuration['description'] ?? ''),
                'locale' => $pageLocale,
                'direction' => $localeDefinition->direction,
                'translation_key' => $this->translationKey($plan->page, $localeDefinition->contentRoot),
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
                'reading_mobile_toc' => (string) data_get($plan->configuration, 'reading.mobile_toc', 'auto'),
                'reading_previous_next' => (bool) data_get($plan->configuration, 'reading.previous_next', true),
                'outline' => $outline['items'],
                'url' => $route['url'],
                'output' => $route['output'],
                'home_url' => $localeUrls->home($pageLocale),
                'content_html' => $contentHtml,
                'components' => $components,
                'component_calls' => $components->normalizedCalls,
                'declarative_supported' => $unsupportedDeclarativeComponents === [],
                'declarative_unsupported_components' => $unsupportedDeclarativeComponents,
            ];
        }

        $authoredPages = $pages;
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
        $componentCatalogProjector = new PortableComponentCatalogProjector(
            $this->markdown,
            translator: $translator,
        );
        $componentCatalogProjections = [];
        foreach ($localeRegistry->all() as $locale => $definition) {
            $localeCatalogBasePlan = $locale === $buildLocale
                ? $catalogBasePlan
                : $loader->resolveGeneratedBase($definition->contentRoot . '/components/catalog/index.md');
            if (CanonicalJson::encode($localeCatalogBasePlan->frameworkLock) !== $frameworkLockCanonical) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_LOCK_CHANGED_DURING_BUILD',
                    'The Framework lock changed while a localized component catalogue was being resolved.',
                );
            }
            $componentCatalogProjection = $componentCatalogProjector->project(
                catalog: $effectiveComponentCatalog,
                runtime: $runtime,
                basePlan: $localeCatalogBasePlan,
                contentRoot: $definition->contentRoot,
                baseUrl: $localeUrls->home($locale),
                homeUrl: $localeUrls->home($locale),
                outputPrefix: $definition->publicPrefix,
                reservedDocumentIds: $this->html->reservedDocumentIds(),
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
                reservedDocumentIds: $this->html->reservedDocumentIds(),
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
        $previewRoutes = DeclarativePreviewRouteMap::fromPages(
            array_values(array_filter(
                $pages,
                static fn (array $page): bool => ($page['locale'] ?? null) === $buildLocale,
            )),
            $localeRegistry->get($buildLocale)->publicPrefix,
        );

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

        $this->prepareDestination($root, $destination);
        try {
            $result = collect();
            $diagnostics = [];
            $previewRecords = [];
            $previewRenderer = new DeclarativePreviewRenderer(translator: $translator);
            $previewProjector = new DeclarativePreviewLinkProjector;
            $previewAssetPlan = null;

            $docaraOutputDirectory = rtrim($destination, '/\\') . '/_docara';
            $this->files->ensureDirectoryExists($docaraOutputDirectory);
            $this->files->put($docaraOutputDirectory . '/component-catalog.json', $componentCatalogJson);
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

            if ($searchPlan instanceof PortableSearchPlan) {
                foreach ($localeDestinations as $localeDestination) {
                    $localizedDocaraDirectory = rtrim($localeDestination, '/\\') . '/_docara';
                    $this->files->ensureDirectoryExists($localizedDocaraDirectory);
                    $this->files->put($localizedDocaraDirectory . '/search-index.json', $searchPlan->indexJson);
                    $this->files->put($localizedDocaraDirectory . '/search.js', $searchPlan->runtime);
                }
            }

            foreach ($pages as $pageIndex => $page) {
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
                        ? (string) $page['component_catalog_index_url']
                        : (($page['declarative_example_kind'] ?? null) === 'detail'
                            ? (string) $page['declarative_example_index_url']
                            : (string) $page['url']),
                );
                if (($page['declarative_supported'] ?? false) === true
                    || isset($page['component_catalog_kind'])
                    || isset($page['declarative_example_kind'])
                ) {
                    /** @var ResolvedPagePlan $declarativePlan */
                    $declarativePlan = $page['plan'];
                    $composition = PageCompositionContext::fromBuilder(
                        $page['branding'],
                        (string) $page['home_url'],
                        $activeNavigation,
                        $page['outline'],
                        is_array($page['ui_copy'] ?? null) ? $page['ui_copy'] : [],
                    );
                    $declarativePipeline ??= DeclarativePipeline::bundled(
                        $declarativePlan->frameworkLock,
                        $this->markdown,
                        $this->html->reservedDocumentIds(),
                    );
                    $declarativeArguments = [
                        $declarativePlan->markdown,
                        $declarativePlan->page,
                        (string) $page['output'],
                        (string) $page['title'],
                        (int) data_get($declarativePlan->configuration, 'reading.toc_depth', 3),
                    ];
                    $layoutConfiguration = is_array($declarativePlan->configuration['layout'] ?? null)
                        ? $declarativePlan->configuration['layout']
                        : [];
                    $generatedProjection = isset($page['component_catalog_kind'])
                        || isset($page['declarative_example_kind']);
                    $declarative = $generatedProjection
                        ? $declarativePipeline->buildGenerated(
                            $declarativeArguments[0],
                            $declarativeArguments[1],
                            $declarativeArguments[2],
                            $declarativeArguments[3],
                            $declarativeArguments[4],
                            (string) $page['content_html'],
                            $composition,
                            $layoutConfiguration,
                            $declarativePlan->provenance,
                        )
                        : $declarativePipeline->build(
                            $declarativeArguments[0],
                            $declarativeArguments[1],
                            $declarativeArguments[2],
                            $declarativeArguments[3],
                            $declarativeArguments[4],
                            $composition,
                            $layoutConfiguration,
                            $declarativePlan->provenance,
                        );
                    if ($generatedProjection) {
                        $generatedHash = hash('sha256', (string) $page['content_html']);
                        $declarativeHash = hash(
                            'sha256',
                            (string) ($declarative->artifact->hydration['regions']['main'] ?? ''),
                        );
                        if (! hash_equals($generatedHash, $declarativeHash)) {
                            throw new PortableConfigurationException(
                                'DECLARATIVE_GENERATED_CONTENT_PARITY_FAILED',
                                "Generated page [{$page['url']}] changed during declarative projection.",
                            );
                        }
                        $parity = [
                            'status' => 'pass',
                            'mode' => 'trusted_generated_projection',
                            'legacy_hash' => $generatedHash,
                            'declarative_hash' => $declarativeHash,
                        ];
                    } else {
                        $parity = (new SemanticParityChecker)->assertEquivalent(
                            (string) $page['title'],
                            (string) $page['content_html'],
                            $page['components']->normalizedCalls,
                            $declarative,
                        )->toArray();
                    }
                    $shellParity = (new ShellStructuralParityChecker)->assertEquivalent(
                        $composition,
                        $declarative->plan,
                    );
                    $larenaContract = (new LarenaContractAdapter)->adapt($declarative->plan);
                    $page['declarative_pipeline'] = $declarative->toArray() + [
                        'semantic_parity' => $parity,
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
                    if (! $generatedProjection && ($page['locale'] ?? null) === $buildLocale) {
                        $previewUrl = $previewRoutes->previewUrl((string) $page['url']);
                        $previewOutput = $previewRoutes->previewOutput((string) $page['url']);
                        if ($previewUrl === null || $previewOutput === null) {
                            throw new PortableConfigurationException(
                                'DECLARATIVE_PREVIEW_ROUTE_REQUIRED',
                                "Declarative page [{$page['url']}] has no preview route.",
                            );
                        }
                        $previewHtml = $previewRenderer->page(
                            (string) $page['locale'],
                            $documentationVersion,
                            (string) $page['title'],
                            (string) $page['url'],
                            $previewRoutes->indexUrl,
                            $previewProjector->project(
                                $localeLinkProjectors[$pageLocale]->project(
                                    $declarative->artifact->html,
                                ),
                                $previewRoutes,
                            ),
                            $page['components']->assetPlan,
                        );
                        $previewPath = rtrim($destination, '/\\') . '/' . $previewOutput;
                        $this->files->ensureDirectoryExists(dirname($previewPath));
                        $this->files->put($previewPath, $previewHtml);
                        $page['declarative_pipeline']['preview'] = [
                            'status' => 'rendered',
                            'url' => $previewUrl,
                            'output' => $previewOutput,
                            'html_sha256' => hash('sha256', $previewHtml),
                            'legacy_url' => (string) $page['url'],
                        ];
                    }
                } elseif (array_key_exists('declarative_unsupported_components', $page)) {
                    $page['declarative_pipeline'] = [
                        'status' => 'not_in_vertical_slice',
                        'unsupported_components' => $page['declarative_unsupported_components'],
                        'supported_components' => ['ui.alert', 'ui.button'],
                    ];
                }
                if (array_key_exists('declarative_supported', $page)
                    && ($page['locale'] ?? null) === $buildLocale
                ) {
                    $previewAssetPlan ??= $page['components']->assetPlan;
                    $previewRecords[] = [
                        'title' => (string) $page['title'],
                        'legacy_url' => (string) $page['url'],
                        'preview_url' => $page['declarative_pipeline']['preview']['url'] ?? null,
                        'preview_output' => $page['declarative_pipeline']['preview']['output'] ?? null,
                        'status' => $page['declarative_pipeline']['preview']['status'] ?? 'skipped',
                        'unsupported_components' => $page['declarative_unsupported_components'],
                        'html_sha256' => $page['declarative_pipeline']['preview']['html_sha256'] ?? null,
                    ];
                }
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
                    'output' => $page['output'],
                    'url' => $page['url'],
                    'resolved_page_plan' => $plan->toArray(),
                    'component_runtime' => $page['components']->toArray(),
                    'publisher' => [
                        'id' => $this->publisher->id(),
                        'html_sha256' => hash('sha256', $rendered),
                        'rollback' => $this->publisher instanceof LegacyPortablePagePublisher,
                    ],
                    'declarative_pipeline' => $page['declarative_pipeline'] ?? [
                        'status' => 'not_applicable_generated_page',
                    ],
                ];
                $diagnostics[] = $record;
                $result->put((string) $page['url'], $record);
            }
            if (! $previewAssetPlan instanceof FrameworkAssetPlan) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PREVIEW_ASSET_PLAN_REQUIRED',
                    'Declarative preview requires an authored page asset plan.',
                );
            }
            $previewIndexHtml = $previewRenderer->index(
                $buildLocale,
                $documentationVersion,
                $siteTitle,
                $previewRoutes->receiptUrl,
                $previewRecords,
                $previewAssetPlan,
            );
            $previewIndexPath = rtrim($destination, '/\\') . '/' . $previewRoutes->indexOutput;
            $this->files->ensureDirectoryExists(dirname($previewIndexPath));
            $this->files->put($previewIndexPath, $previewIndexHtml);
            $previewReceipt = [
                'schema' => 'docara.declarative_preview_receipt.v1',
                'build' => [
                    'locale' => $buildLocale,
                    'documentation_version' => $documentationVersion,
                ],
                'index' => [
                    'url' => $previewRoutes->indexUrl,
                    'output' => $previewRoutes->indexOutput,
                    'html_sha256' => hash('sha256', $previewIndexHtml),
                ],
                'routes' => $previewRoutes->toArray(),
                'pages' => $previewRecords,
                'nonclaims' => [
                    'primary_publisher_switched' => true,
                    'full_visual_parity' => false,
                    'production_ready' => false,
                ],
            ];
            $this->files->put(
                rtrim($destination, '/\\') . '/' . $previewRoutes->receiptOutput,
                $this->prettyCanonicalJson($previewReceipt),
            );

            $redirectPublisher->publish($redirectPlan, $destination);
            $localeRoutePublisher->publish($localeRoutePlan, $destination);
            $this->copyContentAssets($contentAssets, $destination);
            $brandPublisher->publish($brandPlan['assets'], $destination);
            foreach ($localeDestinations as $localeDestination) {
                $this->publishFrameworkAssets($catalogBasePlan->frameworkLock, $localeDestination);
                $this->publishPagePublisherAssets($localeDestination);
            }
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
        array $contentPaths,
        array $site,
    ): void {
        $this->assertDestinationShape($root, $destination);
        $normalizedDestination = rtrim(str_replace('\\', '/', $destination), '/');
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

    private function publishPagePublisherAssets(string $destination): void
    {
        if (! $this->publisher instanceof DeclarativePortablePagePublisher) {
            return;
        }
        foreach (['declarative-shell.css', 'declarative-shell.js'] as $name) {
            $source = dirname(__DIR__, 2) . '/resources/portable/' . $name;
            if (! is_file($source) || is_link($source)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PUBLISHER_ASSET_MISSING',
                    "Declarative publisher asset [$name] is missing or unsafe.",
                );
            }
            $bytes = file_get_contents($source);
            if (! is_string($bytes) || $bytes === '') {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PUBLISHER_ASSET_INVALID',
                    "Declarative publisher asset [$name] is invalid.",
                );
            }
            $target = rtrim($destination, '/\\') . '/_docara/' . $name;
            if ($this->files->put($target, $bytes) === false
                || ! hash_equals(hash('sha256', $bytes), (string) hash_file('sha256', $target))
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_PUBLISHER_ASSET_PUBLICATION_FAILED',
                    $name,
                );
            }
        }
        foreach (SmartRegistry::bundled()->assets() as $key => $asset) {
            $source = dirname(__DIR__, 2) . '/resources/' . $asset['path'];
            if (! is_file($source) || is_link($source)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_SMART_ASSET_MISSING',
                    "Registered Smart asset [$key] is missing or unsafe.",
                );
            }
            $bytes = file_get_contents($source);
            if (! is_string($bytes) || $bytes === '') {
                throw new PortableConfigurationException(
                    'DECLARATIVE_SMART_ASSET_INVALID',
                    "Registered Smart asset [$key] is invalid.",
                );
            }
            $target = rtrim($destination, '/\\') . '/_docara/' . $asset['public'];
            $this->files->ensureDirectoryExists(dirname($target));
            if ($this->files->put($target, $bytes) === false
                || ! hash_equals(hash('sha256', $bytes), (string) hash_file('sha256', $target))
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_SMART_ASSET_PUBLICATION_FAILED',
                    $key,
                );
            }
        }
    }

    private function prettyCanonicalJson(mixed $value): string
    {
        return CanonicalJson::encodePretty($value);
    }
}
