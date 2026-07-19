<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use DOMDocument;
use DOMElement;
use DOMNode;
use Simai\Docara\Framework\ComponentDirectiveDocument;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\ResolvedPagePlan;

final readonly class PortableComponentCatalogProjector
{
    public function __construct(
        private PortableMarkdownRenderer $markdown,
        private string $packageRoot = __DIR__ . '/../..',
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
        array $reservedDocumentIds = [],
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
            if ($isSupported) {
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
        $presentedEntries = array_map(
            fn (array $entry): array => $this->presentEntry($entry, $locale),
            $entries,
        );
        $deploymentBase = $baseUrl === '/' ? '/' : '/' . trim($baseUrl, '/') . '/';
        $catalogRoute = $deploymentBase . 'components/catalog/';
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
            pagePath: $contentRoot . '/components/catalog/index.md',
            title: $copy['catalog_title'],
            description: $copy['catalog_description'],
            url: $catalogRoute,
            output: 'components/catalog/index.html',
            contentHtml: $indexFragment,
            components: $indexComponents,
            homeUrl: $homeUrl,
            navigationHidden: null,
            sourceMarkdown: '# ' . $copy['catalog_title'] . "\n",
            outline: $indexOutline['items'],
        );
        $index['component_catalog_kind'] = 'index';
        $index['component_catalog_breadcrumbs'] = $catalogBreadcrumbs;
        $index['component_catalog_previous'] = null;
        $index['component_catalog_next'] = null;

        $pages = [];
        $receiptPages = [];
        foreach ($supported as $entry) {
            $id = $this->id($entry);
            $presentedEntry = $this->presentEntry($entry, $locale);
            $source = $this->exampleSource($presentedEntry);
            $components = $runtime->extract($source, '@docara/component-catalog/' . $id . '.md');
            $renderedFragment = $components->hydrate(
                $this->markdown->render($components->markdownWithPlaceholders),
            );
            if (trim($renderedFragment) === '') {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_EXAMPLE_EMPTY',
                    "Component [$id] produced an empty example.",
                );
            }

            $exampleHash = hash('sha256', $source);
            $renderedHash = hash('sha256', $renderedFragment);
            $route = $catalogRoute . rawurlencode($id) . '/';
            $output = 'components/catalog/' . $id . '/index.html';
            $detailOutline = (new PortableDocumentOutlineBuilder)->build(
                $this->detailFragment(
                    $presentedEntry,
                    $source,
                    $renderedFragment,
                    $exampleHash,
                    $renderedHash,
                    $copy,
                ),
                $tocDepth,
                $reservedDocumentIds,
            );
            $detailFragment = $detailOutline['html'];
            $page = $this->page(
                basePlan: $basePlan,
                pagePath: $contentRoot . '/components/catalog/' . $id . '.md',
                title: (string) $presentedEntry['title'],
                description: (string) $presentedEntry['description'],
                url: $route,
                output: $output,
                contentHtml: $detailFragment,
                components: $components,
                homeUrl: $homeUrl,
                navigationHidden: true,
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
                'output' => 'components/catalog/index.html',
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

    /** @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function presentEntry(array $entry, string $locale): array
    {
        $language = $this->language($locale);
        if ($language !== 'ru') {
            return $entry;
        }
        $presentation = $entry['presentation']['ru'] ?? null;
        if (! is_array($presentation)) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_PRESENTATION_MISSING',
                $this->id($entry),
            );
        }
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
        $relative = 'resources/component-catalog/assets/docara-mark.svg';
        $candidate = rtrim($this->packageRoot, '/\\');
        foreach (explode('/', $relative) as $segment) {
            $candidate .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($candidate)) {
                throw new PortableConfigurationException(
                    'COMPONENT_CATALOG_ASSET_INVALID',
                    $relative,
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

        return ['_docara/component-catalog/docara-mark.svg' => $bytes];
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
            'max_width' => (string) data_get($configuration, 'layout.max_width', 'normal'),
            'navigation_hidden' => (bool) data_get($configuration, 'navigation.hidden', false),
            'navigation_order' => data_get($configuration, 'navigation.order'),
            'search_enabled' => (bool) data_get($configuration, 'search.enabled', false),
            'search_indexed' => (bool) data_get($configuration, 'search.indexed', true),
            'reading_breadcrumbs' => (bool) data_get($configuration, 'reading.breadcrumbs', true),
            'reading_toc' => (bool) data_get($configuration, 'reading.toc', true),
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
            'native_markdown' => $copy['family_native_plural'],
            'docara_typed' => $copy['family_typed_plural'],
            'framework_smart' => $copy['family_smart_plural'],
        ];
        $sections = [];
        foreach ($groups as $family => $label) {
            $items = [];
            foreach ($entries as $entry) {
                if (($entry['family'] ?? null) !== $family
                    || ($entry['lifecycle'] ?? null) !== 'supported'
                ) {
                    continue;
                }
                $id = $this->id($entry);
                $items[] = '<li ' . $this->filterItemAttributes($entry) . '>'
                    . '<a class="docara-document-link flex flex-col gap-1 color-on-surface decoration-none'
                    . ' bg-surface-0 border border-outline-variant radius-2 p-2 h-full w-full" href="'
                    . $this->escape($catalogRoute . rawurlencode($id) . '/')
                    . '"><span class="weight-7">' . $this->escape((string) $entry['title']) . '</span>'
                    . '<code>' . $this->escape($id) . '</code>'
                    . '<span class="color-on-surface-variant">'
                    . $this->escape((string) $entry['description']) . '</span></a></li>';
            }
            if ($items !== []) {
                $sections[] = '<section data-docara-component-section class="flex flex-col gap-2"><h2>'
                    . $this->escape($label) . '</h2>'
                    . '<ul class="grid grid-col-1 md:grid-col-2 gap-2 list-none m-0 p-0">'
                    . implode('', $items) . '</ul></section>';
            }
        }

        $unavailable = [];
        foreach ($entries as $entry) {
            if (($entry['lifecycle'] ?? null) === 'supported') {
                continue;
            }
            $id = $this->id($entry);
            $gap = is_array($entry['gap'] ?? null) ? $entry['gap'] : [];
            $reason = (string) (
                $gap['reason']
                ?? ($entry['limitations'][0] ?? $copy['unavailable_fallback'])
            );
            $limitations = is_array($entry['limitations'] ?? null)
                ? array_values($entry['limitations'])
                : [];
            $limitationHtml = $limitations === []
                ? '<p>' . $this->escape($copy['no_limitations']) . '</p>'
                : '<ul>' . implode('', array_map(
                    fn (mixed $limitation): string => '<li>'
                        . $this->escape((string) $limitation) . '</li>',
                    $limitations,
                )) . '</ul>';
            $unavailable[] = '<li ' . $this->filterItemAttributes($entry)
                . '><details data-docara-component-gap="' . $this->escape($id)
                . '" class="bg-surface-0 border border-outline-variant radius-2 p-2">'
                . '<summary data-docara-component-details-summary class="cursor-pointer">'
                . '<span class="flex flex-wrap items-center gap-1"><strong>'
                . $this->escape((string) $entry['title']) . '</strong><code>'
                . $this->escape($id) . '</code></span></summary>'
                . '<div class="flex flex-col gap-2 p-top-2">'
                . '<p class="m-0">' . $this->escape($reason) . '</p>'
                . '<dl class="flex flex-col gap-2 m-0">'
                . '<div><dt class="weight-7">' . $this->escape($copy['owner'])
                . '</dt><dd class="m-0"><code>'
                . $this->escape((string) ($gap['owner'] ?? ''))
                . '</code></dd></div>'
                . '<div><dt class="weight-7">' . $this->escape($copy['fallback'])
                . '</dt><dd class="m-0">' . $this->escape((string) ($gap['fallback'] ?? ''))
                . '</dd></div><div><dt class="weight-7">'
                . $this->escape($copy['admission_condition'])
                . '</dt><dd class="m-0">'
                . $this->escape((string) ($gap['admission_condition'] ?? ''))
                . '</dd></div></dl><div><p class="m-0 weight-7">'
                . $this->escape($copy['limitations']) . '</p>' . $limitationHtml
                . '</div></div></details></li>';
        }
        if ($unavailable !== []) {
            $sections[] = '<section data-docara-component-section class="flex flex-col gap-2"><h2>'
                . $this->escape($copy['unavailable_title']) . '</h2>'
                . '<ul class="flex flex-col gap-2 list-none m-0 p-0">'
                . implode('', $unavailable) . '</ul></section>';
        }

        return '<div data-docara-component-catalog-index class="flex flex-col gap-3">'
            . '<h1>' . $this->escape($copy['catalog_title']) . '</h1>'
            . '<p>' . $this->escape($copy['catalog_intro']) . '</p>'
            . $this->filterFragment(count($entries), $copy)
            . implode('', $sections)
            . '<p data-docara-component-filter-empty hidden class="bg-surface border'
            . ' border-outline-variant radius-2 p-2 m-0">'
            . $this->escape($copy['filter_empty']) . '</p>'
            . '</div>';
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function filterItemAttributes(array $entry): string
    {
        $lifecycle = (string) ($entry['lifecycle'] ?? '');
        $availability = $lifecycle === 'supported' ? 'supported' : 'unavailable';
        $gap = is_array($entry['gap'] ?? null) ? $entry['gap'] : [];
        $limitations = is_array($entry['limitations'] ?? null) ? $entry['limitations'] : [];
        $search = implode(' ', array_filter([
            $this->id($entry),
            (string) ($entry['title'] ?? ''),
            (string) ($entry['description'] ?? ''),
            (string) ($entry['family'] ?? ''),
            $lifecycle,
            (string) ($gap['reason'] ?? ''),
            ...array_map('strval', $limitations),
        ], static fn (string $value): bool => $value !== ''));

        return 'data-docara-component-item="' . $this->escape($this->id($entry))
            . '" data-docara-component-family="'
            . $this->escape((string) ($entry['family'] ?? ''))
            . '" data-docara-component-availability="' . $availability
            . '" data-docara-component-search="' . $this->escape($search) . '"';
    }

    /**
     * @param  array<string, string>  $copy
     */
    private function filterFragment(int $total, array $copy): string
    {
        return '<form data-docara-component-filter'
            . ' data-docara-component-filter-controller="docara.component_filter.v1"'
            . ' data-docara-component-total="' . $total . '"'
            . ' data-docara-component-status-label="' . $this->escape($copy['filter_status'])
            . '" class="bg-surface border border-outline-variant radius-2 p-2 flex flex-col gap-2">'
            . '<fieldset class="m-0 p-0 border-none flex flex-col gap-2">'
            . '<legend class="weight-7 p-0">' . $this->escape($copy['filter_title']) . '</legend>'
            . '<div class="grid grid-col-1 md:grid-col-3 gap-2">'
            . '<label class="sf-input sf-input--size-1 sf-input--bordered flex flex-col">'
            . '<span class="sf-input-label flex"><span class="sf-input-text">'
            . $this->escape($copy['filter_query_label']) . '</span></span>'
            . '<span class="sf-input-field items-cross-center transition flex">'
            . '<span class="sf-input-left flex"><sf-icon icon="search" aria-hidden="true"></sf-icon></span>'
            . '<input data-docara-component-filter-query class="sf-input-text-container flex-1"'
            . ' type="search" autocomplete="off" spellcheck="false" placeholder="'
            . $this->escape($copy['filter_query_placeholder']) . '"></span></label>'
            . '<label class="flex flex-col gap-1"><span class="weight-6">'
            . $this->escape($copy['filter_family_label']) . '</span>'
            . '<select data-docara-component-filter-family'
            . ' class="docara-component-filter-control bg-surface-0 color-on-surface border'
            . ' border-outline-variant radius-1 p-1">'
            . '<option value="">' . $this->escape($copy['filter_family_all']) . '</option>'
            . '<option value="native_markdown">' . $this->escape($copy['family_native_plural']) . '</option>'
            . '<option value="docara_typed">' . $this->escape($copy['family_typed_plural']) . '</option>'
            . '<option value="framework_smart">' . $this->escape($copy['family_smart_plural']) . '</option>'
            . '<option value="requirement">' . $this->escape($copy['filter_requirements']) . '</option>'
            . '</select></label>'
            . '<label class="flex flex-col gap-1"><span class="weight-6">'
            . $this->escape($copy['filter_availability_label']) . '</span>'
            . '<select data-docara-component-filter-availability'
            . ' class="docara-component-filter-control bg-surface-0 color-on-surface border'
            . ' border-outline-variant radius-1 p-1">'
            . '<option value="">' . $this->escape($copy['filter_availability_all']) . '</option>'
            . '<option value="supported">' . $this->escape($copy['filter_supported']) . '</option>'
            . '<option value="unavailable">' . $this->escape($copy['filter_unavailable']) . '</option>'
            . '</select></label></div></fieldset>'
            . '<div class="flex flex-wrap items-center content-main-between gap-1">'
            . '<p data-docara-component-filter-status class="color-on-surface-variant m-0"'
            . ' aria-live="polite">' . $this->escape($copy['filter_status']) . ': '
            . $total . ' / ' . $total . '</p>'
            . '<button data-docara-component-filter-reset hidden type="button"'
            . ' class="sf-button sf-button--link sf-button--on-surface sf-button--size-1 radius-default">'
            . '<span class="sf-button-text-container">' . $this->escape($copy['filter_reset'])
            . '</span></button></div></form>';
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, string>  $copy
     */
    public function detailFragment(
        array $entry,
        string $source,
        string $renderedFragment,
        string $exampleHash,
        string $renderedHash,
        array $copy,
    ): string {
        $id = $this->id($entry);
        $parts = [
            '<h1>' . $this->escape((string) $entry['title']) . '</h1>',
            '<p class="color-on-surface-variant"><code>' . $this->escape($id)
                . '</code> · '
                . $this->escape($this->familyLabel((string) $entry['family'], $copy)) . '</p>',
            '<p>' . $this->escape((string) $entry['description']) . '</p>',
            '<h2>' . $this->escape($copy['example']) . '</h2>',
            '<div data-docara-component-demo="' . $this->escape($id)
                . '" data-docara-outline-exclude class="bg-surface border border-outline-variant radius-2 p-3">'
                . $renderedFragment . '</div>',
            '<h2>' . $this->escape($copy['call']) . '</h2>',
            '<pre data-docara-component-source="' . $this->escape($id)
                . '" class="bg-surface border border-outline-variant radius-2 p-2 overflow-auto">'
                . '<code class="language-markdown overflow-auto">'
                . $this->escape(str_replace(["\r\n", "\r"], "\n", $source)) . '</code></pre>',
        ];

        $parameters = is_array($entry['authoring']['parameters'] ?? null)
            ? array_values($entry['authoring']['parameters'])
            : [];
        if ($parameters !== []) {
            $rows = [];
            foreach ($parameters as $parameter) {
                if (! is_array($parameter)) {
                    continue;
                }
                $values = is_array($parameter['values'] ?? null)
                    ? $this->presentedValues(
                        $parameter['values'],
                        $entry['_localized_parameters'][$parameter['name']]['values'] ?? [],
                    )
                    : '';
                $default = array_key_exists('default', $parameter)
                    ? CanonicalJson::encode($parameter['default'])
                    : '—';
                $localized = $entry['_localized_parameters'][$parameter['name']] ?? [];
                $rules = $this->parameterRules($parameter);
                $rows[] = '<tr><td class="min-w-min"><span class="flex flex-col gap-1"><strong>'
                    . $this->escape((string) ($localized['label'] ?? $parameter['name'] ?? ''))
                    . '</strong><code class="wrap-none">' . $this->escape((string) ($parameter['name'] ?? ''))
                    . '</code></span></td><td class="wrap-none">'
                    . $this->escape((string) ($parameter['type'] ?? ''))
                    . '</td><td class="wrap-none">' . (($parameter['required'] ?? false) === true
                        ? $this->escape($copy['yes'])
                        : $this->escape($copy['no']))
                    . '</td><td class="wrap-none">' . $this->escape($default)
                    . '</td><td>' . $this->escape($values)
                    . '</td><td>' . $rules
                    . '</td><td>' . $this->escape((string) (
                        $localized['description'] ?? $parameter['description'] ?? ''
                    ))
                    . '</td></tr>';
            }
            $parts[] = '<h2>' . $this->escape($copy['parameters'])
                . '</h2><div class="overflow-auto"><table class="min-w-full"><thead><tr>'
                . '<th>' . $this->escape($copy['name']) . '</th>'
                . '<th>' . $this->escape($copy['type']) . '</th>'
                . '<th>' . $this->escape($copy['required']) . '</th>'
                . '<th>' . $this->escape($copy['default']) . '</th>'
                . '<th>' . $this->escape($copy['values']) . '</th>'
                . '<th>' . $this->escape($copy['rules']) . '</th>'
                . '<th>' . $this->escape($copy['purpose']) . '</th></tr></thead><tbody>'
                . implode('', $rows) . '</tbody></table></div>';
        }

        $constraints = is_array($entry['authoring']['constraints'] ?? null)
            ? $entry['authoring']['constraints']
            : [];
        $constraintItems = [];
        foreach (($constraints['allowed_combinations'] ?? []) as $combination) {
            if (! is_array($combination)) {
                continue;
            }
            $constraintItems[] = '<li><span class="weight-7">'
                . $this->escape($copy['allowed_combinations'])
                . ' <code>allowed_combinations</code></span>'
                . '<div class="overflow-auto"><code class="wrap-none">'
                . $this->escape(CanonicalJson::encode($combination))
                . '</code></div></li>';
        }
        foreach (($constraints['requires'] ?? []) as $requirement) {
            if (! is_array($requirement)) {
                continue;
            }
            $constraintItems[] = '<li><span class="weight-7"><code>requires</code></span>'
                . '<div class="overflow-auto"><code class="wrap-none">'
                . $this->escape($copy['when'] . ' '
                    . CanonicalJson::encode($requirement['when'] ?? [])
                    . ' → ' . $copy['then'] . ' '
                    . CanonicalJson::encode($requirement['then'] ?? []))
                . '</code></div></li>';
        }
        if ($constraintItems !== []) {
            $parts[] = '<h2>' . $this->escape($copy['parameter_relationships'])
                . '</h2><ul class="flex flex-col gap-2">'
                . implode('', $constraintItems) . '</ul>';
        }

        $states = is_array($entry['states'] ?? null) ? array_values($entry['states']) : [];
        if ($states !== []) {
            $items = array_map(function (mixed $state) use ($entry): string {
                $token = (string) $state;
                $label = (string) ($entry['_localized_states'][$token] ?? $token);

                return '<li><span class="flex items-center gap-1"><span>'
                    . $this->escape($label) . '</span><code>' . $this->escape($token)
                    . '</code></span></li>';
            }, $states);
            $parts[] = '<h2>' . $this->escape($copy['states'])
                . '</h2><ul class="flex flex-wrap gap-1 list-none m-0 p-0">'
                . implode('', $items) . '</ul>';
        }

        $sourceReferences = [];
        $provenance = is_array($entry['provenance'] ?? null) ? $entry['provenance'] : [];
        foreach ($provenance as $key => $value) {
            if (is_scalar($value)) {
                $sourceReferences[] = '<li><code>' . $this->escape((string) $key)
                    . '</code>: ' . $this->escape((string) $value) . '</li>';
            }
        }
        $sourceReferences[] = '<li><code>docs_ref</code>: '
            . $this->escape((string) $entry['docs_ref']) . '</li>';
        $sourceReferences[] = '<li><code>example_ref</code>: '
            . $this->escape((string) $entry['example_ref']) . '</li>';
        $limitations = is_array($entry['limitations'] ?? null) ? array_values($entry['limitations']) : [];
        $limitationHtml = $limitations === []
            ? '<p>' . $this->escape($copy['no_limitations']) . '</p>'
            : '<ul>' . implode('', array_map(
                fn (mixed $limitation): string => '<li>' . $this->escape((string) $limitation) . '</li>',
                $limitations,
            )) . '</ul>';
        $parts[] = '<details class="bg-surface border border-outline-variant radius-2 p-2">'
            . '<summary data-docara-component-details-summary class="weight-7 cursor-pointer">'
            . $this->escape($copy['limitations_and_source']) . '</summary>'
            . '<div class="flex flex-col gap-2 p-top-2">' . $limitationHtml
            . '<ul>' . implode('', $sourceReferences) . '</ul></div></details>';

        return '<div data-docara-component-detail="' . $this->escape($id)
            . '" data-docara-example-source-sha256="' . $exampleHash
            . '" data-docara-example-render-sha256="' . $renderedHash
            . '" class="flex flex-col gap-3">' . implode('', $parts) . '</div>';
    }

    /** @param array<string, mixed> $entry */
    private function id(array $entry): string
    {
        $id = $entry['id'] ?? null;
        if (! is_string($id)
            || preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $id) !== 1
        ) {
            throw new PortableConfigurationException(
                'COMPONENT_CATALOG_ID_INVALID',
                is_scalar($id) ? (string) $id : '',
            );
        }

        return $id;
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

    private function language(string $locale): string
    {
        return strtolower((string) preg_split('/[-_]/', $locale, 2)[0]);
    }

    /** @return array<string, string> */
    private function copy(string $locale): array
    {
        if ($this->language($locale) === 'ru') {
            return [
                'catalog_title' => 'Каталог компонентов',
                'catalog_description' => 'Живые примеры всех компонентов, которые поддерживает эта сборка Docara.',
                'catalog_intro' => 'Проверенные примеры, точный синтаксис и границы компонентов этой сборки Docara.',
                'filter_title' => 'Найти компонент',
                'filter_query_label' => 'Поиск',
                'filter_query_placeholder' => 'Название, ID или назначение',
                'filter_family_label' => 'Тип',
                'filter_family_all' => 'Все типы',
                'filter_requirements' => 'Запланированные возможности',
                'filter_availability_label' => 'Доступность',
                'filter_availability_all' => 'Все состояния',
                'filter_supported' => 'Поддерживается',
                'filter_unavailable' => 'Недоступно сейчас',
                'filter_reset' => 'Сбросить фильтры',
                'filter_status' => 'Показано',
                'filter_empty' => 'По заданным условиям ничего не найдено.',
                'family_native_plural' => 'Markdown',
                'family_typed_plural' => 'Компоненты Docara',
                'family_smart_plural' => 'Smart-компоненты Simai Framework',
                'family_native_single' => 'Markdown',
                'family_typed_single' => 'Компонент Docara',
                'family_smart_single' => 'Smart-компонент Simai Framework',
                'unavailable_title' => 'Недоступно сейчас',
                'unavailable_fallback' => 'Недоступно в этой сборке.',
                'fallback' => 'Безопасная замена',
                'admission_condition' => 'Условие допуска',
                'limitations' => 'Ограничения',
                'owner' => 'Владелец развития',
                'example' => 'Пример',
                'call' => 'Вызов',
                'parameters' => 'Параметры',
                'parameter_relationships' => 'Связи параметров',
                'allowed_combinations' => 'Допустимые сочетания',
                'when' => 'если',
                'then' => 'то',
                'states' => 'Состояния',
                'name' => 'Имя',
                'type' => 'Тип',
                'required' => 'Обязателен',
                'default' => 'По умолчанию',
                'values' => 'Значения',
                'rules' => 'Правила',
                'purpose' => 'Назначение',
                'yes' => 'Да',
                'no' => 'Нет',
                'no_limitations' => 'Дополнительных ограничений не заявлено.',
                'limitations_and_source' => 'Ограничения и источник',
            ];
        }

        return [
            'catalog_title' => 'Component catalog',
            'catalog_description' => 'Live examples of every component supported by this Docara build.',
            'catalog_intro' => 'Verified examples, exact syntax and component boundaries for this Docara build.',
            'filter_title' => 'Find a component',
            'filter_query_label' => 'Search',
            'filter_query_placeholder' => 'Name, ID or purpose',
            'filter_family_label' => 'Type',
            'filter_family_all' => 'All types',
            'filter_requirements' => 'Planned capabilities',
            'filter_availability_label' => 'Availability',
            'filter_availability_all' => 'All availability states',
            'filter_supported' => 'Supported',
            'filter_unavailable' => 'Unavailable in this build',
            'filter_reset' => 'Reset filters',
            'filter_status' => 'Shown',
            'filter_empty' => 'No components match these filters.',
            'family_native_plural' => 'Markdown',
            'family_typed_plural' => 'Docara components',
            'family_smart_plural' => 'Simai Framework Smart components',
            'family_native_single' => 'Markdown',
            'family_typed_single' => 'Docara component',
            'family_smart_single' => 'Simai Framework Smart component',
            'unavailable_title' => 'Unavailable in this build',
            'unavailable_fallback' => 'Unavailable in this build.',
            'fallback' => 'Safe fallback',
            'admission_condition' => 'Admission condition',
            'limitations' => 'Limitations',
            'owner' => 'Evolution owner',
            'example' => 'Example',
            'call' => 'Call',
            'parameters' => 'Parameters',
            'parameter_relationships' => 'Parameter relationships',
            'allowed_combinations' => 'Allowed combinations',
            'when' => 'when',
            'then' => 'then',
            'states' => 'States',
            'name' => 'Name',
            'type' => 'Type',
            'required' => 'Required',
            'default' => 'Default',
            'values' => 'Values',
            'rules' => 'Rules',
            'purpose' => 'Purpose',
            'yes' => 'Yes',
            'no' => 'No',
            'no_limitations' => 'No additional limitations are declared.',
            'limitations_and_source' => 'Limitations and source',
        ];
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

    /** @param list<mixed> $values @param array<string, mixed> $labels */
    private function presentedValues(array $values, array $labels): string
    {
        return implode(', ', array_map(
            function (mixed $value) use ($labels): string {
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
