<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogValidator;
use Simai\Docara\ComponentCatalog\TypedComponentDefinitionRepository;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkComponentRuntime;
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

        self::assertSame(['card', 'columns', 'cta', 'features', 'steps'], $repository->names());
        self::assertSame([
            'docara.card',
            'docara.columns',
            'docara.cta',
            'docara.features',
            'docara.steps',
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
        self::assertSame('sf-v5.3.2-7e836d8a-dd786bba', $first['framework_pair']);
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
            'native.headings_and_text',
            'native.links_and_images',
            'native.lists_and_quotes',
            'native.table',
            'docara.card',
            'docara.columns',
            'docara.cta',
            'docara.features',
            'docara.steps',
            'ui.alert',
            'ui.button',
        ] as $id) {
            self::assertSame('supported', $lifecycles[$id] ?? null, $id);
        }
        foreach ($first['entries'] as $entry) {
            self::assertIsArray($entry['presentation']['ru'] ?? null, (string) $entry['id']);
            self::assertNotSame('', trim((string) $entry['presentation']['ru']['title']), (string) $entry['id']);
            self::assertNotSame('', trim((string) $entry['presentation']['ru']['description']), (string) $entry['id']);
            self::assertCount(
                count($entry['limitations']),
                $entry['presentation']['ru']['limitations'],
                (string) $entry['id'],
            );
            if ($entry['lifecycle'] === 'supported') {
                self::assertArrayNotHasKey('gap', $entry['presentation']['ru'], (string) $entry['id']);
            } else {
                self::assertIsArray($entry['presentation']['ru']['gap'] ?? null, (string) $entry['id']);
            }
        }
        self::assertSame('admission_pending', $lifecycles['ui.badge'] ?? null);
        self::assertSame('framework_gap', $lifecycles['content.icon'] ?? null);
        self::assertSame('framework_gap', $lifecycles['native.code.enhanced'] ?? null);
        self::assertSame('framework_gap', $lifecycles['ui.tabs'] ?? null);
        self::assertSame('deferred', $lifecycles['ui.dataview'] ?? null);

        $alert = $first['entries'][array_search('ui.alert', $ids, true)];
        self::assertSame('framework_smart', $alert['family']);
        self::assertSame(':::ui.alert', $alert['authoring']['call']);
        self::assertSame(['default', 'info', 'warning', 'danger'], $alert['states']);
        self::assertContains('closable=true is not admitted by the current bounded runtime.', $alert['limitations']);
        self::assertContains(
            'type=success is not admitted because the pinned Framework stylesheet renders its status icon transparent.',
            $alert['limitations'],
        );
        self::assertSame(['closable', 'success'], $alert['consumer_policy']['excluded_states']);
        $alertParameters = array_column($alert['authoring']['parameters'], null, 'name');
        self::assertSame(
            ['clear', 'info', 'danger', 'warning'],
            $alertParameters['type']['values'],
        );
        self::assertSame(
            array_keys($alertParameters),
            array_keys($alert['presentation']['ru']['parameters']),
        );
        self::assertSame('Уведомление', $alert['presentation']['ru']['title']);
        self::assertSame(
            'resources/framework/manifests/ui-alert.json',
            $alert['provenance']['manifest_ref'],
        );
        $button = $this->entry($first, 'ui.button');
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
        $minimalCall = ":::ui.button\n{}\n:::\n";
        $minimalDocument = FrameworkComponentRuntime::fromLockFile(
            dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json',
        )->extract($minimalCall, 'catalog-minimal-button.md');
        self::assertSame('Save', $minimalDocument->normalizedCalls[0]['props']['text']);

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
            foreach (['docs_ref', 'example_ref'] as $pathKey) {
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
                self::assertArrayHasKey('example_ref', $entry);
            } else {
                self::assertFalse($entry['verification']['demo']);
                self::assertNotSame('', $entry['gap']['owner'] ?? '');
                self::assertNotSame('', $entry['gap']['reason'] ?? '');
                self::assertNotSame('', $entry['gap']['fallback'] ?? '');
                self::assertNotSame('', $entry['gap']['admission_condition'] ?? '');
            }
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
        unset($missingGap['entries'][$gapIndex]['gap']['fallback']);
        $cases[] = [$missingGap, 'COMPONENT_CATALOG_GAP_EXPLANATION_REQUIRED'];

        $unsupportedDemo = $catalog;
        $unsupportedDemo['entries'][$gapIndex]['verification']['demo'] = true;
        $cases[] = [$unsupportedDemo, 'COMPONENT_CATALOG_UNSUPPORTED_DEMO_FORBIDDEN'];

        $missingLocalizedExample = $catalog;
        unset($missingLocalizedExample['entries'][$supportedIndex]['presentation']['ru']['example_ref']);
        $cases[] = [$missingLocalizedExample, 'COMPONENT_CATALOG_PRESENTATION_INVALID'];

        $unsafePath = $catalog;
        $unsafePath['entries'][0]['docs_ref'] = '/Users/example/private.md';
        $cases[] = [$unsafePath, 'COMPONENT_CATALOG_PATH_INVALID'];

        $unsafeText = $catalog;
        $unsafeText['entries'][0]['description'] = 'Private source: /Users/example/private.md';
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
        $runtime = FrameworkComponentRuntime::fromLockFile($root . '/docs/site/simai-framework.lock.json');
        $renderer = new PortableMarkdownRenderer;
        $nativeIdentity = [
            'native.code' => [
                'markdown' => ['```php', "\$site = 'Docara';"],
                'html' => [
                    '<pre class="bg-surface-container border border-outline-variant radius-2 p-2 overflow-auto"><code class="language-php">',
                    "\$site = 'Docara';",
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
                    '![Docara mark](../../../_docara/component-catalog/docara-mark.svg)',
                ],
                'html' => [
                    '<a href="../">Back to the catalog</a>',
                    '<img src="../../../_docara/component-catalog/docara-mark.svg" alt="Docara mark" />',
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
                ],
            ],
            'native.table' => [
                'markdown' => [
                    '| File | Role |',
                    '| `docara.json` | Site settings |',
                ],
                'html' => [
                    '<div class="overflow-auto"><table class="table table-border table-stripe">',
                    '<th>File</th>',
                    '<code>docara.json</code>',
                ],
            ],
        ];
        $typedIdentity = [
            'docara.card' => [
                '<section class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1">',
                '<h3>Portable project</h3>',
            ],
            'docara.columns' => [
                '<section data-docara-block="columns" data-docara-columns="4" class="grid grid-col-1 md:grid-col-2 lg:grid-col-4 gap-2">',
                '<div class="min-w-0">',
            ],
            'docara.cta' => [
                '<a data-docara-block="cta" class="docara-cta-link sf-button',
                '<span class="sf-button-text-container">Back to the catalog</span>',
            ],
            'docara.features' => [
                '<ul data-docara-block="features" class="docara-feature-grid',
                '<li class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1">',
            ],
            'docara.steps' => [
                '<section class="bg-surface-0 border border-outline-variant radius-2 p-3">',
                '<ol class="flex flex-col gap-2 p-inline-start-3">',
            ],
        ];
        $smartIdentity = [
            'ui.alert' => '<sf-alert',
            'ui.button' => '<sf-button',
        ];
        $supported = array_values(array_filter(
            $catalog['entries'],
            static fn (array $entry): bool => ($entry['lifecycle'] ?? null) === 'supported',
        ));
        self::assertCount(12, $supported);
        $expectedIds = array_merge(
            array_keys($nativeIdentity),
            array_keys($typedIdentity),
            array_keys($smartIdentity),
        );
        sort($expectedIds, SORT_STRING);
        self::assertSame($expectedIds, array_column($supported, 'id'));
        self::assertCount(
            count($supported),
            array_unique(array_column($supported, 'example_ref')),
            'Every supported catalogue entry must own a distinct executable fixture.',
        );

        foreach ($supported as $entry) {
            self::assertSame(
                'resources/component-catalog/examples/' . $entry['id'] . '.md',
                $entry['example_ref'],
                $entry['id'],
            );
            $markdown = file_get_contents($root . '/' . $entry['example_ref']);
            self::assertIsString($markdown, $entry['id']);
            $document = $runtime->extract($markdown, $entry['example_ref']);
            $html = $document->hydrate($renderer->render($document->markdownWithPlaceholders));
            self::assertNotSame('', trim($html), $entry['id']);
            $localizedReference = $entry['presentation']['ru']['example_ref'] ?? null;
            self::assertIsString($localizedReference, $entry['id']);
            self::assertStringEndsWith('.ru.md', $localizedReference, $entry['id']);
            self::assertNotSame($entry['example_ref'], $localizedReference, $entry['id']);
            $localizedMarkdown = file_get_contents($root . '/' . $localizedReference);
            self::assertIsString($localizedMarkdown, $entry['id']);
            $localizedDocument = $runtime->extract($localizedMarkdown, $localizedReference);
            self::assertNotSame(
                '',
                trim($localizedDocument->hydrate(
                    $renderer->render($localizedDocument->markdownWithPlaceholders),
                )),
                $entry['id'],
            );

            if ($entry['family'] === 'native_markdown') {
                self::assertSame([], $document->normalizedCalls, $entry['id']);
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
            self::assertSame(
                1,
                preg_match_all('/^' . preg_quote($call, '/') . '\h*$/m', $markdown),
                "{$entry['id']} fixture must contain its exact directive opener once.",
            );

            if ($entry['family'] === 'docara_typed') {
                self::assertSame([], $document->normalizedCalls, $entry['id']);
                self::assertArrayHasKey($entry['id'], $typedIdentity);
                foreach ($typedIdentity[$entry['id']] as $marker) {
                    self::assertStringContainsString($marker, $html, $entry['id']);
                }

                continue;
            }

            self::assertSame('framework_smart', $entry['family'], $entry['id']);
            self::assertArrayHasKey($entry['id'], $smartIdentity);
            self::assertCount(1, $document->normalizedCalls, $entry['id']);
            self::assertSame($entry['id'], $document->normalizedCalls[0]['id']);
            self::assertSame(
                1,
                substr_count($html, $smartIdentity[$entry['id']]),
                "{$entry['id']} fixture must render its exact Smart host once.",
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
            $alert['presentation']['ru']['title'] = 'Другой заголовок';
            file_put_contents($alertPath, json_encode($alert, JSON_THROW_ON_ERROR));
            try {
                $makeBuilder()->build();
                self::fail('Smart presentation conflicting with exact manifest i18n unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('COMPONENT_CATALOG_SMART_PRESENTATION_MISMATCH', $exception->errorCode);
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
