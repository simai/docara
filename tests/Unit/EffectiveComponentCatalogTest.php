<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogValidator;
use Simai\Docara\ComponentCatalog\TypedComponentDefinitionRepository;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Document\SourceSpan;
use Simai\Docara\Declarative\Rendering\SmartRenderer;
use Simai\Docara\Declarative\Rendering\TrustedTemplateRegistry;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\DocumentRenderContext;
use Simai\Docara\Document\DocumentRendererRegistry;
use Simai\Docara\Document\MarkdownCompiler;
use Simai\Docara\Document\SmartComponentNode;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkConsumerPolicy;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownProfile;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class EffectiveComponentCatalogTest extends TestCase
{
    #[Test]
    public function typed_definitions_are_the_single_sorted_directive_source(): void
    {
        $repository = TypedComponentDefinitionRepository::bundled();

        self::assertSame([
            'alert',
            'atlas_index',
            'backlinks',
            'banner',
            'card',
            'code',
            'columns',
            'component_index',
            'cta',
            'details',
            'diagram',
            'download',
            'embed',
            'example',
            'features',
            'figure',
            'grid',
            'hero',
            'html',
            'internal_preview',
            'logos',
            'math',
            'media',
            'promo',
            'schema_reference',
            'showcase',
            'steps',
            'surface',
            'tabs',
            'tree',
        ], $repository->names());
        self::assertSame([
            'docara.alert',
            'docara.atlas_index',
            'docara.backlinks',
            'docara.banner',
            'docara.card',
            'docara.code',
            'docara.columns',
            'docara.component_index',
            'docara.cta',
            'docara.details',
            'docara.diagram',
            'docara.download',
            'docara.embed',
            'docara.example',
            'docara.features',
            'docara.figure',
            'docara.grid',
            'docara.hero',
            'docara.html',
            'docara.internal_preview',
            'docara.logos',
            'docara.math',
            'docara.media',
            'docara.promo',
            'docara.schema_reference',
            'docara.showcase',
            'docara.steps',
            'docara.surface',
            'docara.tabs',
            'docara.tree',
        ], array_column($repository->all(), 'id'));
        self::assertSame('docara.card.v1', $repository->byName('card')['renderer']);
        self::assertSame('docara.columns.v1', $repository->byName('columns')['renderer']);
        self::assertNull($repository->findByName('panel'));
        self::assertFileDoesNotExist(
            dirname(__DIR__, 2) . '/resources/component-catalog/requirements/docara.columns.json',
            'A promoted component must have exactly one typed owner record.',
        );
    }

    #[Test]
    public function native_profile_owns_both_enabled_extensions_and_capability_ids(): void
    {
        $profile = PortableMarkdownProfile::bundled();

        self::assertSame([
            'native.code',
            'native.footnotes_and_sources',
            'native.headings_and_text',
            'native.links_and_images',
            'native.lists_and_quotes',
            'native.table',
        ], array_column($profile->entries(), 'id'));

        $environment = $profile->environment();
        self::assertNotNull($environment);
    }

    #[Test]
    public function effective_projection_is_deterministic_sorted_and_honest(): void
    {
        $first = $this->builder()->build();
        $second = $this->builder()->build();

        self::assertSame(CanonicalJson::encodePretty($first), CanonicalJson::encodePretty($second));
        self::assertSame('docara.effective_component_catalog.v1', $first['schema']);
        self::assertSame(1, $first['version']);
        self::assertSame('sf-v5.6.1-34f5ff45-23d00d92', $first['framework_pair']);
        self::assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $first['provider_revision']);
        self::assertSame([
            'catalog_is_canonical_framework_registry' => false,
            'all_framework_components_supported' => false,
            'production_ready' => false,
            'public_release_ready' => false,
        ], $first['nonclaims']);
        self::assertSame(
            hash('sha256', CanonicalJson::encode($first['entries'])),
            $first['content_sha256'],
        );

        $ids = array_column($first['entries'], 'id');
        $sorted = $ids;
        sort($sorted, SORT_STRING);
        self::assertSame($sorted, $ids);
        self::assertSame($ids, array_values(array_unique($ids)));

        $lifecycles = array_column($first['entries'], 'lifecycle', 'id');
        foreach ([
            'native.code',
            'native.footnotes_and_sources',
            'native.headings_and_text',
            'native.links_and_images',
            'native.lists_and_quotes',
            'native.table',
            'docara.card',
            'docara.banner',
            'docara.diagram',
            'docara.hero',
            'docara.html',
            'docara.logos',
            'docara.math',
            'docara.steps',
            'docara.tabs',
            'ui.alert',
            'ui.button',
        ] as $id) {
            self::assertSame('supported', $lifecycles[$id] ?? null, $id);
        }
        foreach ($first['entries'] as $entry) {
            foreach (['title', 'description', 'presentation', 'limitations', 'example_ref'] as $localizedKey) {
                self::assertArrayNotHasKey($localizedKey, $entry, (string) $entry['id']);
            }
            if ($entry['lifecycle'] === 'supported') {
                self::assertIsArray($entry['metadata'] ?? null, (string) $entry['id']);
                self::assertNotSame('', trim((string) ($entry['metadata']['owner'] ?? '')), (string) $entry['id']);
                self::assertNotSame('', trim((string) ($entry['metadata']['package'] ?? '')), (string) $entry['id']);
                self::assertNotSame('', trim((string) ($entry['metadata']['version'] ?? '')), (string) $entry['id']);
                self::assertIsString($entry['metadata']['source_ref'] ?? null, (string) $entry['id']);
                self::assertSame(
                    array_values($entry['authoring']['jobs'] ?? []),
                    $entry['metadata']['capabilities'] ?? null,
                    (string) $entry['id'],
                );
                self::assertArrayNotHasKey('gap', $entry, (string) $entry['id']);
            } else {
                self::assertIsArray($entry['gap'] ?? null, (string) $entry['id']);
            }
        }
        self::assertSame('admission_pending', $lifecycles['ui.badge'] ?? null);
        self::assertSame('framework_gap', $lifecycles['content.icon'] ?? null);
        self::assertSame('framework_gap', $lifecycles['native.code.enhanced'] ?? null);
        self::assertSame('framework_gap', $lifecycles['ui.tabs'] ?? null);
        self::assertSame('deferred', $lifecycles['ui.dataview'] ?? null);

        $alert = $first['entries'][array_search('ui.alert', $ids, true)];
        self::assertSame('larena/ui', $alert['metadata']['owner']);
        self::assertSame('larena/ui', $alert['metadata']['package']);
        self::assertSame('1.0.1', $alert['metadata']['version']);
        self::assertSame(
            'resources/framework/manifests/ui-alert.json',
            $alert['metadata']['source_ref'],
        );
        self::assertSame('framework_smart', $alert['family']);
        self::assertSame(':::ui.alert', $alert['authoring']['call']);
        self::assertSame(['default', 'info', 'warning', 'danger'], $alert['states']);
        self::assertSame(['closable', 'success'], $alert['consumer_policy']['excluded_states']);
        $alertParameters = array_column($alert['authoring']['parameters'], null, 'name');
        self::assertSame(
            ['clear', 'info', 'danger', 'warning'],
            $alertParameters['type']['values'],
        );
        self::assertSame(
            'resources/framework/manifests/ui-alert.json',
            $alert['provenance']['manifest_ref'],
        );
        $button = $this->entry($first, 'ui.button');
        self::assertSame('larena/ui', $button['metadata']['package']);
        self::assertSame('1.0.0', $button['metadata']['version']);
        self::assertSame(
            'resources/framework/manifests/ui-button.json',
            $button['metadata']['source_ref'],
        );
        self::assertSame('simai/docara', $this->entry($first, 'native.code')['metadata']['package']);
        self::assertSame('simai/docara', $this->entry($first, 'docara.card')['metadata']['package']);
        $presetIndex = array_search(
            'preset',
            array_column($button['authoring']['parameters'], 'name'),
            true,
        );
        self::assertIsInt($presetIndex);
        $preset = $button['authoring']['parameters'][$presetIndex];
        self::assertSame('enum', $preset['type']);
        self::assertSame([
            'link',
            'link_on_surface',
            'on_surface',
            'outline',
            'outline_on_surface',
            'primary',
            'secondary',
            'tonal_on_surface',
        ], $preset['values']);
        foreach ($button['authoring']['parameters'] as $parameter) {
            self::assertFalse(
                $parameter['required'],
                "{$parameter['name']} is defaulted by the bounded runtime and is not required author input.",
            );
        }
        $buttonParameters = array_column($button['authoring']['parameters'], null, 'name');
        self::assertSame('Save', $buttonParameters['text']['default']);
        self::assertFalse($buttonParameters['loading']['default']);
        self::assertSame([
            'min_length' => 1,
            'max_length' => 120,
            'pattern' => '\S',
        ], $buttonParameters['text']['validation']);
        self::assertSame(['aria-label'], $buttonParameters['text']['mirrors']);
        $alertParameters = array_column($alert['authoring']['parameters'], null, 'name');
        self::assertSame([
            'min_length' => 1,
            'max_length' => 160,
            'pattern' => '\S',
        ], $alertParameters['title']['validation']);
        self::assertSame(['aria-label'], $alertParameters['title']['mirrors']);
        self::assertSame([
            'min_length' => 1,
            'max_length' => 500,
            'pattern' => '\S',
        ], $alertParameters['supporting-text']['validation']);
        self::assertSame([
            'allowed_combinations' => [[
                'keys' => ['type', 'scheme'],
                'values' => [
                    ['default', 'primary'],
                    ['default', 'on-surface'],
                    ['tonal', 'secondary'],
                    ['tonal', 'on-surface'],
                    ['outline', 'primary'],
                    ['outline', 'on-surface'],
                    ['link', 'primary'],
                    ['link', 'on-surface'],
                ],
            ]],
            'requires' => [[
                'when' => ['loading' => true],
                'then' => ['disabled' => true],
            ]],
        ], $button['authoring']['constraints']);
        self::assertSame([
            'allowed_combinations' => [],
            'requires' => [],
        ], $alert['authoring']['constraints']);

        $minimalAuthorProps = [];
        foreach ($button['authoring']['parameters'] as $parameter) {
            if ($parameter['required']) {
                $minimalAuthorProps[$parameter['name']] = $parameter['default'] ?? null;
            }
        }
        self::assertSame([], $minimalAuthorProps);
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json')->toArray();
        $minimalPlan = SmartComponentGateway::bundled($lock)->resolve(new SmartCallNode(
            'catalog-minimal-button',
            'ui.button',
            'default',
            [],
            1,
            new SourceSpan('catalog-minimal-button.md', 1, 3),
        ));
        self::assertSame('Save', $minimalPlan->props['text']);

        $panel = array_values(array_filter(
            $first['entries'],
            static fn (array $entry): bool => ($entry['id'] ?? null) === 'docara.panel',
        ));
        self::assertSame([], $panel);
        self::assertSame('neutral_panel', $this->entry($first, 'docara.card')['authoring']['jobs'][1]);
    }

    #[Test]
    public function supported_entries_have_evidence_and_all_paths_are_safe_relative_paths(): void
    {
        $catalog = $this->builder()->build();

        foreach ($catalog['entries'] as $entry) {
            self::assertDoesNotMatchRegularExpression(
                '~(?:^|[/@])(?:main|master|latest)(?:$|[/])~i',
                CanonicalJson::encode($entry),
            );
            foreach (['docs_ref'] as $pathKey) {
                if (! isset($entry[$pathKey])) {
                    continue;
                }
                self::assertIsString($entry[$pathKey]);
                self::assertStringNotContainsString('/Users/', $entry[$pathKey]);
                self::assertFalse(str_starts_with($entry[$pathKey], '/'));
                self::assertStringNotContainsString('../', $entry[$pathKey]);
            }

            if ($entry['lifecycle'] === 'supported') {
                self::assertTrue($entry['verification']['renderer']);
                self::assertTrue($entry['verification']['tests']);
                self::assertTrue($entry['verification']['docs']);
                self::assertTrue($entry['verification']['demo']);
                self::assertFileExists(
                    dirname(__DIR__, 2) . '/resources/component-catalog/examples/' . $entry['id'] . '.md',
                );
            } else {
                self::assertFalse($entry['verification']['demo']);
                self::assertNotSame('', $entry['gap']['owner'] ?? '');
            }
        }
    }

    #[Test]
    public function every_entry_points_to_its_exact_authored_documentation_owner(): void
    {
        $catalog = $this->builder()->build();
        $actual = array_column($catalog['entries'], 'docs_ref', 'id');
        $expected = [
            'content.icon' => 'docs/site/content/ru/start/component-model.md',
            'docara.alert' => 'docs/site/content/ru/start/component-model.md',
            'docara.backlinks' => 'docs/site/content/ru/components/backlinks.md',
            'docara.badge' => 'docs/site/content/ru/start/component-model.md',
            'docara.banner' => 'docs/site/content/ru/components/banner.md',
            'docara.button' => 'docs/site/content/ru/components/button.md',
            'docara.card' => 'docs/site/content/ru/components/card.md',
            'docara.code' => 'docs/site/content/ru/components/code-from-file.md',
            'docara.details' => 'docs/site/content/ru/components/details.md',
            'docara.diagram' => 'docs/site/content/ru/components/diagram.md',
            'docara.download' => 'docs/site/content/ru/components/download.md',
            'docara.embed' => 'docs/site/content/ru/components/embed.md',
            'docara.example' => 'docs/site/content/ru/components/example.md',
            'docara.figure' => 'docs/site/content/ru/components/figure.md',
            'docara.grid' => 'docs/site/content/ru/components/grid.md',
            'docara.hero' => 'docs/site/content/ru/components/hero.md',
            'docara.html' => 'docs/site/content/ru/components/html.md',
            'docara.icon' => 'docs/site/content/ru/components/icon.md',
            'docara.kbd' => 'docs/site/content/ru/components/kbd.md',
            'docara.logos' => 'docs/site/content/ru/components/logos.md',
            'docara.math' => 'docs/site/content/ru/components/math.md',
            'docara.media' => 'docs/site/content/ru/components/media.md',
            'docara.steps' => 'docs/site/content/ru/components/steps.md',
            'docara.surface' => 'docs/site/content/ru/components/surface.md',
            'docara.tabs' => 'docs/site/content/ru/components/tabs.md',
            'docara.tree' => 'docs/site/content/ru/components/tree.md',
            'native.code' => 'docs/site/content/ru/authoring/markdown.md',
            'native.code.enhanced' => 'docs/site/content/ru/start/component-model.md',
            'native.footnotes_and_sources' => 'docs/site/content/ru/authoring/markdown.md',
            'native.headings_and_text' => 'docs/site/content/ru/authoring/markdown.md',
            'native.links_and_images' => 'docs/site/content/ru/authoring/markdown.md',
            'native.lists_and_quotes' => 'docs/site/content/ru/authoring/markdown.md',
            'native.table' => 'docs/site/content/ru/authoring/markdown.md',
            'ui.alert' => 'docs/site/content/ru/start/component-model.md',
            'ui.badge' => 'docs/site/content/ru/start/component-model.md',
            'ui.button' => 'docs/site/content/ru/start/component-model.md',
            'ui.dataview' => 'docs/site/content/ru/start/component-model.md',
            'ui.tabs' => 'docs/site/content/ru/start/component-model.md',
        ];

        self::assertSame($expected, $actual);
        foreach ($actual as $id => $docsReference) {
            self::assertFileExists(dirname(__DIR__, 2) . '/' . $docsReference, $id);
            self::assertStringStartsWith(
                'docs/site/content/',
                $docsReference,
                "$id must reference authored guidance, not generated output.",
            );
        }
    }

    #[Test]
    public function semantic_validator_rejects_duplicate_order_evidence_gap_path_and_overclaim_regressions(): void
    {
        $catalog = $this->builder()->build();
        $cases = [];

        $duplicate = $catalog;
        $duplicate['entries'][] = $duplicate['entries'][0];
        $cases[] = [$duplicate, 'COMPONENT_CATALOG_DUPLICATE_ID'];

        $unordered = $catalog;
        [$unordered['entries'][0], $unordered['entries'][1]] = [$unordered['entries'][1], $unordered['entries'][0]];
        $cases[] = [$unordered, 'COMPONENT_CATALOG_ORDER_INVALID'];

        $missingEvidence = $catalog;
        $supportedIndex = array_search('native.code', array_column($missingEvidence['entries'], 'id'), true);
        $missingEvidence['entries'][$supportedIndex]['verification']['tests'] = false;
        $cases[] = [$missingEvidence, 'COMPONENT_CATALOG_SUPPORTED_EVIDENCE_REQUIRED'];

        $missingDemo = $catalog;
        $missingDemo['entries'][$supportedIndex]['verification']['demo'] = false;
        $cases[] = [$missingDemo, 'COMPONENT_CATALOG_SUPPORTED_EVIDENCE_REQUIRED'];

        $missingGap = $catalog;
        $gapIndex = array_search('content.icon', array_column($missingGap['entries'], 'id'), true);
        unset($missingGap['entries'][$gapIndex]['gap']['owner']);
        $cases[] = [$missingGap, 'COMPONENT_CATALOG_GAP_OWNER_REQUIRED'];

        $unsupportedDemo = $catalog;
        $unsupportedDemo['entries'][$gapIndex]['verification']['demo'] = true;
        $cases[] = [$unsupportedDemo, 'COMPONENT_CATALOG_UNSUPPORTED_DEMO_FORBIDDEN'];

        $unsafePath = $catalog;
        $unsafePath['entries'][0]['docs_ref'] = '/Users/example/private.md';
        $cases[] = [$unsafePath, 'COMPONENT_CATALOG_PATH_INVALID'];

        $unsafeText = $catalog;
        $unsafeText['entries'][0]['provenance']['framework_owner'] = '/Users/example/private.md';
        $cases[] = [$unsafeText, 'COMPONENT_CATALOG_PROVENANCE_UNSAFE'];

        $overclaim = $catalog;
        $overclaim['nonclaims']['production_ready'] = true;
        $cases[] = [$overclaim, 'COMPONENT_CATALOG_OVERCLAIM_FORBIDDEN'];

        $nativeFamily = $catalog;
        $nativeIndex = array_search('native.code', array_column($nativeFamily['entries'], 'id'), true);
        $nativeFamily['entries'][$nativeIndex]['family'] = 'requirement';
        $nativeFamily['content_sha256'] = hash('sha256', CanonicalJson::encode($nativeFamily['entries']));
        $cases[] = [$nativeFamily, 'COMPONENT_CATALOG_FAMILY_CONTRACT_INVALID'];

        $smartProvenance = $catalog;
        $smartIndex = array_search('ui.alert', array_column($smartProvenance['entries'], 'id'), true);
        $smartProvenance['entries'][$smartIndex]['provenance']['runtime_pair'] = 'tampered-pair';
        $smartProvenance['content_sha256'] = hash('sha256', CanonicalJson::encode($smartProvenance['entries']));
        $cases[] = [$smartProvenance, 'COMPONENT_CATALOG_SMART_PROVENANCE_MISMATCH'];

        $smartPolicy = $catalog;
        $smartPolicy['entries'][$smartIndex]['consumer_policy']['managed_properties'] = [];
        $smartPolicy['content_sha256'] = hash('sha256', CanonicalJson::encode($smartPolicy['entries']));
        $cases[] = [$smartPolicy, 'COMPONENT_CATALOG_SMART_POLICY_MISMATCH'];

        foreach ($cases as [$invalid, $expected]) {
            try {
                (new EffectiveComponentCatalogValidator)->assertValid($invalid);
                self::fail("Invalid effective catalogue unexpectedly passed [$expected].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame($expected, $exception->errorCode);
            }
        }
    }

    #[Test]
    public function smart_admission_requires_complete_projected_assets_and_an_explicit_consumer_policy(): void
    {
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        unset($lock['asset_projection']['files']['smart/buttons/js/buttons.js']);

        try {
            EffectiveComponentCatalogBuilder::bundled(FrameworkLock::fromArray($lock))->build();
            self::fail('A Smart component with a missing projected asset unexpectedly passed admission.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_ASSET_NOT_PROJECTED', $exception->errorCode);
        }

        try {
            (new FrameworkConsumerPolicy)->catalogMetadata('ui.badge');
            self::fail('An unreviewed Smart component unexpectedly received an empty consumer policy.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_CONSUMER_POLICY_MISSING', $exception->errorCode);
        }
    }

    #[Test]
    public function smart_consumer_policy_states_and_blocked_values_are_exact_manifest_narrowing(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode(
            (string) file_get_contents($root . '/resources/framework/manifests/ui-alert.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $policy = new FrameworkConsumerPolicy;

        self::assertSame(
            ['default', 'info', 'warning', 'danger'],
            $policy->admittedStates('ui.alert', $manifest['atlas']['states']),
        );

        $manifest['props']['properties']['closable']['enum'] = [false];
        try {
            $policy->assertNarrowing('ui.alert', $manifest);
            self::fail('An inert blocked policy value unexpectedly passed the exact property schema.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_CONSUMER_POLICY_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function every_supported_entry_has_one_executable_example_fixture(): void
    {
        $root = dirname(__DIR__, 2);
        $catalog = $this->builder()->build();
        $lock = FrameworkLock::fromJsonFile($root . '/docs/site/simai-framework.lock.json')->toArray();
        $gateway = SmartComponentGateway::bundled($lock);
        $renderer = new PortableMarkdownRenderer(components: $gateway);
        $documentRenderers = DocumentRendererRegistry::bundled(
            $renderer,
            new SmartRenderer(new TrustedTemplateRegistry),
        );
        $nativeIdentity = [
            'native.code' => [
                'markdown' => ['```php', "\$site = 'Docara';"],
                'html' => [
                    '<div data-docara-code-block class="source init docara-code-block min-w-0 overflow-hidden bg-surface-container border border-outline-variant radius-2 m-bottom-1">',
                    '<pre class="docara-code-scroll overflow-auto m-0 p-2"><code class="language-php">',
                    "\$site = 'Docara';",
                ],
            ],
            'native.footnotes_and_sources' => [
                'markdown' => [
                    'verifiable source reference.[^source]',
                    '[^source]: Source title, version, and publication address.',
                ],
                'html' => [
                    'class="footnote-ref"',
                    'class="footnotes"',
                    'Source title, version, and publication address.',
                ],
            ],
            'native.headings_and_text' => [
                'markdown' => [
                    '## Clear heading',
                    '**important text**',
                    '*emphasis*',
                    '~~outdated wording~~',
                ],
                'html' => [
                    '<h2>Clear heading</h2>',
                    '<strong>important text</strong>',
                    '<em>emphasis</em>',
                    '<del>outdated wording</del>',
                ],
            ],
            'native.links_and_images' => [
                'markdown' => [
                    '[Back to the catalog](../)',
                    '![Docara mark](../../_docara/component-catalog/docara-mark.svg)',
                ],
                'html' => [
                    '<a href="../">Back to the catalog</a>',
                    '<img src="../../_docara/component-catalog/docara-mark.svg" alt="Docara mark" />',
                    'data-docara-native-image',
                    'aspect-16x9',
                ],
            ],
            'native.lists_and_quotes' => [
                'markdown' => [
                    '- First item',
                    '> Good documentation helps people complete a task.',
                ],
                'html' => [
                    '<li>First item</li>',
                    '<blockquote>',
                    'Good documentation helps people complete a task.',
                    'data-docara-native-quote',
                ],
            ],
            'native.table' => [
                'markdown' => [
                    '| File | Role |',
                    '| `docara.json` | Site settings |',
                ],
                'html' => [
                    '<div data-docara-table-scroll class="overflow-auto m-bottom-1"><table class="table table-border table-stripe">',
                    '<th>File</th>',
                    '<code>docara.json</code>',
                ],
            ],
        ];
        $typedIdentity = [
            'docara.alert' => [
                '<section data-docara-block="alert"',
                'class="sf-alert sf-alert--info sf-alert--default flex items-cross-start m-bottom-1"',
            ],
            'docara.card' => [
                '<section data-docara-block="card" data-docara-card-variant="default" class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1 m-bottom-1">',
                '<h3>Portable project</h3>',
            ],
            'docara.banner' => [
                '<aside data-docara-block="banner"',
                'sf-alert--flat',
            ],
            'docara.backlinks' => [
                '<nav data-docara-block="backlinks"',
                'data-docara-backlinks-limit=',
            ],
            'docara.code' => [
                '<div data-docara-code-block',
                'language-php',
            ],
            'docara.diagram' => [
                '<figure data-docara-block="diagram"',
                'data-docara-diagram-source',
            ],
            'docara.details' => [
                '<details data-docara-block="details"',
                '<summary class="flex items-cross-center gap-1 cursor-pointer',
            ],
            'docara.download' => [
                '<section data-docara-block="download" data-action="download"',
                'docara-portable.zip',
            ],
            'docara.embed' => [
                '<div data-docara-block="embed"',
                '<iframe',
            ],
            'docara.example' => [
                'data-docara-example=',
                'data-docara-example-tab="markdown"',
            ],
            'docara.html' => [
                '<iframe data-docara-block="html"',
                'sandbox srcdoc=',
            ],
            'docara.figure' => [
                '<figure data-docara-block="figure"',
                '<figcaption',
            ],
            'docara.grid' => [
                '<section data-docara-block="grid"',
                'lg:grid-col-3',
            ],
            'docara.hero' => [
                '<section data-docara-block="hero" data-variant="split" data-docara-width="full"',
                '<a data-docara-hero-action class="sf-button',
            ],
            'docara.logos' => [
                '<ul data-docara-block="logos" data-tone="normal" class="grid grid-col-2 md:grid-col-3 lg:grid-col-6',
                '<li class="min-w-0 flex items-cross-center content-main-center">',
            ],
            'docara.media' => [
                '<section data-docara-block="media"',
                'data-side="right"',
            ],
            'docara.math' => [
                'data-docara-block="math"',
                'role="math"',
            ],
            'docara.steps' => [
                '<section data-docara-block="steps" data-view="timeline" class="m-bottom-1">',
                '<ol class="m-0 p-0">',
            ],
            'docara.surface' => [
                '<section data-docara-block="surface" data-docara-surface',
                'data-docara-content-width="container"',
            ],
            'docara.tabs' => [
                '<section data-docara-block="tabs"',
                'role="tablist"',
            ],
            'docara.tree' => [
                '<div data-docara-block="tree"',
                '<ul',
            ],
        ];
        $smartIdentity = [
            'ui.alert' => '<sf-alert',
            'ui.button' => '<sf-button',
        ];
        $inlineIdentity = [
            'docara.badge' => '<span class="sf-badge ',
            'docara.button' => '<a class="sf-button ',
            'docara.icon' => '<i class="sf-icon ',
            'docara.kbd' => '<kbd class="inline-flex ',
        ];
        $supported = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => ($entry['lifecycle'] ?? null) === 'supported',
        ));
        self::assertCount(33, $supported);
        $expectedIds = array_merge(
            array_keys($nativeIdentity),
            array_keys($typedIdentity),
            array_keys($inlineIdentity),
            array_keys($smartIdentity),
        );
        sort($expectedIds, SORT_STRING);
        self::assertSame($expectedIds, array_column($supported, 'id'));
        $englishExamples = array_map(
            static fn (array $entry): string => 'resources/component-catalog/examples/' . $entry['id'] . '.md',
            $supported,
        );
        self::assertCount(
            count($supported),
            array_unique($englishExamples),
            'Every supported catalogue entry must own a distinct executable fixture.',
        );

        foreach ($supported as $entry) {
            $englishReference = 'resources/component-catalog/examples/' . $entry['id'] . '.md';
            self::assertSame(
                'resources/component-catalog/examples/' . $entry['id'] . '.md',
                $englishReference,
                $entry['id'],
            );
            $markdown = file_get_contents($root . '/' . $englishReference);
            self::assertIsString($markdown, $entry['id']);
            $document = (new MarkdownCompiler)->compile($markdown, $englishReference);
            $smartNodes = array_values(array_filter(
                $document->allNodes(),
                static fn ($node): bool => $node instanceof SmartComponentNode,
            ));
            $html = $entry['family'] === 'framework_smart'
                ? $documentRenderers->render(
                    $document,
                    new DocumentRenderContext($root, $root . '/' . $englishReference),
                )['document']->html
                : $renderer->render($markdown, $root, $root . '/' . $englishReference);
            self::assertNotSame('', trim($html), $entry['id']);
            if (in_array($entry['id'], [
                'docara.alert',
                'docara.backlinks',
                'docara.banner',
                'docara.button',
                'docara.card',
                'docara.code',
                'docara.details',
                'docara.download',
                'docara.embed',
                'docara.example',
                'docara.diagram',
                'docara.figure',
                'docara.grid',
                'docara.icon',
                'docara.kbd',
                'docara.hero',
                'docara.html',
                'docara.logos',
                'docara.math',
                'docara.media',
                'docara.steps',
                'docara.tabs',
                'docara.tree',
                'native.code',
                'native.footnotes_and_sources',
                'native.headings_and_text',
                'native.links_and_images',
                'native.lists_and_quotes',
                'native.table',
            ], true)) {
                $slug = match ($entry['id']) {
                    'docara.backlinks' => 'backlinks',
                    'docara.banner' => 'banner',
                    'docara.button' => 'button',
                    'docara.card' => 'card',
                    'docara.code' => 'code-from-file',
                    'docara.details' => 'details',
                    'docara.download' => 'download',
                    'docara.embed' => 'embed',
                    'docara.example' => 'example',
                    'docara.diagram' => 'diagram',
                    'docara.figure' => 'figure',
                    'docara.grid' => 'grid',
                    'docara.icon' => 'icon',
                    'docara.kbd' => 'kbd',
                    'docara.hero' => 'hero',
                    'docara.html' => 'html',
                    'docara.logos' => 'logos',
                    'docara.math' => 'math',
                    'docara.media' => 'media',
                    'docara.steps' => 'steps',
                    'docara.surface' => 'surface',
                    'docara.tabs' => 'tabs',
                    'docara.tree' => 'tree',
                    'native.code' => 'code',
                    'native.footnotes_and_sources' => 'footnotes-and-sources',
                    'native.headings_and_text' => 'headings-and-text',
                    'native.links_and_images' => 'links-and-images',
                    'native.lists_and_quotes' => 'lists-and-quotes',
                    'native.table' => 'table',
                    default => 'alert',
                };
                self::assertFileExists($root . "/docs/site/content/ru/components/$slug.md");

                continue;
            }

            if ($entry['family'] === 'native_markdown') {
                self::assertSame([], $smartNodes, $entry['id']);
                self::assertArrayHasKey($entry['id'], $nativeIdentity);
                foreach ($nativeIdentity[$entry['id']]['markdown'] as $marker) {
                    self::assertStringContainsString($marker, $markdown, $entry['id']);
                }
                foreach ($nativeIdentity[$entry['id']]['html'] as $marker) {
                    self::assertStringContainsString($marker, $html, $entry['id']);
                }

                continue;
            }

            $call = $entry['authoring']['call'];
            self::assertIsString($call, $entry['id']);
            if (($entry['authoring']['syntax'] ?? null) === 'inline') {
                self::assertSame([], $smartNodes, $entry['id']);
                self::assertArrayHasKey($entry['id'], $inlineIdentity);
                self::assertStringContainsString($call . '[', $markdown, $entry['id']);
                self::assertStringContainsString($inlineIdentity[$entry['id']], $html, $entry['id']);

                continue;
            }
            $directivePattern = str_starts_with($call, ':::')
                ? ':{3,}' . preg_quote(substr($call, 3), '/')
                : preg_quote($call, '/');
            $directiveCount = preg_match_all(
                '/^' . $directivePattern . '(?:\h+\{[^}]*\})?\h*$/m',
                $markdown,
            );
            self::assertGreaterThanOrEqual(
                1,
                $directiveCount,
                "{$entry['id']} fixture must contain its exact directive opener.",
            );

            if ($entry['family'] === 'docara_typed') {
                self::assertSame([], $smartNodes, $entry['id']);
                self::assertArrayHasKey($entry['id'], $typedIdentity);
                foreach ($typedIdentity[$entry['id']] as $marker) {
                    self::assertStringContainsString($marker, $html, $entry['id']);
                }

                continue;
            }

            self::assertSame('framework_smart', $entry['family'], $entry['id']);
            self::assertArrayHasKey($entry['id'], $smartIdentity);
            self::assertCount($directiveCount, $smartNodes, $entry['id']);
            foreach ($smartNodes as $smartNode) {
                self::assertSame($entry['id'], $smartNode->smart);
            }
            self::assertSame(
                $directiveCount,
                substr_count($html, $smartIdentity[$entry['id']]),
                "{$entry['id']} fixture must render every declared Smart example.",
            );
            foreach (array_values($smartIdentity) as $host) {
                if ($host !== $smartIdentity[$entry['id']]) {
                    self::assertStringNotContainsString($host, $html, $entry['id']);
                }
            }
        }
    }

    #[Test]
    public function duplicate_smart_metadata_fails_before_it_can_overwrite_an_owner_record(): void
    {
        $root = dirname(__DIR__, 2);
        $temporary = sys_get_temp_dir() . '/docara-smart-' . bin2hex(random_bytes(8));
        $smartDirectory = $temporary . '/resources/component-catalog/smart';
        self::assertTrue(mkdir($smartDirectory, 0700, true));
        foreach (glob($root . '/resources/component-catalog/smart/*.json') ?: [] as $path) {
            copy($path, $smartDirectory . '/' . basename($path));
        }
        $lock = FrameworkLock::fromJsonFile($root . '/docs/site/simai-framework.lock.json');
        $makeBuilder = static fn (): EffectiveComponentCatalogBuilder => new EffectiveComponentCatalogBuilder(
            $temporary,
            PortableMarkdownProfile::bundled(),
            TypedComponentDefinitionRepository::bundled(),
            $lock,
            FrameworkManifestRepository::bundled($lock),
            new FrameworkConsumerPolicy,
        );

        try {
            $alertPath = $smartDirectory . '/ui.alert.json';
            $alert = json_decode((string) file_get_contents($alertPath), true, flags: JSON_THROW_ON_ERROR);
            $alert['authoring']['call'] = ':::ui.wrong';
            file_put_contents($alertPath, json_encode($alert, JSON_THROW_ON_ERROR));
            try {
                $makeBuilder()->build();
                self::fail('Smart metadata detached from its admitted ID unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('COMPONENT_CATALOG_SMART_METADATA_INVALID', $exception->errorCode);
            }
            copy($root . '/resources/component-catalog/smart/ui.alert.json', $alertPath);
            $alert = json_decode((string) file_get_contents($alertPath), true, flags: JSON_THROW_ON_ERROR);
            $alert['states'] = array_values(array_filter(
                $alert['states'],
                static fn (string $state): bool => $state !== 'warning',
            ));
            file_put_contents($alertPath, json_encode($alert, JSON_THROW_ON_ERROR));
            try {
                $makeBuilder()->build();
                self::fail('Smart metadata unexpectedly omitted an admitted manifest state.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('COMPONENT_CATALOG_SMART_METADATA_WIDENS_MANIFEST', $exception->errorCode);
            }
            copy($root . '/resources/component-catalog/smart/ui.alert.json', $alertPath);
            $alert = json_decode((string) file_get_contents($alertPath), true, flags: JSON_THROW_ON_ERROR);
            $alert['states'][] = 'teleporting';
            file_put_contents($alertPath, json_encode($alert, JSON_THROW_ON_ERROR));
            try {
                $makeBuilder()->build();
                self::fail('Smart metadata unexpectedly widened exact manifest states.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('COMPONENT_CATALOG_SMART_METADATA_WIDENS_MANIFEST', $exception->errorCode);
            }
            copy($root . '/resources/component-catalog/smart/ui.alert.json', $alertPath);
            $alert = json_decode((string) file_get_contents($alertPath), true, flags: JSON_THROW_ON_ERROR);
            $alert['title'] = 'Localized text is forbidden in technical metadata';
            file_put_contents($alertPath, json_encode($alert, JSON_THROW_ON_ERROR));
            try {
                $makeBuilder()->build();
                self::fail('Localized presentation unexpectedly passed inside Smart technical metadata.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
            copy($root . '/resources/component-catalog/smart/ui.alert.json', $alertPath);
            copy($alertPath, $smartDirectory . '/duplicate-alert.json');
            $makeBuilder()->build();
            self::fail('Duplicate Smart metadata unexpectedly overwrote the first owner record.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('COMPONENT_CATALOG_DUPLICATE_ID', $exception->errorCode);
        } finally {
            foreach (glob($smartDirectory . '/*') ?: [] as $path) {
                @unlink($path);
            }
            @rmdir($smartDirectory);
            @rmdir(dirname($smartDirectory));
            @rmdir(dirname(dirname($smartDirectory)));
            @rmdir($temporary . '/resources');
            @rmdir($temporary);
        }
    }

    #[Test]
    public function native_and_typed_sources_reject_cross_field_contract_drift(): void
    {
        $root = dirname(__DIR__, 2);
        $native = sys_get_temp_dir() . '/docara-native-' . bin2hex(random_bytes(8));
        $typed = sys_get_temp_dir() . '/docara-typed-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($native, 0700));
        self::assertTrue(mkdir($typed, 0700));

        try {
            foreach (glob($root . '/resources/component-catalog/native/*.json') ?: [] as $path) {
                copy($path, $native . '/' . basename($path));
            }
            foreach (glob($root . '/resources/component-catalog/typed/*.json') ?: [] as $path) {
                copy($path, $typed . '/' . basename($path));
            }

            $nativeCodePath = $native . '/native.code.json';
            $nativeCode = json_decode((string) file_get_contents($nativeCodePath), true, flags: JSON_THROW_ON_ERROR);
            $nativeCode['family'] = 'requirement';
            file_put_contents($nativeCodePath, json_encode($nativeCode, JSON_THROW_ON_ERROR));
            try {
                (new PortableMarkdownProfile($native))->entries();
                self::fail('A native capability with a foreign family unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('NATIVE_MARKDOWN_PROFILE_INVALID', $exception->errorCode);
            }
            $nativeCode['family'] = 'native_markdown';
            file_put_contents($nativeCodePath, json_encode($nativeCode, JSON_THROW_ON_ERROR));
            copy($nativeCodePath, $native . '/stale.json');
            try {
                (new PortableMarkdownProfile($native))->entries();
                self::fail('A stale native capability record unexpectedly passed the exact profile inventory.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('NATIVE_MARKDOWN_PROFILE_INVALID', $exception->errorCode);
            }
            unlink($native . '/stale.json');

            $typedCardPath = $typed . '/docara.card.json';
            $typedCard = json_decode((string) file_get_contents($typedCardPath), true, flags: JSON_THROW_ON_ERROR);
            $typedCard['name'] = 'other';
            file_put_contents($typedCardPath, json_encode($typedCard, JSON_THROW_ON_ERROR));
            try {
                (new TypedComponentDefinitionRepository($typed))->all();
                self::fail('A typed definition detached from its renderer unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('TYPED_COMPONENT_DEFINITION_MISMATCH', $exception->errorCode);
            }
            $typedCard['name'] = 'ui_card';
            file_put_contents($typedCardPath, json_encode($typedCard, JSON_THROW_ON_ERROR));
            try {
                (new TypedComponentDefinitionRepository($typed))->all();
                self::fail('A typed definition unexpectedly entered the reserved ui namespace.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        } finally {
            foreach ([$native, $typed] as $directory) {
                foreach (glob($directory . '/*') ?: [] as $path) {
                    @unlink($path);
                }
                @rmdir($directory);
            }
        }
    }

    private function builder(): EffectiveComponentCatalogBuilder
    {
        return EffectiveComponentCatalogBuilder::bundled(FrameworkLock::fromJsonFile(
            dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json',
        ));
    }

    /** @param array<string, mixed> $catalog */
    private function entry(array $catalog, string $id): array
    {
        foreach ($catalog['entries'] as $entry) {
            if (($entry['id'] ?? null) === $id) {
                return $entry;
            }
        }

        self::fail("Missing effective catalogue entry [$id].");
    }
}
