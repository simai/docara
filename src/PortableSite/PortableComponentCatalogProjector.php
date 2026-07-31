<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use DOMNode;
use Simai\Docara\Framework\ComponentDirectiveDocument;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\I18n\Translator;
use Simai\Docara\ComponentCatalog\PublicComponentPolicy;
use Simai\Docara\ComponentCatalog\PublicComponentPage;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\Preferences\ReaderPreferenceCompiler;

final readonly class PortableComponentCatalogProjector
{
    public function __construct(
        private PortableMarkdownRenderer $markdown,
        private string $packageRoot = __DIR__ . '/../..',
        private ?Translator $translator = null,
        private PortableExampleRenderer $examples = new PortableExampleRenderer,
    ) {}

    /**
     * @param  array<string, mixed>  $catalog
     * @return array{
     *     pages: list<array<string, mixed>>,
     *     receipt: array<string, mixed>
     * }
     */
    public function project(
        array $catalog,
        FrameworkComponentRuntime $runtime,
        ResolvedPagePlan $basePlan,
        string $contentRoot,
        string $baseUrl,
        string $homeUrl,
        string $outputPrefix = '',
        array $reservedDocumentIds = [],
        array $authoredComponents = [],
    ): array {
        $entries = is_array($catalog['entries'] ?? null) ? array_values($catalog['entries']) : [];
        $supported = [];
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ENTRY_INVALID',
                    'The effective component catalogue contains an invalid entry.',
                );
            }
            $isSupported = ($entry['lifecycle'] ?? null) === 'supported';
            $hasDemo = ($entry['verification']['demo'] ?? false) === true;
            if ($isSupported && ! $hasDemo) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_DEMO_EVIDENCE_REQUIRED',
                    (string) ($entry['id'] ?? ''),
                );
            }
            if (! $isSupported && $hasDemo) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_UNSUPPORTED_DEMO_FORBIDDEN',
                    (string) ($entry['id'] ?? ''),
                );
            }
            if ($isSupported
                && ($entry['family'] ?? null) !== 'framework_smart'
                && (new PublicComponentPolicy)->exposes((string) ($entry['id'] ?? ''))
            ) {
                $supported[] = $entry;
            }
        }
        if ($supported === []) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_SUPPORTED_EMPTY',
                'The effective component catalogue has no supported entries.',
            );
        }

        $locale = (string) (
            $basePlan->configuration['locale']
            ?? $basePlan->configuration['default_locale']
            ?? 'en'
        );
        $copy = $this->copy($locale);
        $tocDepth = (int) data_get($basePlan->configuration, 'reading.toc_depth', 3);
        $presentedEntries = array_map(function (array $entry) use ($authoredComponents, $locale): array {
            $id = $this->id($entry);
            $authored = $authoredComponents[$id] ?? null;
            if (! is_array($authored)) {
                return $this->presentEntry($entry, $locale);
            }
            $title = trim((string) ($authored['title'] ?? ''));
            $description = trim((string) ($authored['description'] ?? ''));
            if ($title === '' || $description === '') {
                throw new PortableConfigurationException(
                    'AUTHORED_COMPONENT_PAGE_PRESENTATION_REQUIRED',
                    "Authored component page [$id] requires a title and a short Markdown description.",
                );
            }
            $entry['title'] = $title;
            $entry['description'] = $description;

            return $entry;
        }, $supported);
        $deploymentBase = $baseUrl === '/' ? '/' : '/' . trim($baseUrl, '/') . '/';
        $catalogRoute = $deploymentBase . 'components/';
        $brandTitle = (string) data_get(
            $basePlan->configuration,
            'branding.title',
            'Docara',
        );
        $catalogBreadcrumbs = [
            ['title' => $brandTitle, 'url' => $homeUrl],
            ['title' => $copy['catalog_title'], 'url' => null],
        ];
        $indexComponents = $runtime->extract('', '@docara/component-catalog/index.md');
        $indexOutline = (new PortableDocumentOutlineBuilder)->build(
            $this->indexFragment($presentedEntries, $catalogRoute, $copy),
            $tocDepth,
            $reservedDocumentIds,
        );
        $indexFragment = $indexOutline['html'];
        $index = $this->page(
            basePlan: $basePlan,
            pagePath: $contentRoot . '/components/index.md',
            title: $copy['catalog_title'],
            description: $copy['catalog_description'],
            url: $catalogRoute,
            output: $this->output($outputPrefix, 'components/index.html'),
            contentHtml: $indexFragment,
            components: $indexComponents,
            homeUrl: $homeUrl,
            navigationHidden: null,
            sourceMarkdown: '# ' . $copy['catalog_title'] . "\n",
            outline: [],
        );
        $index['component_catalog_kind'] = 'index';
        $index['component_catalog_breadcrumbs'] = $catalogBreadcrumbs;
        $index['component_catalog_previous'] = null;
        $index['component_catalog_next'] = null;

        $pages = [];
        $receiptPages = [];
        foreach ($supported as $entry) {
            $id = $this->id($entry);
            if (isset($authoredComponents[$id])) {
                continue;
            }
            $presentedEntry = $this->presentEntry($entry, $locale);
            $source = '';
            $exampleHash = hash('sha256', '');
            $renderedHash = hash('sha256', '');
            $components = $runtime->extract('', '@docara/component-catalog/' . $id . '.md');
            $source = $this->exampleSource($presentedEntry);
            $this->assertVariantExamples($presentedEntry, $source);
            $components = $runtime->extract($source, '@docara/component-catalog/' . $id . '.md');
            $renderedFragment = $components->hydrate(
                $this->markdown->render(
                    $components->markdownWithPlaceholders,
                    $this->packageRoot,
                    $this->packageRoot . '/' . (string) $presentedEntry['example_ref'],
                ),
            );
            if (trim($renderedFragment) === '') {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_EXAMPLE_EMPTY',
                    "Component [$id] produced an empty example.",
                );
            }
            $exampleHash = hash('sha256', $source);
            $renderedHash = hash('sha256', $renderedFragment);
            $exampleGroups = [];
            foreach ($this->exampleSourceGroups($presentedEntry, $source, $copy) as $group) {
                $groupComponents = $runtime->extract(
                    $group['source'],
                    '@docara/component-catalog/' . $id . '-' . count($exampleGroups) . '.md',
                );
                $exampleGroups[] = [
                    ...$group,
                    'rendered' => $groupComponents->hydrate(
                        $this->markdown->render(
                            $groupComponents->markdownWithPlaceholders,
                            $this->packageRoot,
                            $this->packageRoot . '/' . (string) $presentedEntry['example_ref'],
                        ),
                    ),
                ];
            }
            $detailHtml = $this->detailFragment(
                $presentedEntry,
                $source,
                $renderedFragment,
                $exampleHash,
                $renderedHash,
                $copy,
                $exampleGroups,
                $locale,
            );
            $slug = $this->publicSlug($entry);
            $route = $catalogRoute . rawurlencode($slug) . '/';
            $output = $this->output($outputPrefix, 'components/' . $slug . '/index.html');
            $detailOutline = (new PortableDocumentOutlineBuilder)->build(
                $detailHtml,
                $tocDepth,
                $reservedDocumentIds,
            );
            $detailFragment = $detailOutline['html'];
            $page = $this->page(
                basePlan: $basePlan,
                pagePath: $contentRoot . '/components/' . $slug . '.md',
                title: (string) $presentedEntry['title'],
                description: (string) $presentedEntry['description'],
                url: $route,
                output: $output,
                contentHtml: $detailFragment,
                components: $components,
                homeUrl: $homeUrl,
                navigationHidden: null,
                sourceMarkdown: $source,
                outline: $detailOutline['items'],
            );
            $page['component_catalog_kind'] = 'detail';
            $page['component_catalog_id'] = $id;
            $page['component_catalog_index_url'] = $catalogRoute;
            $page['component_catalog_breadcrumbs'] = [
                ...array_slice($catalogBreadcrumbs, 0, -1),
                ['title' => $copy['catalog_title'], 'url' => $catalogRoute],
                ['title' => (string) $presentedEntry['title'], 'url' => null],
            ];
            $pages[] = $page;
            $receiptPages[] = [
                'id' => $id,
                'family' => (string) $entry['family'],
                'lifecycle' => (string) $entry['lifecycle'],
                'route' => $route,
                'output' => $output,
                'example_ref' => (string) $presentedEntry['example_ref'],
                'catalog_entry_sha256' => hash('sha256', CanonicalJson::encode($entry)),
                'example_sha256' => $exampleHash,
                'rendered_fragment_sha256' => $renderedHash,
                'contract_fragment_sha256' => $this->normalizedFragmentHash($detailFragment),
            ];
        }

        foreach ($pages as $indexPosition => &$page) {
            $page['component_catalog_previous'] = $indexPosition === 0
                ? null
                : [
                    'title' => (string) $pages[$indexPosition - 1]['title'],
                    'url' => (string) $pages[$indexPosition - 1]['url'],
                ];
            $page['component_catalog_next'] = $indexPosition === count($pages) - 1
                ? null
                : [
                    'title' => (string) $pages[$indexPosition + 1]['title'],
                    'url' => (string) $pages[$indexPosition + 1]['url'],
                ];
        }
        unset($page);

        $receiptCore = [
            'catalog_content_sha256' => (string) ($catalog['content_sha256'] ?? ''),
            'index' => [
                'route' => $catalogRoute,
                'output' => $this->output($outputPrefix, 'components/index.html'),
                'contract_fragment_sha256' => $this->normalizedFragmentHash($indexFragment),
            ],
            'pages' => $receiptPages,
        ];
        $receipt = [
            'schema' => 'docara.component_catalog_pages.v1',
            'version' => 1,
            'catalog_content_sha256' => $receiptCore['catalog_content_sha256'],
            'content_sha256' => hash('sha256', CanonicalJson::encode($receiptCore)),
            'index' => $receiptCore['index'],
            'pages' => $receiptCore['pages'],
        ];

        return [
            'pages' => [$index, ...$pages],
            'receipt' => $receipt,
        ];
    }

    private function output(string $prefix, string $relative): string
    {
        return implode('/', array_filter([
            trim($prefix, '/'),
            ltrim($relative, '/'),
        ], static fn (string $part): bool => $part !== ''));
    }

    /** @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function presentEntry(array $entry, string $locale): array
    {
        if (! $this->translator instanceof Translator) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_TRANSLATOR_REQUIRED',
                'Component catalogue projection requires a resolved language-pack translator.',
            );
        }
        $presentation = $this->translator->component($locale, $this->id($entry));
        if (! is_array($presentation)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_MISSING',
                $this->id($entry),
            );
        }
        $this->assertPresentationContract($entry, $presentation);
        foreach (['title', 'description', 'limitations'] as $key) {
            $entry[$key] = $presentation[$key];
        }
        if (is_string($presentation['example_ref'] ?? null)) {
            $entry['example_ref'] = $presentation['example_ref'];
        }
        if (isset($entry['gap']) && is_array($presentation['gap'] ?? null)) {
            $entry['gap'] = array_replace($entry['gap'], $presentation['gap']);
        }
        $entry['_localized_states'] = $presentation['states'];
        $entry['_localized_parameters'] = $presentation['parameters'];

        return $entry;
    }

    /** @param array<string, mixed> $entry @param array<string, mixed> $presentation */
    private function assertPresentationContract(array $entry, array $presentation): void
    {
        $id = $this->id($entry);
        foreach (['title', 'description'] as $key) {
            if (! is_string($presentation[$key] ?? null) || trim($presentation[$key]) === '') {
                throw new PortableConfigurationException(
                    'COMPONENT_PRESENTATION_TEXT_REQUIRED',
                    "Component [$id] has no localized [$key].",
                );
            }
        }
        if (! is_array($presentation['limitations'] ?? null)
            || ! is_array($presentation['states'] ?? null)
            || ! is_array($presentation['parameters'] ?? null)
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_PRESENTATION_SHAPE_INVALID',
                "Component [$id] has an incomplete localized presentation.",
            );
        }
        foreach ($presentation['limitations'] as $limitation) {
            if (! is_string($limitation) || trim($limitation) === '') {
                throw new PortableConfigurationException(
                    'COMPONENT_PRESENTATION_LIMITATIONS_INVALID',
                    "Component [$id] has an invalid localized limitation.",
                );
            }
        }

        $expectedStates = array_map('strval', is_array($entry['states'] ?? null) ? $entry['states'] : []);
        $actualStates = array_map('strval', array_keys($presentation['states']));
        sort($expectedStates, SORT_STRING);
        sort($actualStates, SORT_STRING);
        if ($expectedStates !== $actualStates) {
            throw new PortableConfigurationException(
                'COMPONENT_PRESENTATION_STATES_MISMATCH',
                "Component [$id] localized states do not match its technical contract.",
            );
        }

        $expectedParameters = [];
        foreach (($entry['authoring']['parameters'] ?? []) as $parameter) {
            if (! is_array($parameter) || ! is_string($parameter['name'] ?? null)) {
                throw new PortableConfigurationException(
                    'COMPONENT_PRESENTATION_PARAMETERS_MISMATCH',
                    "Component [$id] has an invalid technical parameter.",
                );
            }
            $expectedParameters[$parameter['name']] = $parameter;
        }
        $expectedNames = array_keys($expectedParameters);
        $actualNames = array_map('strval', array_keys($presentation['parameters']));
        sort($expectedNames, SORT_STRING);
        sort($actualNames, SORT_STRING);
        if ($expectedNames !== $actualNames) {
            throw new PortableConfigurationException(
                'COMPONENT_PRESENTATION_PARAMETERS_MISMATCH',
                "Component [$id] localized parameters do not match its technical contract.",
            );
        }
        foreach ($expectedParameters as $name => $parameter) {
            $localized = $presentation['parameters'][$name] ?? null;
            if (! is_array($localized)
                || ! is_string($localized['label'] ?? null)
                || trim($localized['label']) === ''
                || ! is_string($localized['description'] ?? null)
                || trim($localized['description']) === ''
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_PRESENTATION_PARAMETER_TEXT_REQUIRED',
                    "Component [$id] parameter [$name] has no complete localized presentation.",
                );
            }
            $expectedValues = array_map('strval', is_array($parameter['values'] ?? null) ? $parameter['values'] : []);
            $actualValues = array_map('strval', array_keys(is_array($localized['values'] ?? null) ? $localized['values'] : []));
            sort($expectedValues, SORT_STRING);
            sort($actualValues, SORT_STRING);
            if ($expectedValues !== $actualValues) {
                throw new PortableConfigurationException(
                    'COMPONENT_PRESENTATION_PARAMETER_VALUES_MISMATCH',
                    "Component [$id] parameter [$name] localized values do not match its technical contract.",
                );
            }
        }

        $supported = ($entry['lifecycle'] ?? null) === 'supported';
        if ($supported) {
            if (! is_string($presentation['example_ref'] ?? null)
                || isset($presentation['gap'])
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_PRESENTATION_SUPPORTED_CONTRACT_INVALID',
                    "Supported component [$id] requires a localized example and cannot declare a gap.",
                );
            }
        } elseif (! is_array($presentation['gap'] ?? null)
            || ! is_string($presentation['gap']['reason'] ?? null)
            || ! is_string($presentation['gap']['fallback'] ?? null)
            || ! is_string($presentation['gap']['admission_condition'] ?? null)
            || isset($presentation['example_ref'])
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_PRESENTATION_GAP_CONTRACT_INVALID',
                "Unavailable component [$id] requires a complete localized gap and cannot declare an example.",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function exampleSource(array $entry): string
    {
        $id = $this->id($entry);
        $relative = $entry['example_ref'] ?? null;
        if (! is_string($relative)
            || $relative === ''
            || str_starts_with($relative, '/')
            || str_contains($relative, '\\')
            || str_contains($relative, "\0")
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_EXAMPLE_PATH_INVALID',
                $id,
            );
        }
        $segments = explode('/', $relative);
        if (in_array('', $segments, true)
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_EXAMPLE_PATH_INVALID',
                $id,
            );
        }

        $candidate = rtrim($this->packageRoot, '/\\');
        foreach ($segments as $segment) {
            $candidate .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($candidate)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_EXAMPLE_SYMLINK_FORBIDDEN',
                    $id,
                );
            }
        }
        $root = realpath($this->packageRoot);
        $real = realpath($candidate);
        $stat = @lstat($candidate);
        if (! is_string($root)
            || ! is_string($real)
            || ! is_file($real)
            || ! str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
            || ! is_array($stat)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 0) !== 1
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_EXAMPLE_NOT_FOUND',
                $id,
            );
        }
        $source = file_get_contents($real);
        if (! is_string($source) || preg_match('//u', $source) !== 1) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_EXAMPLE_INVALID',
                $id,
            );
        }

        return $source;
    }

    /** @return array<string, string> */
    public function assets(): array
    {
        $assets = [];
        foreach ([
            'docara-flow.png',
            'docara-mark.svg',
            'docara-screen.png',
            'feature-build.png',
            'feature-json.png',
            'feature-markdown.png',
            'simai.svg',
        ] as $name) {
            $relative = 'resources/component-catalog/assets/' . $name;
            $candidate = rtrim($this->packageRoot, '/\\');
            foreach (explode('/', $relative) as $segment) {
                $candidate .= DIRECTORY_SEPARATOR . $segment;
                if (! is_link($candidate)) {
                    continue;
                }
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ASSET_INVALID',
                    $relative,
                );
            }
            $root = realpath($this->packageRoot);
            $real = realpath($candidate);
            $stat = @lstat($candidate);
            if (! is_string($root)
                || ! is_string($real)
                || ! is_file($real)
                || ! str_starts_with($real, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                || ! is_array($stat)
                || (($stat['mode'] ?? 0) & 0170000) !== 0100000
                || ($stat['nlink'] ?? 0) !== 1
            ) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ASSET_INVALID',
                    $relative,
                );
            }
            $bytes = file_get_contents($real);
            if (! is_string($bytes) || $bytes === '') {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ASSET_INVALID',
                    $relative,
                );
            }
            $assets['_docara/component-catalog/' . $name] = $bytes;
        }

        return $assets;
    }

    /**
     * @param  array<string, mixed>  $basePlan
     * @return array<string, mixed>
     */
    private function page(
        ResolvedPagePlan $basePlan,
        string $pagePath,
        string $title,
        string $description,
        string $url,
        string $output,
        string $contentHtml,
        ComponentDirectiveDocument $components,
        string $homeUrl,
        ?bool $navigationHidden,
        string $sourceMarkdown,
        array $outline,
    ): array {
        $configuration = $basePlan->configuration;
        $configuration['title'] = $title;
        $configuration['description'] = $description;
        $configuration['preset'] = 'docs';
        $configuration['navigation'] = is_array($configuration['navigation'] ?? null)
            ? $configuration['navigation']
            : [];
        if ($navigationHidden !== null) {
            $configuration['navigation']['hidden'] = $navigationHidden;
        }
        $provenance = array_filter(
            $basePlan->provenance,
            static fn (string $pointer): bool => ! preg_match(
                '#^/(?:title|description|preset)(?:/|$)#',
                $pointer,
            ),
            ARRAY_FILTER_USE_KEY,
        );
        $provenance = array_replace($provenance, [
            '/title' => '@docara/component-catalog',
            '/description' => '@docara/component-catalog',
            '/preset' => '@docara/component-catalog',
        ]);
        if ($navigationHidden !== null) {
            $provenance['/navigation/hidden'] = '@docara/component-catalog';
        }
        $trace = $basePlan->trace;
        $trace[] = [
            'role' => 'generated-content',
            'source' => '@docara/component-catalog/' . basename($pagePath),
            'sha256' => hash('sha256', $sourceMarkdown),
        ];
        $plan = new ResolvedPagePlan(
            page: $pagePath,
            markdown: $sourceMarkdown,
            configuration: $configuration,
            frameworkLock: $basePlan->frameworkLock,
            trace: $trace,
            provenance: $provenance,
        );

        return [
            'plan' => $plan,
            'page_path' => $pagePath,
            'title' => $title,
            'description' => $description,
            'locale' => (string) ($configuration['locale'] ?? $configuration['default_locale'] ?? 'en'),
            'preset' => 'docs',
            'theme' => (string) data_get($configuration, 'settings.theme', 'system'),
            'modal_blur' => (string) data_get($configuration, 'settings.modal_blur', 'large'),
            'reader_preferences' => is_array($configuration['reader_preferences'] ?? null)
                ? $configuration['reader_preferences']
                : ReaderPreferenceCompiler::defaultConfiguration(),
            'reader_preferences_storage_key' => ReaderPreferenceCompiler::storageKey($configuration),
            'container_max' => (int) data_get($configuration, 'layout.container.max', 7),
            'scrollbar_preset' => (string) data_get($configuration, 'layout.scrollbar.preset', 'overlay'),
            'content_gap' => (int) data_get($configuration, 'layout.content.gap', 0),
            'navigation_hidden' => (bool) data_get($configuration, 'navigation.hidden', false),
            'navigation_order' => data_get($configuration, 'navigation.order'),
            'search_enabled' => (bool) data_get($configuration, 'search.enabled', false),
            'search_indexed' => (bool) data_get($configuration, 'search.indexed', true),
            'reading_breadcrumbs' => (bool) data_get($configuration, 'reading.breadcrumbs', true),
            'reading_toc' => (bool) data_get($configuration, 'reading.toc', true),
            'reading_mobile_toc' => (string) data_get($configuration, 'reading.mobile_toc', 'auto'),
            'reading_previous_next' => (bool) data_get(
                $configuration,
                'reading.previous_next',
                true,
            ),
            'outline' => $outline,
            'url' => $url,
            'output' => $output,
            'home_url' => $homeUrl,
            'content_html' => $contentHtml,
            'components' => $components,
            'component_calls' => $components->normalizedCalls,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, string>  $copy
     */
    public function indexFragment(array $entries, string $catalogRoute, array $copy): string
    {
        $groups = [
            'text_code' => [],
            'structure' => [],
            'media' => [],
            'actions' => [],
        ];
        foreach ($entries as $entry) {
            if (($entry['lifecycle'] ?? null) !== 'supported') {
                continue;
            }
            $id = $this->id($entry);
            $group = $this->indexGroup($id);
            $groups[$group][] = '<li data-docara-component-item="' . $this->escape($id) . '">'
                . '<a href="' . $this->escape($catalogRoute . rawurlencode($this->publicSlug($entry)) . '/') . '">'
                . $this->escape((string) $entry['title']) . '</a></li>';
        }

        $sections = [];
        foreach ($groups as $group => $items) {
            if ($items === []) {
                continue;
            }
            $sections[] = '<section data-docara-component-group="' . $this->escape($group) . '">'
                . '<h2 class="heading-4">' . $this->escape($copy['group_' . $group]) . '</h2>'
                . '<ul class="list-none m-0 p-0 flex flex-col gap-1">'
                . implode('', $items) . '</ul></section>';
        }

        return '<div data-docara-component-catalog-index>'
            . '<h1>' . $this->escape($copy['catalog_title']) . '</h1>'
            . '<p>' . $this->escape($copy['catalog_intro']) . '</p>'
            . '<div class="flex flex-col gap-2">'
            . implode('', $sections) . '</div>'
            . '</div>';
    }

    private function indexGroup(string $id): string
    {
        return match ($id) {
            'native.headings_and_text', 'native.lists_and_quotes', 'native.table',
            'native.code', 'native.footnotes_and_sources', 'docara.badge', 'docara.kbd',
            'docara.icon', 'docara.code', 'docara.diagram', 'docara.math', 'docara.html' => 'text_code',
            'docara.card', 'docara.grid', 'docara.details', 'docara.example',
            'docara.steps', 'docara.tabs', 'docara.tree', 'docara.backlinks' => 'structure',
            'native.links_and_images', 'docara.figure', 'docara.media', 'docara.embed',
            'docara.download', 'docara.logos' => 'media',
            'docara.alert', 'docara.banner', 'docara.button', 'docara.hero',
            'ui.alert', 'ui.button' => 'actions',
            default => throw new PortableConfigurationException(
                'COMPONENT_CATALOG_INDEX_GROUP_REQUIRED',
                $id,
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, string>  $copy
     * @param  list<array{label:string,source:string,rendered:string,parameter:?string}>  $exampleGroups
     */
    public function detailFragment(
        array $entry,
        string $source,
        string $renderedFragment,
        string $exampleHash,
        string $renderedHash,
        array $copy,
        array $exampleGroups = [],
        string $locale = 'en',
    ): string {
        $id = $this->id($entry);
        $parameters = is_array($entry['authoring']['parameters'] ?? null)
            ? array_values($entry['authoring']['parameters'])
            : [];
        $parameterExamples = [];
        $mainExamples = [];
        foreach ($exampleGroups as $group) {
            $parameterName = $group['parameter'] ?? null;
            if ($parameterName === null) {
                $mainExamples[] = $group;

                continue;
            }
            $parameterExamples[$parameterName][] = $group;
        }
        if ($mainExamples === []) {
            $mainExamples[] = [
                'source' => $this->publishedExampleSource($source),
                'rendered' => $renderedFragment,
            ];
        }
        $publicSource = implode("\n", array_map(
            static fn (array $example): string => rtrim($example['source']),
            $mainExamples,
        )) . "\n";
        $mainPreview = implode('', array_map(
            static fn (array $example): string => $example['rendered'],
            $mainExamples,
        ));
        $sourceFragment = rtrim(
            $this->markdown->render($this->sourceFence($publicSource)),
            "\n",
        );
        $parts = [
            '<h1>' . $this->escape((string) $entry['title']) . '</h1>',
            '<p>' . $this->escape((string) $entry['description']) . '</p>',
            '<pre hidden aria-hidden="true" data-docara-component-source="'
                . $this->escape($id) . '">'
                . $this->escape($this->publishedExampleSource($source)) . '</pre>',
            '<div data-docara-component-source-display="' . $this->escape($id) . '">'
                . $this->examples->render(
                    id: 'component-' . $this->publicSlug($entry),
                    preview: $mainPreview,
                    sources: ['Markdown' => $sourceFragment],
                    exampleLabel: $copy['example'],
                    copyLabel: $copy['copy'],
                    copiedLabel: $copy['copied'],
                    legacyComponentId: $id,
                ) . '</div>',
        ];

        if ($parameters !== []) {
            $parts[] = $this->parameterDefinitions(
                $entry,
                $parameters,
                $parameterExamples,
                $copy,
                $locale,
            );
        }

        return '<div data-docara-component-detail="' . $this->escape($id)
            . '" data-docara-example-source-sha256="' . $exampleHash
            . '" data-docara-example-render-sha256="' . $renderedHash
            . '">' . implode('', $parts) . '</div>';
    }

    /**
     * @param array<string,mixed> $entry
     * @param list<mixed> $parameters
     * @param array<string,list<array{label:string,source:string,rendered:string,parameter:?string}>> $parameterExamples
     * @param array<string,string> $copy
     */
    private function parameterDefinitions(
        array $entry,
        array $parameters,
        array $parameterExamples,
        array $copy,
        string $locale,
    ): string {
        $items = [];
        foreach ($parameters as $parameter) {
            if (! is_array($parameter) || ! is_string($parameter['name'] ?? null)) {
                continue;
            }
            $name = $parameter['name'];
            $localized = is_array($entry['_localized_parameters'][$name] ?? null)
                ? $entry['_localized_parameters'][$name]
                : [];
            $description = trim((string) ($localized['description'] ?? $parameter['description'] ?? ''));
            $details = [];
            $values = is_array($parameter['values'] ?? null)
                ? array_values($parameter['values'])
                : [];
            if ($values === [] && ($parameter['type'] ?? null) === 'boolean') {
                $values = [true, false];
            }
            if ($values !== []) {
                $details[] = $this->parameterValuesTable(
                    $values,
                    is_array($localized['values'] ?? null) ? $localized['values'] : [],
                    $copy,
                    $parameter['default'] ?? null,
                    array_key_exists('default', $parameter),
                );
            }
            if ($values === [] && array_key_exists('default', $parameter)) {
                $details[] = '<p><strong>' . $this->escape($copy['default']) . ':</strong> <code>'
                    . $this->escape(CanonicalJson::encode($parameter['default'])) . '</code></p>';
            }
            if (($parameter['required'] ?? false) === true) {
                $details[] = '<p><strong>' . $this->escape($copy['required']) . ':</strong> '
                    . $this->escape($copy['yes']) . '</p>';
            }
            $parameterExample = '';
            if (isset($parameterExamples[$name])) {
                $examples = $parameterExamples[$name];
                $exampleSource = implode("\n", array_map(
                    static fn (array $example): string => rtrim($example['source']),
                    $examples,
                )) . "\n";
                $examplePreview = implode('', array_map(
                    static fn (array $example): string => $example['rendered'],
                    $examples,
                ));
                $sourceFragment = rtrim(
                    $this->markdown->render($this->sourceFence($exampleSource)),
                    "\n",
                );
                $parameterExample = '<div data-docara-component-parameter-example="'
                    . $this->escape($name) . '">'
                    . $this->examples->render(
                        id: 'component-' . $this->publicSlug($entry) . '-parameter-' . $name,
                        preview: $examplePreview,
                        sources: ['Markdown' => $sourceFragment],
                        exampleLabel: $copy['example'],
                        copyLabel: $copy['copy'],
                        copiedLabel: $copy['copied'],
                    ) . '</div>';
            }
            $items[] = '<section data-docara-component-parameter="' . $this->escape($name)
                . '" class="m-bottom-1">'
                . '<h2>' . $this->escape((string) ($localized['label'] ?? $name)) . '</h2>'
                . ($description === '' ? '' : $this->parameterDescription($name, $description, $locale))
                . implode('', $details)
                . $parameterExample
                . '</section>';
        }

        return '<div data-docara-component-parameters>' . implode('', $items) . '</div>';
    }

    /** @param array<string,mixed> $entry @param array<string,string> $copy @return list<array{label:string,source:string,parameter:?string}> */
    private function exampleSourceGroups(array $entry, string $source, array $copy): array
    {
        $lines = preg_split('/\r\n|\n|\r/u', trim($source));
        if (! is_array($lines)) {
            return [];
        }
        $groups = [];
        $current = [];
        $hasContent = false;
        foreach ($lines as $line) {
            $isMarker = preg_match('/^<!--\s*docara-(?:variant|parameter):/D', trim($line)) === 1;
            if ($isMarker && $hasContent) {
                $groups[] = $current;
                $current = [];
                $hasContent = false;
            }
            $current[] = $line;
            if (trim($line) !== '' && ! str_starts_with(trim($line), '<!--')) {
                $hasContent = true;
            }
        }
        if ($current !== []) {
            $groups[] = $current;
        }

        $result = [];
        foreach ($groups as $index => $groupLines) {
            $raw = implode("\n", $groupLines) . "\n";
            $label = '';
            preg_match_all('/<!--\s*(?<label>[^>]+?)\s*-->/u', $raw, $labelMatches);
            foreach (($labelMatches['label'] ?? []) as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== ''
                    && ! str_starts_with($candidate, 'docara-variant:')
                    && ! str_starts_with($candidate, 'docara-parameter:')
                ) {
                    $label = $candidate;
                    break;
                }
            }
            if ($label === '') {
                $label = $this->exampleGroupLabel($entry, $raw, $copy, $index);
            }
            $parameter = null;
            if (preg_match(
                '/<!--\s*docara-parameter:(?<name>[a-z][a-z0-9_]*)\s*-->/D',
                $raw,
                $parameterMatch,
            ) === 1) {
                $parameter = (string) $parameterMatch['name'];
                $parameterNames = array_values(array_filter(array_map(
                    static fn (mixed $parameter): ?string => is_array($parameter)
                        && is_string($parameter['name'] ?? null)
                            ? $parameter['name']
                            : null,
                    is_array($entry['authoring']['parameters'] ?? null)
                        ? $entry['authoring']['parameters']
                        : [],
                )));
                if (! in_array($parameter, $parameterNames, true)) {
                    throw new PortableConfigurationException(
                        'COMPONENT_CATALOG_PARAMETER_EXAMPLE_UNKNOWN',
                        'Component [' . $this->id($entry)
                            . "] example references unknown parameter [$parameter].",
                    );
                }
            } else {
                $parameter = $this->inferredExampleParameter($entry, $raw);
            }
            $result[] = [
                'label' => $label,
                'source' => $this->publishedExampleSource($raw),
                'parameter' => $parameter,
            ];
        }

        return $result;
    }

    /** @param array<string,mixed> $entry */
    private function inferredExampleParameter(array $entry, string $source): ?string
    {
        preg_match_all(
            '/<!--\s*docara-variant:(?<id>[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)*)\s*-->/D',
            $source,
            $matches,
        );
        $ids = array_values(array_map('strval', $matches['id'] ?? []));
        $coverage = is_array($entry['verification']['variant_coverage'] ?? null)
            ? array_values($entry['verification']['variant_coverage'])
            : [];
        foreach (array_reverse($ids) as $id) {
            foreach ($coverage as $variant) {
                if (! is_array($variant) || ($variant['id'] ?? null) !== $id) {
                    continue;
                }
                if (($variant['kind'] ?? null) === 'parameter'
                    && is_string($variant['name'] ?? null)
                ) {
                    return $variant['name'];
                }
                if (($variant['kind'] ?? null) !== 'state'
                    || ! is_string($variant['name'] ?? null)
                ) {
                    continue;
                }
                $state = $variant['name'];
                $candidates = [];
                foreach (($entry['authoring']['parameters'] ?? []) as $parameter) {
                    if (! is_array($parameter) || ! is_string($parameter['name'] ?? null)) {
                        continue;
                    }
                    $values = array_map(
                        'strval',
                        is_array($parameter['values'] ?? null) ? $parameter['values'] : [],
                    );
                    if (in_array($state, $values, true)) {
                        $candidates[] = $parameter['name'];
                    }
                }
                if (count($candidates) === 1) {
                    return $candidates[0];
                }
            }
        }

        return null;
    }

    /** @param array<string,mixed> $entry @param array<string,string> $copy */
    private function exampleGroupLabel(array $entry, string $source, array $copy, int $index): string
    {
        preg_match_all(
            '/<!--\s*docara-variant:(?<id>[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)*)\s*-->/D',
            $source,
            $matches,
        );
        $ids = array_values(array_filter(
            array_map('strval', $matches['id'] ?? []),
            static fn (string $id): bool => $id !== 'base',
        ));
        $coverage = is_array($entry['verification']['variant_coverage'] ?? null)
            ? array_values($entry['verification']['variant_coverage'])
            : [];
        foreach (array_reverse($ids) as $id) {
            foreach ($coverage as $variant) {
                if (! is_array($variant) || ($variant['id'] ?? null) !== $id) {
                    continue;
                }

                return match ((string) ($variant['kind'] ?? '')) {
                    'state' => (string) (
                        $entry['_localized_states'][$variant['name'] ?? '']
                        ?? $variant['name']
                        ?? $id
                    ),
                    'parameter' => $this->variantParameterLabel($entry, $variant),
                    default => $copy['variant_base'],
                };
            }
        }

        return $index === 0
            ? $copy['variant_base']
            : $copy['parameter_examples'] . ' ' . $index;
    }

    /** @param array<string, mixed> $entry @param array<string, string> $copy */
    private function metadataFragment(array $entry, array $copy): string
    {
        $metadata = is_array($entry['metadata'] ?? null) ? $entry['metadata'] : [];
        $capabilities = is_array($metadata['capabilities'] ?? null)
            ? array_values($metadata['capabilities'])
            : [];
        $badges = implode('', array_map(
            fn (mixed $capability): string => '<li><code>'
                . $this->escape((string) $capability) . '</code></li>',
            $capabilities,
        ));
        $sourceUrl = is_string($metadata['source_url'] ?? null) ? $metadata['source_url'] : null;
        $historyUrl = is_string($metadata['history_url'] ?? null) ? $metadata['history_url'] : null;
        $links = [];
        if ($sourceUrl !== null) {
            $links[] = '<a href="' . $this->escape($sourceUrl)
                . '" rel="noopener noreferrer">' . $this->escape($copy['open_source']) . '</a>';
        }
        if ($historyUrl !== null) {
            $links[] = '<a href="' . $this->escape($historyUrl)
                . '" rel="noopener noreferrer">' . $this->escape($copy['open_history']) . '</a>';
        }
        $historyNotice = ($metadata['history_exact'] ?? false) === true
            ? ''
            : '<p class="color-on-surface-variant m-bottom-0">'
                . $this->escape($copy['history_fallback']) . '</p>';

        return '<section data-docara-component-metadata class="bg-surface-0 border border-outline-variant radius-2 p-2">'
            . '<h2 class="m-top-0">' . $this->escape($copy['metadata']) . '</h2>'
            . '<dl class="grid grid-col-1 md:grid-col-2 gap-2 m-0">'
            . '<div><dt class="weight-7">' . $this->escape($copy['package'])
            . '</dt><dd class="m-0"><code>' . $this->escape((string) ($metadata['package'] ?? ''))
            . '</code></dd></div>'
            . '<div><dt class="weight-7">' . $this->escape($copy['version'])
            . '</dt><dd class="m-0"><code>' . $this->escape((string) ($metadata['version'] ?? ''))
            . '</code></dd></div>'
            . '<div><dt class="weight-7">' . $this->escape($copy['owner'])
            . '</dt><dd class="m-0"><code>' . $this->escape((string) ($metadata['owner'] ?? ''))
            . '</code></dd></div>'
            . '<div><dt class="weight-7">' . $this->escape($copy['source'])
            . '</dt><dd class="m-0"><code>' . $this->escape((string) ($metadata['source_ref'] ?? ''))
            . '</code></dd></div>'
            . '<div><dt class="weight-7">' . $this->escape($copy['revision'])
            . '</dt><dd class="m-0"><code>' . $this->breakableCode((string) ($metadata['revision'] ?? ''))
            . '</code></dd></div>'
            . '<div><dt class="weight-7">' . $this->escape($copy['author'])
            . '</dt><dd class="m-0">' . $this->escape((string) ($metadata['author'] ?? ''))
            . '</dd></div>'
            . '<div><dt class="weight-7">' . $this->escape($copy['changed_at'])
            . '</dt><dd class="m-0"><time datetime="' . $this->escape((string) ($metadata['changed_at'] ?? ''))
            . '">' . $this->escape((string) ($metadata['changed_at'] ?? '')) . '</time></dd></div>'
            . '<div><dt class="weight-7">' . $this->escape($copy['source_hash'])
            . '</dt><dd class="m-0"><code>' . $this->breakableCode((string) ($metadata['source_sha256'] ?? ''))
            . '</code></dd></div></dl>'
            . ($links === [] ? '' : '<p class="flex flex-wrap gap-1">' . implode('', $links) . '</p>')
            . $historyNotice
            . '<p class="weight-7">' . $this->escape($copy['capabilities']) . '</p>'
            . '<ul class="flex flex-wrap gap-1 list-none m-0 p-0">' . $badges . '</ul>'
            . '</section>';
    }

    private function breakableCode(string $value): string
    {
        return implode('<wbr>', array_map(
            fn (string $chunk): string => $this->escape($chunk),
            str_split($value, 8),
        ));
    }

    /** @param array<string, mixed> $entry @param array<string, string> $copy */
    private function variantCoverageFragment(array $entry, array $copy): string
    {
        $coverage = is_array($entry['verification']['variant_coverage'] ?? null)
            ? array_values($entry['verification']['variant_coverage'])
            : [];
        $items = [];
        foreach ($coverage as $variant) {
            if (! is_array($variant)) {
                continue;
            }
            $kind = (string) ($variant['kind'] ?? '');
            $label = match ($kind) {
                'base' => $copy['variant_base'],
                'state' => (string) (
                    $entry['_localized_states'][$variant['name'] ?? '']
                    ?? $variant['name']
                    ?? ''
                ),
                'parameter' => $this->variantParameterLabel($entry, $variant),
                default => '',
            };
            $kindLabel = match ($kind) {
                'base' => $copy['variant_base'],
                'state' => $copy['variant_state'],
                'parameter' => $copy['variant_parameter'],
                default => '',
            };
            $items[] = '<li class="flex flex-col gap-1/4 bg-surface border border-outline-variant radius-1 p-1">'
                . '<span class="label-medium color-on-surface-variant">'
                . $this->escape($kindLabel) . '</span><strong>' . $this->escape($label)
                . '</strong><code>' . $this->escape((string) ($variant['id'] ?? ''))
                . '</code></li>';
        }

        return '<section data-docara-component-variants class="flex flex-col gap-2">'
            . '<h2 class="m-0">' . $this->escape($copy['variants']) . '</h2>'
            . '<ul class="grid grid-col-1 md:grid-col-2 gap-1 list-none m-0 p-0">'
            . implode('', $items) . '</ul></section>';
    }

    /** @param array<string, mixed> $entry @param array<string, mixed> $variant */
    private function variantParameterLabel(array $entry, array $variant): string
    {
        $name = (string) ($variant['name'] ?? '');
        $value = (string) ($variant['value'] ?? '');
        $localized = is_array($entry['_localized_parameters'][$name] ?? null)
            ? $entry['_localized_parameters'][$name]
            : [];
        $parameter = (string) ($localized['label'] ?? $name);
        $values = is_array($localized['values'] ?? null) ? $localized['values'] : [];
        $valueLabel = (string) ($values[$value] ?? ($value === '' ? 'default' : $value));

        return $parameter . ' · ' . $valueLabel;
    }

    /** @param array<string, mixed> $entry */
    private function assertVariantExamples(array $entry, string $source): void
    {
        $coverage = is_array($entry['verification']['variant_coverage'] ?? null)
            ? array_values($entry['verification']['variant_coverage'])
            : [];
        preg_match_all(
            '/<!--\s*docara-variant:([a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)*)\s*-->/D',
            $source,
            $matches,
        );
        $actual = array_values(array_unique($matches[1] ?? []));
        $expected = array_map(
            static fn (array $variant): string => (string) $variant['id'],
            $coverage,
        );
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($actual !== $expected) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_VARIANT_DEMO_MISMATCH',
                'Component [' . $this->id($entry)
                    . '] example markers must exactly cover its admitted variant matrix.',
            );
        }
    }

    /** @param array<string, mixed> $entry */
    private function id(array $entry): string
    {
        return PublicComponentPage::id($entry);
    }

    /** @param array<string, mixed> $entry */
    private function publicSlug(array $entry): string
    {
        return PublicComponentPage::slug($entry);
    }

    public function publishedExampleSource(string $source): string
    {
        $source = preg_replace(
            '/^<!--\s*docara-(?:variant:[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)*|parameter:[a-z][a-z0-9_]*)\s*-->\R?/mD',
            '',
            str_replace(["\r\n", "\r"], "\n", $source),
        ) ?? $source;

        return trim($source) . "\n";
    }

    /** @param array<string, string> $copy */
    private function familyLabel(string $family, array $copy): string
    {
        return match ($family) {
            'native_markdown' => $copy['family_native_single'],
            'docara_typed' => $copy['family_typed_single'],
            'framework_smart' => $copy['family_smart_single'],
            default => throw new PortableConfigurationException(
                'COMPONENT_CATALOG_FAMILY_INVALID',
                $family,
            ),
        };
    }

    /** @return array<string, string> */
    private function copy(string $locale): array
    {
        $keys = [
            'catalog_title', 'catalog_description', 'catalog_intro', 'family_native_plural',
            'family_typed_plural', 'family_smart_plural', 'family_native_single',
            'family_typed_single', 'family_smart_single', 'limitations',
            'owner', 'example', 'result', 'important', 'call', 'parameters', 'parameter_relationships',
            'allowed_combinations', 'when', 'then', 'states', 'name', 'type',
            'required', 'default', 'values', 'rules', 'purpose', 'yes', 'no',
            'no_limitations',
            'metadata', 'package', 'version', 'source', 'revision', 'author',
            'changed_at', 'source_hash', 'open_source', 'open_history',
            'history_fallback', 'capabilities', 'variants',
            'variant_base', 'variant_state', 'variant_parameter',
            'component', 'component_family', 'component_purpose', 'group_text_code',
            'group_structure', 'group_media', 'group_actions', 'call_intro',
            'parameters_intro', 'parameter_examples', 'parameter_examples_intro',
            'considerations', 'source_intro', 'copy', 'copied',
        ];
        $copy = [];
        foreach ($keys as $key) {
            $copy[$key] = $this->translator->message($locale, 'catalog.' . $key);
        }

        return $copy;
    }

    /** @param array<string, mixed> $parameter */
    private function parameterRules(array $parameter): string
    {
        $items = [];
        $validation = is_array($parameter['validation'] ?? null)
            ? $parameter['validation']
            : [];
        foreach ($validation as $name => $value) {
            if (! is_string($name) || ! is_scalar($value)) {
                continue;
            }
            $items[] = '<li><code>' . $this->escape($name) . '</code>: <code>'
                . $this->escape(CanonicalJson::encode($value)) . '</code></li>';
        }
        $mirrors = is_array($parameter['mirrors'] ?? null)
            ? array_values($parameter['mirrors'])
            : [];
        if ($mirrors !== []) {
            $items[] = '<li><code>mirrors</code>: <code>'
                . $this->escape(implode(', ', array_map('strval', $mirrors)))
                . '</code></li>';
        }

        return $items === []
            ? '—'
            : '<ul class="flex flex-col gap-1 list-none m-0 p-0">'
                . implode('', $items) . '</ul>';
    }

    /** @param list<mixed> $values @param array<string, mixed> $labels @param array<string,string> $copy */
    private function parameterValuesTable(
        array $values,
        array $labels,
        array $copy,
        mixed $default,
        bool $hasDefault,
    ): string
    {
        $rows = implode('', array_map(
            function (mixed $value) use ($labels, $copy, $default, $hasDefault): string {
                $token = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                $label = is_bool($value)
                    ? ($value ? $copy['yes'] : $copy['no'])
                    : (is_string($labels[$token] ?? null) ? $labels[$token] : $token);
                $defaultLabel = $hasDefault && $value === $default
                    ? ' <strong>· ' . $this->escape($copy['default']) . '</strong>'
                    : '';

                return '<tr><td><code>' . $this->escape($token) . '</code></td><td>'
                    . $this->escape($label) . $defaultLabel . '</td></tr>';
            },
            $values,
        ));

        return '<div data-docara-table-scroll class="overflow-auto m-bottom-1"><table class="table table-border table-stripe">'
            . '<thead><tr><th>' . $this->escape($copy['values']) . '</th><th>'
            . $this->escape($copy['purpose']) . '</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div>';
    }

    private function parameterDescription(string $name, string $description, string $locale): string
    {
        $description = $this->lowercaseSentenceStart(trim($description));
        $parameter = '<code>' . $this->escape($name) . '</code>';

        return match ($locale) {
            'ru' => '<p>Параметр ' . $parameter . ' ' . $this->escape($description) . '</p>',
            'en' => '<p>The ' . $parameter . ' parameter ' . $this->escape($description) . '</p>',
            default => '<p>' . $parameter . ': ' . $this->escape(trim($description)) . '</p>',
        };
    }

    private function lowercaseSentenceStart(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $first = mb_substr($value, 0, 1, 'UTF-8');
        $second = mb_substr($value, 1, 1, 'UTF-8');
        if ($second !== '' && $second === mb_strtoupper($second, 'UTF-8')) {
            return $value;
        }

        return mb_strtolower($first, 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
    }

    /** @param list<mixed> $values @param array<string, mixed> $labels */
    private function presentedValues(array $values, array $labels): string
    {
        return implode(', ', array_map(
            static function (mixed $value) use ($labels): string {
                $token = (string) $value;
                $label = is_string($labels[$token] ?? null) ? $labels[$token] : $token;

                return $label === $token ? $token : $label . ' — ' . $token;
            },
            $values,
        ));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function sourceFence(string $source): string
    {
        $normalized = rtrim(str_replace(["\r\n", "\r"], "\n", $source), "\n");
        preg_match_all('/~+/', $normalized, $matches);
        $longest = 0;
        foreach ($matches[0] ?? [] as $run) {
            $longest = max($longest, strlen($run));
        }
        $fence = str_repeat('~', max(4, $longest + 1));

        return $fence . "markdown\n" . $normalized . "\n" . $fence . "\n";
    }

    public function normalizedFragmentHash(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!doctype html><html><head><meta charset="UTF-8"></head><body>'
            . $html . '</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_FRAGMENT_INVALID',
                'A generated component catalogue fragment is not valid HTML.',
            );
        }
        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_FRAGMENT_INVALID',
                'A generated component catalogue fragment has no body.',
            );
        }
        $roots = [];
        foreach ($body->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $roots[] = $child;

                continue;
            }
            if ($child->nodeType === XML_TEXT_NODE && trim((string) $child->nodeValue) === '') {
                continue;
            }
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_FRAGMENT_INVALID',
                'A generated component catalogue fragment must have one element root.',
            );
        }
        if (count($roots) !== 1) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_FRAGMENT_INVALID',
                'A generated component catalogue fragment must have one element root.',
            );
        }

        return hash('sha256', CanonicalJson::encode($this->normalizeNode($roots[0])));
    }

    /** @return array<string, mixed> */
    private function normalizeNode(DOMNode $node): array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return ['text' => (string) $node->nodeValue];
        }
        if (! $node instanceof DOMElement) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_FRAGMENT_NODE_FORBIDDEN',
                (string) $node->nodeName,
            );
        }

        $attributes = [];
        foreach ($node->attributes as $attribute) {
            $attributes[strtolower($attribute->nodeName)] = $attribute->nodeValue;
        }
        ksort($attributes, SORT_STRING);
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $this->normalizeNode($child);
        }

        return [
            'element' => strtolower($node->tagName),
            'attributes' => $attributes,
            'children' => $children,
        ];
    }
}
