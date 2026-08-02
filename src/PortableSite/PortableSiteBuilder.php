<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Illuminate\Support\Collection;
use JsonException;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\Content\PageSource;
use Simai\Docara\Content\PageSourceLocator;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\DeclarativePipeline;
use Simai\Docara\Declarative\Rendering\RenderArtifact;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Smart\CompositeSmartPlanResolver;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\DocumentIr;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\I18n\ContentLanguageRepository;
use Simai\Docara\I18n\LocaleInternalLinkProjector;
use Simai\Docara\I18n\LocaleMissingPagePolicy;
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
use Simai\Docara\Smart\Runtime\ProjectSmartRuntime;
use Simai\Docara\Smart\SmartRegistry;
use Symfony\Component\Process\Process;

final readonly class PortableSiteBuilder
{
    private PortablePagePublisher $publisher;

    private PageBuilder $pageBuilder;

    private ?\Closure $observer;

    private bool $publisherInjected;

    private bool $pageBuilderInjected;

    public function __construct(
        private Filesystem $files,
        private PortableMarkdownRenderer $markdown,
        ?PortablePagePublisher $publisher = null,
        ?PageBuilder $pageBuilder = null,
        ?\Closure $observer = null,
    ) {
        $this->publisherInjected = $publisher !== null;
        $this->pageBuilderInjected = $pageBuilder !== null;
        $this->publisher = $publisher ?? new DeclarativePortablePagePublisher;
        $this->pageBuilder = $pageBuilder ?? new PageBuilder($markdown);
        $this->observer = $observer;
    }

    /** @return Collection<string, array<string, mixed>> */
    public function build(string $root, string $destination, ?string $onlyPage = null): Collection
    {
        // Validate the caller's lexical root before realpath normalization so
        // link, link/ and link/. cannot hide the same symbolic-link root.
        $loader = new PortableConfigurationLoader($root);
        $root = $this->realDirectory($root, 'PORTABLE_ROOT_INVALID');
        $site = $this->siteConfiguration($root);
        $frameworkLock = FrameworkLock::fromJsonFile(
            $root . '/' . ltrim((string) $site['framework_lock'], '/'),
        )->toArray();
        $projectSmart = ProjectSmartRuntime::fromSite($root, $site, $frameworkLock);
        $smartRegistry = $projectSmart?->registry ?? SmartRegistry::bundled();
        $gateway = $projectSmart?->gateway ?? SmartComponentGateway::bundled($frameworkLock);
        $templates = $projectSmart?->templates ?? new TrustedTemplateRegistry(smarts: $smartRegistry);
        $smartRenderer = $projectSmart?->renderer ?? new SmartRenderer($templates);
        $markdown = new PortableMarkdownRenderer(components: $gateway);
        $pageBuilder = $this->pageBuilderInjected
            ? $this->pageBuilder
            : new PageBuilder($markdown, smartRenderer: $smartRenderer);
        $publisher = ! $this->publisherInjected
            ? new DeclarativePortablePagePublisher(
                $templates,
                $smartRegistry,
                composites: new CompositeSmartPlanResolver(smarts: $smartRegistry),
                smartRenderer: $smartRenderer,
            )
            : $this->publisher;
        $explicitLocaleRegistry = is_array($site['locales'] ?? null) && $site['locales'] !== [];
        $localeRegistry = LocaleRegistry::fromSite($site);
        $missingPagePolicy = LocaleMissingPagePolicy::fromSite($site);
        $localeRouting = LocaleRoutingPolicy::fromSite($site, $localeRegistry);
        $localeUrls = new LocaleUrlProjector(
            (string) ($site['base_url'] ?? '/'),
            $localeRegistry,
            $localeRouting,
        );
        $contentLanguages = new ContentLanguageRepository($root);
        $translator = new Translator(
            $localeRegistry,
            $contentLanguages,
            false,
        );
        $uiCopy = new UiCopy($translator);
        $runtimeMetadata = new PortableRuntimeMetadata(dirname(__DIR__, 2));
        $engineRevision = $runtimeMetadata->package();
        $dependencyLock = $runtimeMetadata->dependencies();
        $buildLocale = $localeRegistry->default()->tag->value();
        $documentationVersion = (string) ($site['documentation_version'] ?? 'current');
        $defaultLocale = $localeRegistry->default();
        $contentRoot = $defaultLocale->contentRoot;
        $contentPath = $this->confinedDirectory($root, $contentRoot);
        $contentContexts = [];
        $pagePaths = [];
        $pageSources = [];
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
            array_push($pageSources, ...$localePageSources);
            array_push(
                $pagePaths,
                ...array_map(static fn (PageSource $source): string => $source->path, $localePageSources),
            );
        }
        $this->assertMissingPagePolicy($pageSources, $localeRegistry, $missingPagePolicy);
        sort($pagePaths, SORT_STRING);
        $this->assertDestinationInputBoundary(
            $root,
            $destination,
            array_values(array_column($contentContexts, 'path')),
            $site,
        );
        $finalDestination = $destination;
        $destination = $this->candidateDestination($root, $finalDestination);
        $selectedPageUrl = $onlyPage === null ? null : $this->normalizePageSelector($onlyPage);
        $existingDiagnostics = [];
        $earlyPhysicalSelection = false;
        if ($selectedPageUrl !== null) {
            if (is_link($finalDestination) || ! $this->files->isDirectory($finalDestination)) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_BASE_MISSING',
                    'A single-page build requires an existing complete build. Run a full build first.',
                );
            }
            $existingDiagnostics = $this->existingDiagnosticsByUrl($finalDestination);
            $existingBuild = $this->existingBuildProvenance($finalDestination);
            if (! hash_equals(
                (string) ($existingBuild['engine']['tree_sha256'] ?? ''),
                (string) $engineRevision['tree_sha256'],
            ) || ! hash_equals(
                (string) ($existingBuild['dependencies']['runtime_tuple_sha256'] ?? ''),
                (string) $dependencyLock['runtime_tuple_sha256'],
            )) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_ENGINE_CHANGED',
                    'The engine or dependency tuple changed after the complete build. Run a full build before rebuilding one page.',
                );
            }
            if (! isset($existingDiagnostics[$selectedPageUrl])) {
                throw new PortableConfigurationException(
                    'PORTABLE_PAGE_NOT_FOUND',
                    "No existing Docara page resolves to [$selectedPageUrl]. Run a full build after structural changes.",
                );
            }
            foreach ($pageSources as $source) {
                $sourceUrl = $localeUrls->page($source->locale, $source->route)['url'];
                if ($sourceUrl !== $selectedPageUrl) {
                    continue;
                }
                $pagePaths = [$source->path];
                $earlyPhysicalSelection = true;

                break;
            }
        }
        $pages = [];
        $outputs = [];
        $frameworkLockCanonical = null;
        $runtime = null;
        $declarativePipeline = null;
        foreach ($pagePaths as $pagePath) {
            $this->observe('page.build', $pagePath);
            $plan = $loader->resolve($pagePath);
            $pageLocale = (string) ($plan->configuration['locale'] ?? $buildLocale);
            if (! $explicitLocaleRegistry && $pageLocale !== $buildLocale) {
                throw new PortableConfigurationException(
                    'PORTABLE_BUILD_LOCALE_MISMATCH',
                    "Page [$pagePath] locale [$pageLocale] does not match build locale [$buildLocale].",
                );
            }
            $localeDefinition = $localeRegistry->get($pageLocale);
            $route = $this->route(
                $plan,
                $localeDefinition->contentRoot,
                $localeUrls,
                $pageLocale,
            );
            if (($plan->frontMatter['draft'] ?? false) === true) {
                if ($selectedPageUrl !== null) {
                    throw new PortableConfigurationException(
                        'PAGE_DRAFT_NOT_PUBLISHED',
                        "Draft page for locale [$pageLocale], route [{$route['url']}], source [$pagePath] cannot be rebuilt as a public page.",
                    );
                }

                continue;
            }
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
            try {
                $pageResult = $pageBuilder->build(
                    $plan,
                    $root,
                    $runtime,
                    (int) data_get($plan->configuration, 'reading.toc_depth', 3),
                );
            } catch (\Throwable $exception) {
                throw new PortableConfigurationException(
                    'PAGE_BUILDER_FAILED',
                    "PageBuilder failed for locale [$pageLocale], route [{$route['url']}], source [$pagePath]: {$exception->getMessage()}",
                    previous: $exception,
                );
            }
            $components = $pageResult->frameworkComponents;
            $outline = $pageResult->outline;
            $contentHtml = $pageResult->contentHtml;
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
                'translation_key' => $this->translationKey($plan, $localeDefinition->contentRoot),
                'tags' => $plan->frontMatter['tags'] ?? [],
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
                'document_artifact' => $pageResult->documentArtifact,
                'document_ir' => $pageResult->document,
                'components' => $components,
                'component_calls' => $components->normalizedCalls,
            ];
        }

        $buildBasePlan = $pages[0]['plan'] ?? null;
        if (! $buildBasePlan instanceof ResolvedPagePlan || ! $runtime instanceof FrameworkComponentRuntime) {
            throw new PortableConfigurationException(
                'FRAMEWORK_RUNTIME_MISSING',
                'The build requires at least one authored page and an initialized component runtime.',
            );
        }
        $effectiveComponentCatalog = EffectiveComponentCatalogBuilder::bundled(
            FrameworkLock::fromArray($buildBasePlan->frameworkLock),
        )->build();

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
        $contextPages = $earlyPhysicalSelection
            ? $this->contextPagesFromDiagnostics($existingDiagnostics, $pages)
            : $pages;
        if ($earlyPhysicalSelection) {
            foreach ($contextPages as $contextPage) {
                $outputs[(string) $contextPage['output']] = (string) $contextPage['page_path'];
            }
        }
        $translations = [];
        foreach ($contextPages as $page) {
            $translations[(string) ($page['translation_key'] ?? $page['page_path'])][(string) $page['locale']] = [
                'url' => (string) $page['url'],
                'label' => $localeRegistry->get((string) $page['locale'])->label,
            ];
        }
        foreach ($pages as &$page) {
            $pageLocale = (string) $page['locale'];
            $page['direction'] = $localeRegistry->get($pageLocale)->direction;
            $page['ui_copy'] = $uiCopy->forLocale($pageLocale);
            $contentMessages = $contentLanguages->messages($localeRegistry->get($pageLocale));
            foreach (['navigation.backlinks_heading', 'navigation.backlinks_empty'] as $messageId) {
                if (isset($contentMessages[$messageId])) {
                    $page['ui_copy'][$messageId] = $contentMessages[$messageId];
                }
            }
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
        $backlinkHydrator = new PortableBacklinkHydrator;
        $backlinkReceipt = null;
        if ($earlyPhysicalSelection) {
            $backlinkReceiptPath = rtrim($finalDestination, '/\\') . '/.docara/backlinks.json';
            try {
                $backlinkReceipt = json_decode(
                    (string) $this->files->get($backlinkReceiptPath),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $exception) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_BACKLINK_BASE_INVALID',
                    'The complete build has no valid backlink projection for an isolated page rebuild.',
                    $exception,
                );
            }
            if (! is_array($backlinkReceipt)
                || ($backlinkReceipt['schema'] ?? null) !== 'docara.backlinks.v1'
                || ! is_array($backlinkReceipt['targets'] ?? null)
                || ! is_string($backlinkReceipt['content_sha256'] ?? null)
                || ! hash_equals(
                    $backlinkReceipt['content_sha256'],
                    hash('sha256', CanonicalJson::encode($backlinkReceipt['targets'])),
                )
            ) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_BACKLINK_BASE_INVALID',
                    'The complete build has no valid backlink projection for an isolated page rebuild.',
                );
            }
            $pages = $backlinkHydrator->hydrate($pages, $backlinkReceipt['targets']);
        } else {
            $backlinkTargets = $backlinkHydrator->index($pages);
            $backlinkReceipt = [
                'schema' => 'docara.backlinks.v1',
                'content_sha256' => hash('sha256', CanonicalJson::encode($backlinkTargets)),
                'targets' => $backlinkTargets,
            ];
            $pages = $backlinkHydrator->hydrate($pages, $backlinkTargets);
            $contextPages = $pages;
        }
        $componentIndexHydrator = new PortableComponentIndexHydrator;
        $componentIndexReceipt = null;
        if ($earlyPhysicalSelection) {
            $componentIndexReceiptPath = rtrim($finalDestination, '/\\') . '/.docara/component-index.json';
            try {
                $componentIndexReceipt = json_decode(
                    (string) $this->files->get($componentIndexReceiptPath),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $exception) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_COMPONENT_INDEX_BASE_INVALID',
                    'The complete build has no valid component-index projection for an isolated page rebuild.',
                    $exception,
                );
            }
            if (! is_array($componentIndexReceipt)
                || ($componentIndexReceipt['schema'] ?? null) !== 'docara.component_index.v1'
                || ! is_array($componentIndexReceipt['indexes'] ?? null)
                || ! is_string($componentIndexReceipt['content_sha256'] ?? null)
                || ! hash_equals(
                    $componentIndexReceipt['content_sha256'],
                    hash('sha256', CanonicalJson::encode($componentIndexReceipt['indexes'])),
                )
            ) {
                throw new PortableConfigurationException(
                    'PORTABLE_INCREMENTAL_COMPONENT_INDEX_BASE_INVALID',
                    'The complete build has no valid component-index projection for an isolated page rebuild.',
                );
            }
            $pages = $componentIndexHydrator->hydrate($pages, $componentIndexReceipt['indexes']);
        } else {
            $componentIndexes = [];
            foreach ($pages as $page) {
                if (($page['page_source_kind'] ?? null) !== 'authored_markdown'
                    || ! str_contains((string) ($page['content_html'] ?? ''), 'data-docara-component-index')
                ) {
                    continue;
                }
                $catalogRoute = '/' . trim((string) $page['url'], '/') . '/';
                $componentIndexes[$catalogRoute] = $componentIndexHydrator->index($contextPages, $catalogRoute);
            }
            ksort($componentIndexes, SORT_STRING);
            $componentIndexReceipt = [
                'schema' => 'docara.component_index.v1',
                'content_sha256' => hash('sha256', CanonicalJson::encode($componentIndexes)),
                'indexes' => $componentIndexes,
            ];
            $pages = $componentIndexHydrator->hydrate($pages, $componentIndexes);
            $contextPages = $pages;
        }
        $outlineBuilder = new PortableDocumentOutlineBuilder;
        foreach ($pages as &$hydratedPage) {
            $hydratedPlan = $hydratedPage['plan'] ?? null;
            if (! $hydratedPlan instanceof ResolvedPagePlan) {
                continue;
            }
            $hydratedOutline = $outlineBuilder->build(
                (string) $hydratedPage['content_html'],
                (int) data_get($hydratedPlan->configuration, 'reading.toc_depth', 3),
                PortableDocumentIds::reserved(),
            );
            $hydratedPage['content_html'] = $hydratedOutline['html'];
            $hydratedPage['outline'] = $hydratedOutline['items'];
        }
        unset($hydratedPage);
        $localeLinkRoutes = [];
        foreach ($contextPages as $page) {
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
                $contextPages,
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
            $contextPages,
            $contentAssets,
            $buildLocale,
            $documentationVersion,
            $uiCopy->forLocale($buildLocale),
            $localeRegistry->default()->direction,
        );
        $localeRoutePublisher = new PortableLocaleRoutePublisher($this->files);
        $localeRoutePlan = $localeRoutePublisher->plan(
            $contextPages,
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
            $searchPlan = $earlyPhysicalSelection
                ? $this->existingSearchPlan($finalDestination, $localeUrls->rootUrl())
                : (new PortableSearchIndexBuilder)->plan(
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
        if ($selectedPageUrl !== null) {
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
            if ($selectedPageUrl === null) {
                $backlinkReceiptPath = rtrim($destination, '/\\') . '/.docara/backlinks.json';
                $this->files->ensureDirectoryExists(dirname($backlinkReceiptPath));
                $this->files->put($backlinkReceiptPath, $this->prettyCanonicalJson($backlinkReceipt));
                $this->files->put(
                    rtrim($destination, '/\\') . '/.docara/component-index.json',
                    $this->prettyCanonicalJson($componentIndexReceipt),
                );
                $this->files->put($docaraOutputDirectory . '/component-catalog.json', $componentCatalogJson);
                $this->files->put(
                    $docaraOutputDirectory . '/page-metadata.json',
                    $this->prettyCanonicalJson($this->pageMetadata($pages, $root, $documentationVersion)),
                );
            }
            $localeDestinations = [$destination];
            foreach ($localeRegistry->all() as $definition) {
                if ($definition->publicPrefix !== '') {
                    $localeDestinations[] = rtrim($destination, '/\\') . '/' . $definition->publicPrefix;
                }
            }
            $localeDestinations = array_values(array_unique($localeDestinations));
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
                    $markdown,
                    PortableDocumentIds::reserved(),
                    $smartRegistry,
                    $projectSmart?->gateway,
                    $projectSmart?->renderer,
                );
                $outlineDepth = (int) data_get($declarativePlan->configuration, 'reading.toc_depth', 3);
                $layoutConfiguration = is_array($declarativePlan->configuration['layout'] ?? null)
                    ? $declarativePlan->configuration['layout']
                    : [];
                $document = $page['document_artifact'];
                if (! $document instanceof RenderArtifact
                    || ! $page['plan'] instanceof ResolvedPagePlan
                ) {
                    throw new PortableConfigurationException(
                        'PAGE_BUILDER_DOCUMENT_ARTIFACT_REQUIRED',
                        "Page [{$page['url']}] has no typed PageBuilder document artifact.",
                    );
                }
                $document = new RenderArtifact(
                    (string) $page['content_html'],
                    $document->assets,
                    $document->hydration + ['derived_views_applied' => true],
                    $document->provenance + ['derived_views' => 'pagebuilder_route_metadata'],
                );
                $documentIr = $page['document_ir'];
                if (! $documentIr instanceof DocumentIr) {
                    throw new PortableConfigurationException(
                        'PAGE_BUILDER_DOCUMENT_IR_REQUIRED',
                        "Page [{$page['url']}] has no typed Document IR.",
                    );
                }
                $declarative = $declarativePipeline->compose(
                    $documentIr,
                    (string) $page['output'],
                    (string) $page['title'],
                    $outlineDepth,
                    $document,
                    $composition,
                    $layoutConfiguration,
                    $declarativePlan->provenance,
                );
                $renderedMainHash = hash('sha256', $document->html);
                $composedMainHash = hash('sha256', (string) ($declarative->artifact->hydration['regions']['main'] ?? ''));
                if (! hash_equals($renderedMainHash, $composedMainHash)) {
                    throw new PortableConfigurationException(
                        'DECLARATIVE_RENDERED_CONTENT_PARITY_FAILED',
                        "Rendered page [{$page['url']}] changed during declarative composition.",
                    );
                }
                $page['declarative_pipeline'] = [
                    'status' => 'published',
                    'plan_hash' => $declarative->plan->canonicalHash(),
                    'assets' => $declarative->artifact->assets,
                    'main_source' => $declarative->artifact->hydration['main_source'] ?? null,
                    'document_ir' => [
                        'schema' => 'docara.document_ir.v1',
                        'source' => $documentIr->source,
                        'nodes' => count($documentIr->allNodes()),
                        'sha256' => $documentIr->canonicalHash(),
                    ],
                ];
                $outputPath = rtrim($destination, '/\\') . '/' . $page['output'];
                $this->files->ensureDirectoryExists(dirname($outputPath));
                $rendered = $publisher->render(
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
                    'translation_key' => $page['translation_key'],
                    'tags' => $page['tags'] ?? [],
                    'output' => $page['output'],
                    'url' => $page['url'],
                    'resolved_page_plan' => $plan->toArray(),
                    'component_runtime' => $page['components']->toArray(),
                    'publisher' => [
                        'id' => $publisher->id(),
                        'html_sha256' => hash('sha256', $rendered),
                    ],
                    'declarative_pipeline' => $page['declarative_pipeline'],
                    'input_chain' => [
                        'resolved_plan_sha256' => $plan->canonicalHash(),
                        'trace' => $plan->trace,
                        'document_ir_sha256' => $documentIr->canonicalHash(),
                        'framework_lock_sha256' => hash('sha256', CanonicalJson::encode($plan->frameworkLock)),
                        'component_runtime_sha256' => hash('sha256', CanonicalJson::encode($page['components']->toArray())),
                    ],
                ];
                $diagnosticsByUrl[(string) $page['url']] = $record;
                $result->put((string) $page['url'], $record);
            }
            $redirectPublisher->publish($redirectPlan, $destination);
            $localeRoutePublisher->publish($localeRoutePlan, $destination);
            $this->copyContentAssets($contentAssets, $destination);
            $brandPublisher->publish($brandPlan['assets'], $destination);
            foreach ($localeDestinations as $localeDestination) {
                $this->publishFrameworkAssets($buildBasePlan->frameworkLock, $localeDestination);
                (new PortablePublisherAssetPublisher($this->files, $smartRegistry))->publish($localeDestination);
            }
            $diagnosticPath = rtrim($destination, '/\\') . '/.docara/resolved-page-plans.json';
            $this->files->ensureDirectoryExists(dirname($diagnosticPath));
            $this->files->put($diagnosticPath, $this->prettyCanonicalJson([
                'schema' => 'docara.resolved_page_plans.v1',
                'build' => [
                    'locale' => $buildLocale,
                    'documentation_version' => $documentationVersion,
                    'engine' => $engineRevision,
                    'dependencies' => $dependencyLock,
                    'framework' => [
                        'lock_sha256' => hash('sha256', CanonicalJson::encode($buildBasePlan->frameworkLock)),
                        'runtime' => $buildBasePlan->frameworkLock['runtime'],
                        'manifests' => $buildBasePlan->frameworkLock['manifests'],
                        'asset_projection' => $buildBasePlan->frameworkLock['asset_projection'],
                    ],
                    'production_inputs' => $runtimeMetadata->productionInputGroups(),
                    'component_catalog_sha256' => hash('sha256', CanonicalJson::encode($effectiveComponentCatalog)),
                    'publisher' => $publisher->id(),
                    'locale_sources' => $this->localeSourceHashes($root, $contentContexts),
                ],
                'pages' => $this->orderedDiagnostics($contextPages, $diagnosticsByUrl),
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

    private function translationKey(ResolvedPagePlan $plan, string $contentRoot): string
    {
        return (string) ($plan->frontMatter['translation_key']
            ?? substr($plan->page, strlen(rtrim($contentRoot, '/') . '/')));
    }

    private function pageTitle(ResolvedPagePlan $plan): string
    {
        if (is_string($plan->frontMatter['title'] ?? null)) {
            return $plan->frontMatter['title'];
        }
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
        if (is_string($plan->frontMatter['description'] ?? null)) {
            return $plan->frontMatter['description'];
        }
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

    /** @param PageSource[] $sources */
    private function assertMissingPagePolicy(
        array $sources,
        LocaleRegistry $locales,
        LocaleMissingPagePolicy $policy,
    ): void {
        if ($policy->value === LocaleMissingPagePolicy::SKIP) {
            return;
        }

        $routes = [];
        foreach ($sources as $source) {
            $routes[$source->route][$source->locale] = $source->path;
        }
        ksort($routes, SORT_STRING);
        foreach ($routes as $route => $owners) {
            foreach ($locales->all() as $locale => $definition) {
                if (isset($owners[$locale])) {
                    continue;
                }
                $expected = rtrim($definition->contentRoot, '/') . '/'
                    . ($route === '' ? 'index' : $route) . '.md';
                throw new PortableConfigurationException(
                    'LOCALE_PAGE_MISSING',
                    "Locale [$locale] has no Markdown owner for route [$route]. Expected source [$expected] because locales.missing_page_policy is error.",
                );
            }
        }
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

    private function observe(string $event, string $subject): void
    {
        if ($this->observer instanceof \Closure) {
            ($this->observer)($event, $subject);
        }
    }

    /**
     * Rebuild only the selected physical source while retaining the complete
     * route/navigation identity from the existing full-build diagnostics.
     * Diagnostic records are metadata input here; they are never rendered as
     * page content and never replace the selected Markdown owner.
     *
     * @param  array<string, array<string, mixed>>  $diagnosticsByUrl
     * @param  list<array<string, mixed>>  $selectedPages
     * @return list<array<string, mixed>>
     */
    private function contextPagesFromDiagnostics(array $diagnosticsByUrl, array $selectedPages): array
    {
        $selectedByUrl = [];
        foreach ($selectedPages as $page) {
            $selectedByUrl[(string) $page['url']] = $page;
        }

        $pages = [];
        foreach ($diagnosticsByUrl as $url => $record) {
            if (isset($selectedByUrl[$url])) {
                $pages[] = $selectedByUrl[$url];

                continue;
            }
            $resolved = is_array($record['resolved_page_plan'] ?? null)
                ? $record['resolved_page_plan']
                : [];
            $configuration = is_array($resolved['configuration'] ?? null)
                ? $resolved['configuration']
                : [];
            $pagePath = (string) ($record['page_path'] ?? $resolved['page'] ?? '');
            $pages[] = [
                'page_path' => $pagePath,
                'page_source_kind' => (string) ($record['page_source_kind'] ?? 'generated_projection'),
                'title' => (string) ($record['title'] ?? ''),
                'description' => (string) ($record['description'] ?? ''),
                'locale' => (string) ($record['locale'] ?? $configuration['locale'] ?? ''),
                'translation_key' => (string) ($record['translation_key'] ?? $pagePath),
                'preset' => (string) ($configuration['preset'] ?? 'docs'),
                'navigation_hidden' => (bool) data_get($configuration, 'navigation.hidden', false),
                'navigation_order' => data_get($configuration, 'navigation.order'),
                'search_enabled' => (bool) data_get($configuration, 'search.enabled', false),
                'search_indexed' => (bool) data_get($configuration, 'search.indexed', true),
                'content_html' => '',
                'component_calls' => [],
                'url' => (string) $url,
                'output' => (string) ($record['output'] ?? ''),
            ];
        }

        if (count($selectedByUrl) !== 1 || count($pages) !== count($diagnosticsByUrl)) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_DIAGNOSTICS_INCOMPLETE',
                'The selected physical route does not match the complete build diagnostics. Run a full build first.',
            );
        }

        return $pages;
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

    /** @return array<string, mixed> */
    private function existingBuildProvenance(string $destination): array
    {
        $path = rtrim($destination, '/\\') . '/.docara/resolved-page-plans.json';
        try {
            $document = json_decode((string) $this->files->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_DIAGNOSTICS_INVALID',
                'The existing build does not contain valid complete provenance. Run a full build first.',
                $exception,
            );
        }
        if (! is_array($document) || ! is_array($document['build'] ?? null)) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_DIAGNOSTICS_INVALID',
                'The existing build provenance is incomplete. Run a full build first.',
            );
        }

        return $document['build'];
    }

    /**
     * @param  array<string, array{root:string,path:string,prefix:string}>  $contentContexts
     * @return array<string, array{path:string,sha256:string}>
     */
    private function localeSourceHashes(string $root, array $contentContexts): array
    {
        $sources = [];
        foreach ($contentContexts as $locale => $context) {
            $relative = rtrim($context['root'], '/\\') . '/lang.json';
            $path = $root . '/' . $relative;
            if (! is_file($path) || is_link($path)) {
                continue;
            }
            $sources[$locale] = [
                'path' => $relative,
                'sha256' => hash_file('sha256', $path),
            ];
        }
        ksort($sources, SORT_STRING);

        return $sources;
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

    private function existingSearchPlan(string $destination, string $baseUrl): PortableSearchPlan
    {
        $indexPath = rtrim($destination, '/\\') . '/_docara/search-index.json';
        $runtimePath = rtrim($destination, '/\\') . '/_docara/search.js';
        try {
            $indexJson = (string) $this->files->get($indexPath);
            $index = json_decode($indexJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_SEARCH_BASE_INVALID',
                'The complete build has no valid search index for an isolated page rebuild.',
                $exception,
            );
        }
        $runtime = (string) $this->files->get($runtimePath);
        $documents = is_array($index) ? ($index['documents'] ?? null) : null;
        $contentHash = is_array($index) ? ($index['content_sha256'] ?? null) : null;
        if (! is_array($index)
            || ($index['schema'] ?? null) !== 'docara.search_index.v1'
            || ! is_array($documents)
            || ! is_string($contentHash)
            || ! hash_equals($contentHash, hash('sha256', CanonicalJson::encode($documents)))
            || $runtime === ''
            || preg_match('//u', $runtime) !== 1
        ) {
            throw new PortableConfigurationException(
                'PORTABLE_INCREMENTAL_SEARCH_BASE_INVALID',
                'The complete build has no valid search projection for an isolated page rebuild.',
            );
        }
        (new SchemaRepository)->assertValid($index, 'search-index.schema.json');
        $runtimeHash = hash('sha256', $runtime);
        $deploymentBase = $baseUrl === '/' ? '' : '/' . trim($baseUrl, '/');

        return new PortableSearchPlan(
            $index,
            $indexJson,
            $runtime,
            $contentHash,
            $runtimeHash,
            $deploymentBase . '/_docara/search-index.json?docara_v=' . $contentHash,
            $deploymentBase . '/_docara/search.js?docara_v=' . $runtimeHash,
        );
    }
}
