<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Rendering\View\DeclarativeExampleDetailViewModel;
use Simai\Docara\Declarative\Rendering\View\DeclarativeExampleIndexItemViewModel;
use Simai\Docara\Declarative\Rendering\View\DeclarativeExampleIndexViewModel;
use Simai\Docara\Declarative\Rendering\View\DeclarativeExampleSourceViewModel;
use Simai\Docara\Framework\ComponentDirectiveDocument;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\I18n\Translator;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\ResolvedPagePlan;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortableDeclarativeExampleProjector
{
    private const MAX_SOURCE_BYTES = 131072;

    public function __construct(
        private TrustedTemplateRegistry $templates = new TrustedTemplateRegistry,
        private SchemaRepository $schemas = new SchemaRepository,
        private ?Translator $translator = null,
    ) {}

    public function exists(string $root): bool
    {
        return is_dir(rtrim($root, '/\\') . '/examples');
    }

    /**
     * @param  list<array<string, mixed>>  $authoredPages
     * @param  list<string>  $reservedDocumentIds
     * @return array{pages:list<array<string,mixed>>,receipt:array<string,mixed>}
     */
    public function project(
        string $root,
        array $authoredPages,
        FrameworkComponentRuntime $runtime,
        ResolvedPagePlan $basePlan,
        string $contentRoot,
        string $baseUrl,
        string $homeUrl,
        array $reservedDocumentIds = [],
    ): array {
        $descriptors = $this->descriptors($root);
        $pageBySource = [];
        foreach ($authoredPages as $page) {
            $source = $page['page_path'] ?? null;
            if (is_string($source)) {
                $pageBySource[$source] = $page;
            }
        }

        $locale = (string) (
            $basePlan->configuration['locale']
            ?? $basePlan->configuration['default_locale']
            ?? 'en'
        );
        $copy = $this->copy($locale);
        $tocDepth = (int) data_get($basePlan->configuration, 'reading.toc_depth', 3);
        $deploymentBase = $baseUrl === '/' ? '/' : '/' . trim($baseUrl, '/') . '/';
        $indexRoute = $deploymentBase . 'examples/';
        $brandTitle = (string) data_get($basePlan->configuration, 'branding.title', 'Docara');
        $breadcrumbs = [
            ['title' => $brandTitle, 'url' => $homeUrl],
            ['title' => $copy['title'], 'url' => null],
        ];

        $items = [];
        $details = [];
        $receiptPages = [];
        foreach ($descriptors as $record) {
            $descriptor = $record['descriptor'];
            $id = (string) $descriptor['id'];
            $resultSource = (string) $descriptor['result_page'];
            $resultPage = $pageBySource[$resultSource] ?? null;
            if (! is_array($resultPage)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_RESULT_PAGE_MISSING',
                    "Example [$id] references unknown result page [$resultSource].",
                );
            }
            if (($resultPage['navigation_hidden'] ?? false) !== true) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_RESULT_MUST_BE_HIDDEN',
                    "Example result page [$resultSource] must be hidden from navigation.",
                );
            }

            $sources = $this->sources($root, $record['path'], $descriptor);
            $route = $indexRoute . rawurlencode($id) . '/';
            $output = 'examples/' . $id . '/index.html';
            $category = $copy['categories'][(string) $descriptor['category']];
            $items[] = new DeclarativeExampleIndexItemViewModel(
                $this->escape((string) $descriptor['title']),
                $this->escape((string) $descriptor['description']),
                $this->escape($category),
                $this->escape($route),
            );
            $sourceViews = array_map(
                fn (array $source): DeclarativeExampleSourceViewModel => new DeclarativeExampleSourceViewModel(
                    $this->escape((string) $source['label']),
                    $this->escape((string) $source['path']),
                    $this->escape((string) $source['language']),
                    $this->escape((string) $source['bytes']),
                ),
                $sources,
            );
            $fragment = $this->templates->render('demonstrator.docara.detail', [
                'view' => new DeclarativeExampleDetailViewModel(
                    $this->escape((string) $descriptor['title']),
                    $this->escape((string) $descriptor['description']),
                    $this->escape($category),
                    $this->escape((string) $resultPage['url']),
                    $this->escape((string) $descriptor['preview']),
                    $sourceViews,
                    $this->escape($this->message($locale, 'examples.result')),
                    $this->escape($this->message($locale, 'examples.open_separately')),
                    $this->escape($this->message($locale, 'examples.result_frame', [
                        'title' => (string) $descriptor['title'],
                    ])),
                    $this->escape($this->message($locale, 'examples.sources')),
                    $this->escape($this->message($locale, 'examples.sources_description')),
                ),
            ]);
            $outline = (new PortableDocumentOutlineBuilder)->build(
                $fragment,
                $tocDepth,
                $reservedDocumentIds,
            );
            $components = $runtime->extract('', '@docara/declarative-examples/' . $id . '.md');
            $page = $this->page(
                basePlan: $basePlan,
                pagePath: $contentRoot . '/examples/' . $id . '.md',
                title: (string) $descriptor['title'],
                description: (string) $descriptor['description'],
                url: $route,
                output: $output,
                contentHtml: $outline['html'],
                components: $components,
                homeUrl: $homeUrl,
                navigationHidden: true,
                navigationOrder: null,
                sourceMarkdown: '# ' . (string) $descriptor['title'] . "\n",
                outline: $outline['items'],
            );
            $page['declarative_example_kind'] = 'detail';
            $page['declarative_example_id'] = $id;
            $page['declarative_example_index_url'] = $indexRoute;
            $page['declarative_example_breadcrumbs'] = [
                ...array_slice($breadcrumbs, 0, -1),
                ['title' => $copy['title'], 'url' => $indexRoute],
                ['title' => (string) $descriptor['title'], 'url' => null],
            ];
            $details[] = $page;
            $receiptPages[] = [
                'id' => $id,
                'category' => (string) $descriptor['category'],
                'descriptor' => $record['path'],
                'descriptor_sha256' => hash('sha256', $record['bytes']),
                'route' => $route,
                'output' => $output,
                'result_page' => $resultSource,
                'result_route' => (string) $resultPage['url'],
                'sources' => array_map(
                    static fn (array $source): array => [
                        'path' => $source['path'],
                        'sha256' => hash('sha256', $source['bytes']),
                    ],
                    $sources,
                ),
            ];
        }

        foreach ($details as $position => &$detail) {
            $detail['declarative_example_previous'] = $position === 0
                ? null
                : [
                    'title' => (string) $details[$position - 1]['title'],
                    'url' => (string) $details[$position - 1]['url'],
                ];
            $detail['declarative_example_next'] = $position === count($details) - 1
                ? null
                : [
                    'title' => (string) $details[$position + 1]['title'],
                    'url' => (string) $details[$position + 1]['url'],
                ];
        }
        unset($detail);

        $indexFragment = $this->templates->render('demonstrator.docara.index', [
            'view' => new DeclarativeExampleIndexViewModel(
                $this->escape($copy['title']),
                $this->escape($copy['intro']),
                $items,
                $this->escape($this->message($locale, 'examples.open')),
            ),
        ]);
        $indexOutline = (new PortableDocumentOutlineBuilder)->build(
            $indexFragment,
            $tocDepth,
            $reservedDocumentIds,
        );
        $index = $this->page(
            basePlan: $basePlan,
            pagePath: $contentRoot . '/examples/index.md',
            title: $copy['title'],
            description: $copy['description'],
            url: $indexRoute,
            output: 'examples/index.html',
            contentHtml: $indexOutline['html'],
            components: $runtime->extract('', '@docara/declarative-examples/index.md'),
            homeUrl: $homeUrl,
            navigationHidden: false,
            navigationOrder: 35,
            sourceMarkdown: '# ' . $copy['title'] . "\n",
            outline: $indexOutline['items'],
        );
        $index['declarative_example_kind'] = 'index';
        $index['declarative_example_index_url'] = $indexRoute;
        $index['declarative_example_breadcrumbs'] = $breadcrumbs;
        $index['declarative_example_previous'] = null;
        $index['declarative_example_next'] = null;

        $receiptCore = [
            'index' => ['route' => $indexRoute, 'output' => 'examples/index.html'],
            'pages' => $receiptPages,
        ];

        return [
            'pages' => [$index, ...$details],
            'receipt' => [
                'schema' => 'docara.declarative_examples.v1',
                'version' => 1,
                'content_sha256' => hash('sha256', CanonicalJson::encode($receiptCore)),
                ...$receiptCore,
            ],
        ];
    }

    /** @return list<array{path:string,bytes:string,descriptor:array<string,mixed>}> */
    private function descriptors(string $root): array
    {
        $examplesRoot = $this->directory($root, 'examples');
        $files = glob($examplesRoot . '/*.json') ?: [];
        sort($files, SORT_STRING);
        if ($files === []) {
            throw new PortableConfigurationException(
                'DECLARATIVE_EXAMPLES_EMPTY',
                'The examples directory contains no descriptors.',
            );
        }

        $records = [];
        $ids = [];
        foreach ($files as $file) {
            $relative = 'examples/' . basename($file);
            $safe = $this->file($root, $relative);
            try {
                $descriptor = json_decode($safe['bytes'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_JSON_INVALID',
                    "Example descriptor [$relative] is not valid JSON.",
                    $exception,
                );
            }
            $this->schemas->assertValid($descriptor, 'declarative-example.schema.json');
            $id = (string) $descriptor['id'];
            if (basename($relative, '.json') !== $id) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_ID_FILENAME_MISMATCH',
                    "Example [$id] must use descriptor filename [$id.json].",
                );
            }
            if (isset($ids[$id])) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_ID_DUPLICATE',
                    "Example id [$id] is duplicated.",
                );
            }
            $ids[$id] = true;
            $records[] = $safe + ['descriptor' => $descriptor];
        }
        usort($records, static function (array $left, array $right): int {
            $order = ((int) $left['descriptor']['order']) <=> ((int) $right['descriptor']['order']);

            return $order !== 0
                ? $order
                : strcmp((string) $left['descriptor']['id'], (string) $right['descriptor']['id']);
        });

        return $records;
    }

    /**
     * @param  array<string, mixed>  $descriptor
     * @return list<array{label:string,path:string,language:string,bytes:string}>
     */
    private function sources(string $root, string $descriptorPath, array $descriptor): array
    {
        $paths = [];
        $sources = [[
            'label' => 'Descriptor',
            'path' => $descriptorPath,
            'language' => 'json',
            'bytes' => $this->file($root, $descriptorPath)['bytes'],
        ]];
        $resultIncluded = false;
        foreach ($descriptor['sources'] as $source) {
            $path = (string) $source['path'];
            if (isset($paths[$path])) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_SOURCE_DUPLICATE',
                    "Example [{$descriptor['id']}] repeats source [$path].",
                );
            }
            $paths[$path] = true;
            $language = (string) $source['language'];
            if (($language === 'markdown' && ! str_ends_with($path, '.md'))
                || ($language === 'json' && ! str_ends_with($path, '.json'))
            ) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_SOURCE_LANGUAGE_MISMATCH',
                    "Example source [$path] does not match language [$language].",
                );
            }
            $safe = $this->file($root, $path);
            $sources[] = [
                'label' => (string) $source['label'],
                'path' => $path,
                'language' => $language,
                'bytes' => $safe['bytes'],
            ];
            $resultIncluded = $resultIncluded || $path === $descriptor['result_page'];
        }
        if (! $resultIncluded) {
            throw new PortableConfigurationException(
                'DECLARATIVE_EXAMPLE_RESULT_SOURCE_REQUIRED',
                "Example [{$descriptor['id']}] must expose its result Markdown source.",
            );
        }

        return $sources;
    }

    /** @return array{path:string,bytes:string} */
    private function file(string $root, string $relative): array
    {
        if ($relative === ''
            || str_starts_with($relative, '/')
            || str_contains($relative, '\\')
            || preg_match('#(?:^|/)\.\.(?:/|$)#', $relative) === 1
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_EXAMPLE_SOURCE_PATH_INVALID',
                "Example source path [$relative] is unsafe.",
            );
        }
        $rootReal = realpath($root);
        $candidate = rtrim($root, '/\\');
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.') {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_SOURCE_PATH_INVALID',
                    "Example source path [$relative] is unsafe.",
                );
            }
            $candidate .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($candidate)) {
                throw new PortableConfigurationException(
                    'DECLARATIVE_EXAMPLE_SOURCE_SYMLINK_FORBIDDEN',
                    "Example source [$relative] crosses a symbolic link.",
                );
            }
        }
        $real = realpath($candidate);
        $stat = @lstat($candidate);
        if ($rootReal === false
            || $real === false
            || ! is_array($stat)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ! str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_EXAMPLE_SOURCE_MISSING',
                "Example source [$relative] is missing or unsafe.",
            );
        }
        if (($stat['size'] ?? 0) > self::MAX_SOURCE_BYTES) {
            throw new PortableConfigurationException(
                'DECLARATIVE_EXAMPLE_SOURCE_TOO_LARGE',
                "Example source [$relative] exceeds the size limit.",
            );
        }

        return ['path' => $relative, 'bytes' => (string) file_get_contents($real)];
    }

    private function directory(string $root, string $relative): string
    {
        $rootReal = realpath($root);
        $candidate = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . $relative;
        $real = realpath($candidate);
        if ($rootReal === false
            || $real === false
            || is_link($candidate)
            || ! is_dir($real)
            || ! str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR)
        ) {
            throw new PortableConfigurationException(
                'DECLARATIVE_EXAMPLES_ROOT_INVALID',
                'The examples directory is missing or unsafe.',
            );
        }

        return $real;
    }

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
        bool $navigationHidden,
        ?int $navigationOrder,
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
        $configuration['navigation']['hidden'] = $navigationHidden;
        if ($navigationOrder !== null) {
            $configuration['navigation']['order'] = $navigationOrder;
        } else {
            unset($configuration['navigation']['order']);
        }
        $provenance = array_filter(
            $basePlan->provenance,
            static fn (string $pointer): bool => ! preg_match(
                '#^/(?:title|description|preset|navigation)(?:/|$)#',
                $pointer,
            ),
            ARRAY_FILTER_USE_KEY,
        );
        $provenance = array_replace($provenance, [
            '/title' => '@docara/declarative-examples',
            '/description' => '@docara/declarative-examples',
            '/preset' => '@docara/declarative-examples',
            '/navigation/hidden' => '@docara/declarative-examples',
        ]);
        if ($navigationOrder !== null) {
            $provenance['/navigation/order'] = '@docara/declarative-examples';
        }
        $trace = $basePlan->trace;
        $trace[] = [
            'role' => 'generated-content',
            'source' => '@docara/declarative-examples/' . basename($pagePath),
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
            'navigation_hidden' => $navigationHidden,
            'navigation_order' => $navigationOrder,
            'search_enabled' => (bool) data_get($configuration, 'search.enabled', false),
            'search_indexed' => true,
            'reading_breadcrumbs' => (bool) data_get($configuration, 'reading.breadcrumbs', true),
            'reading_toc' => (bool) data_get($configuration, 'reading.toc', true),
            'reading_previous_next' => (bool) data_get($configuration, 'reading.previous_next', true),
            'outline' => $outline,
            'url' => $url,
            'output' => $output,
            'home_url' => $homeUrl,
            'content_html' => $contentHtml,
            'components' => $components,
            'component_calls' => $components->normalizedCalls,
        ];
    }

    /** @return array<string, mixed> */
    private function copy(string $locale): array
    {
        return [
            'title' => $this->message($locale, 'examples.title'),
            'description' => $this->message($locale, 'examples.description'),
            'intro' => $this->message($locale, 'examples.intro'),
            'categories' => [
                'regions' => $this->message($locale, 'examples.category_regions'),
                'inheritance' => $this->message($locale, 'examples.category_inheritance'),
                'presets' => $this->message($locale, 'examples.category_presets'),
                'smart' => $this->message($locale, 'examples.category_smart'),
            ],
        ];
    }

    /** @param array<string, scalar> $parameters */
    private function message(string $locale, string $id, array $parameters = []): string
    {
        if (! $this->translator instanceof Translator) {
            throw new PortableConfigurationException(
                'DECLARATIVE_EXAMPLE_TRANSLATOR_REQUIRED',
                'Declarative examples require a resolved language-pack translator.',
            );
        }

        return $this->translator->message($locale, $id, $parameters);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
