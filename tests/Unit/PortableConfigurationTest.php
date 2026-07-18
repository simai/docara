<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\ConfigurationMerger;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\PortableConfigurationLoader;
use Simai\Docara\Portable\SchemaRepository;

final class PortableConfigurationTest extends TestCase
{
    private string $root;

    /** @var list<string> */
    private array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/docara-portable-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0777, true);
        $this->cleanup[] = $this->root;
        $this->createValidSite();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $path) {
            $this->deletePath($path);
        }

        parent::tearDown();
    }

    public function test_it_resolves_an_explainable_portable_page_plan(): void
    {
        $loader = new PortableConfigurationLoader($this->root);

        $plan = $loader->resolve('content/docs/deep/install.md');

        self::assertSame('content/docs/deep/install.md', $plan->page);
        self::assertSame("# Install\n\nPortable content.\n", $plan->markdown);
        self::assertSame('landing', $plan->configuration['preset']);
        self::assertSame('Portable docs', $plan->configuration['title']);
        self::assertSame('content', $plan->configuration['content_root']);
        self::assertSame('framework.lock.json', $plan->configuration['framework_lock']);
        self::assertSame('docs/install', $plan->configuration['slug']);
        self::assertSame('full', $plan->configuration['layout']['max_width']);
        self::assertSame('dark', $plan->configuration['settings']['theme']);
        self::assertSame('Portable brand', $plan->configuration['branding']['title']);
        self::assertSame('Deep documentation', $plan->configuration['branding']['label']);
        self::assertSame('assets/logo.svg', $plan->configuration['branding']['logo']);
        self::assertTrue($plan->configuration['navigation']['hidden']);
        self::assertSame(5, $plan->configuration['navigation']['order']);
        self::assertSame('content/docs/deep/install.page.json', $plan->provenance['/layout/max_width']);
        self::assertSame('content/docs/deep/_section.json', $plan->provenance['/settings/theme']);
        self::assertSame('docara.json', $plan->provenance['/branding/logo']);
        self::assertSame('content/docs/deep/_section.json', $plan->provenance['/branding/label']);
        self::assertSame('content/docs/_section.json', $plan->provenance['/navigation/hidden']);
        self::assertSame('content/docs/deep/install.page.json', $plan->provenance['/navigation/order']);
        self::assertSame('content/docs/deep/install.page.json', $plan->provenance['/preset']);
        self::assertSame(
            ['site', 'framework-lock', 'section', 'section', 'section', 'section', 'page', 'content'],
            array_column($plan->trace, 'role'),
        );
        self::assertSame(
            [
                'docara.json',
                'framework.lock.json',
                '_section.json',
                'content/_section.json',
                'content/docs/_section.json',
                'content/docs/deep/_section.json',
                'content/docs/deep/install.page.json',
                'content/docs/deep/install.md',
            ],
            array_column($plan->trace, 'source'),
        );
        self::assertSame('docara.site.v1', $plan->trace[0]['schema']);
        self::assertSame('docara.framework_lock.v1', $plan->trace[1]['schema']);
        self::assertSame('larena.ui.frontend_runtime_lock.v3', $plan->frameworkLock['runtime']['schema']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plan->canonicalHash());
        $serialized = $plan->toArray();
        self::assertSame(1, $serialized['contract_version']);
        self::assertArrayHasKey('framework_lock', $serialized);
        self::assertArrayNotHasKey('contractVersion', $serialized);
        self::assertArrayNotHasKey('frameworkLock', $serialized);
        self::assertStringNotContainsString($this->root, CanonicalJson::encode($serialized));
    }

    public function test_arrays_replace_objects_merge_and_reset_clears_a_branch_and_its_provenance(): void
    {
        $merger = new ConfigurationMerger;
        $base = $merger->merge([], [
            'layout' => [
                'sidebar' => ['position' => 'left', 'width' => '18rem'],
                'slots' => ['header', 'content', 'footer'],
            ],
            'settings' => ['theme' => 'system'],
            'accent' => 'blue',
        ], 'docara.json');

        $resolved = $merger->merge($base->configuration, [
            'layout' => [
                'sidebar' => ['$reset' => true, 'position' => 'right'],
                'slots' => ['hero', 'content'],
            ],
            'settings' => [],
            'accent' => ['$reset' => true, '$value' => null],
        ], 'section/_section.json', $base->provenance);

        self::assertSame(['position' => 'right'], $resolved->configuration['layout']['sidebar']);
        self::assertSame(['hero', 'content'], $resolved->configuration['layout']['slots']);
        self::assertSame(['theme' => 'system'], $resolved->configuration['settings']);
        self::assertNull($resolved->configuration['accent']);
        self::assertArrayNotHasKey('/layout/sidebar/width', $resolved->provenance);
        self::assertSame('section/_section.json', $resolved->provenance['/layout/sidebar/position']);
        self::assertSame('section/_section.json', $resolved->provenance['/layout/slots']);
        self::assertSame('section/_section.json', $resolved->provenance['/accent']);
    }

    public function test_reset_only_preserves_an_empty_json_object_in_the_resolved_page_plan(): void
    {
        $section = json_decode(
            (string) file_get_contents($this->path('content/docs/_section.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $section['navigation'] = ['$reset' => true];
        $this->writeJson('content/docs/_section.json', $section);
        $page = json_decode(
            (string) file_get_contents($this->path('content/docs/deep/install.page.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        unset($page['navigation']);
        $this->writeJson('content/docs/deep/install.page.json', $page);

        $plan = (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');

        self::assertInstanceOf(\stdClass::class, $plan->configuration['navigation']);
        $canonical = CanonicalJson::encode($plan->toArray());
        self::assertStringContainsString('"navigation":{}', $canonical);
        self::assertStringNotContainsString('"navigation":[]', $canonical);
        self::assertSame('content/docs/_section.json', $plan->provenance['/navigation']);
    }

    public function test_the_canonical_hash_is_stable_for_the_same_semantic_plan(): void
    {
        $loader = new PortableConfigurationLoader($this->root);
        $first = $loader->resolve('content/docs/deep/install.md');
        $site = json_decode((string) file_get_contents($this->path('docara.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->writeJson('docara.json', array_reverse($site, true));
        $second = $loader->resolve('content/docs/deep/install.md');

        self::assertNotSame($first->trace[0]['sha256'], $second->trace[0]['sha256']);
        self::assertSame($first->canonicalHash(), $second->canonicalHash());
        self::assertEquals($first->configuration, $second->configuration);
    }

    public function test_content_root_defaults_to_content_and_is_explained_in_provenance(): void
    {
        $site = json_decode((string) file_get_contents($this->path('docara.json')), true, 512, JSON_THROW_ON_ERROR);
        unset($site['content_root']);
        $this->writeJson('docara.json', $site);

        $plan = (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');

        self::assertSame('content', $plan->configuration['content_root']);
        self::assertSame('@defaults', $plan->provenance['/content_root']);
    }

    public function test_the_shipped_portable_lock_is_accepted_with_its_independent_manifest_provider_revision(): void
    {
        $stub = dirname(__DIR__, 2) . '/stubs/portable';

        $plan = (new PortableConfigurationLoader($stub))->resolve('content/index.md');

        self::assertSame(
            '4b055d09926fec4c32f2ae43b2e7e0a6f64d7663',
            $plan->frameworkLock['manifests']['ui.button']['provider_revision'],
        );
        self::assertSame('larena/ui', $plan->frameworkLock['manifests']['ui.button']['provider']);
    }

    public function test_unknown_schema_versions_are_rejected(): void
    {
        $site = json_decode((string) file_get_contents($this->path('docara.json')), true, 512, JSON_THROW_ON_ERROR);
        $site['schema'] = 'docara.site.v2';
        $this->writeJson('docara.json', $site);

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('[SCHEMA_VALIDATION_FAILED]');

        (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');
    }

    public function test_malformed_json_is_rejected(): void
    {
        file_put_contents($this->path('content/docs/_section.json'), '{"schema":"docara.section.v1",');

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('[JSON_INVALID]');

        (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');
    }

    public function test_invalid_component_calls_are_rejected_by_the_shared_schema(): void
    {
        foreach ([
            ['schema' => 'docara.component_call.v2', 'id' => 'ui.alert', 'props' => []],
            ['schema' => 'docara.component_call.v1', 'id' => 'alert', 'props' => []],
            ['schema' => 'docara.component_call.v1', 'id' => 'ui.card', 'props' => []],
            ['schema' => 'docara.component_call.v1', 'id' => 'ui.alert'],
            ['schema' => 'docara.component_call.v1', 'id' => 'ui.alert', 'props' => [], 'unknown' => true],
        ] as $call) {
            try {
                (new SchemaRepository)->assertValid($call, 'component-call.schema.json');
                self::fail('Invalid component call unexpectedly passed schema validation.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }
    }

    public function test_descriptor_schemas_expose_only_working_presentation_settings(): void
    {
        foreach ([
            ['site.schema.json', [
                'schema' => 'docara.site.v1',
                'preset' => 'docs',
                'framework_lock' => 'framework.lock.json',
                'layout' => ['max_width' => 'wide'],
                'settings' => ['theme' => 'system'],
                'navigation' => ['hidden' => false, 'order' => 2147483647],
                'branding' => [
                    'title' => 'Product',
                    'label' => 'Docs',
                    'logo' => 'assets/logo.svg',
                    'logo_dark' => 'assets/logo-dark.svg',
                    'favicon' => 'assets/favicon.ico',
                ],
            ]],
            ['section.schema.json', [
                'schema' => 'docara.section.v1',
                'layout' => ['$reset' => true, 'max_width' => 'compact'],
                'settings' => ['theme' => 'dark'],
                'navigation' => ['hidden' => true, 'order' => 20],
                'branding' => ['$reset' => true, 'title' => 'Section product'],
            ]],
            ['page.schema.json', [
                'schema' => 'docara.page.v1',
                'layout' => ['max_width' => 'full'],
                'settings' => ['$reset' => true, 'theme' => 'light'],
                'navigation' => ['$reset' => true, 'order' => 5],
                'branding' => ['label' => 'Reference'],
            ]],
        ] as [$schema, $descriptor]) {
            (new SchemaRepository)->assertValid($descriptor, $schema);
            $this->addToAssertionCount(1);
        }

        $site = [
            'schema' => 'docara.site.v1',
            'preset' => 'docs',
            'framework_lock' => 'framework.lock.json',
        ];
        foreach ([
            [$site + ['theme' => 'dark'], 'site.schema.json'],
            [$site + ['layout' => ['sidebar' => ['position' => 'left']]], 'site.schema.json'],
            [$site + ['layout' => ['max_width' => '72rem']], 'site.schema.json'],
            [['schema' => 'docara.section.v1', 'settings' => ['theme' => 'sepia']], 'section.schema.json'],
            [['schema' => 'docara.section.v1', 'settings' => ['table_of_contents' => true]], 'section.schema.json'],
            [['schema' => 'docara.page.v1', 'navigation' => ['enabled' => true]], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'navigation' => ['hidden' => 'false']], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'navigation' => ['order' => -1]], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'navigation' => ['order' => 2147483648]], 'page.schema.json'],
            [['schema' => 'docara.section.v1', 'navigation' => ['order' => '10']], 'section.schema.json'],
            [['schema' => 'docara.page.v1', 'branding' => ['unknown' => true]], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'branding' => ['title' => '']], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'branding' => ['label' => '']], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'branding' => ['logo' => '/absolute/logo.svg']], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'branding' => ['logo' => '../logo.svg']], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'branding' => ['logo' => 'assets\\logo.svg']], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'components' => []], 'page.schema.json'],
            [['schema' => 'docara.page.v1', 'variables' => []], 'page.schema.json'],
        ] as [$descriptor, $schema]) {
            try {
                (new SchemaRepository)->assertValid($descriptor, $schema);
                self::fail("No-op or invalid presentation settings unexpectedly passed [$schema].");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }
    }

    public function test_branding_reset_clears_inherited_assets_and_records_new_provenance(): void
    {
        $this->writeJson('content/docs/deep/install.page.json', [
            'schema' => 'docara.page.v1',
            'branding' => ['$reset' => true, 'title' => 'Page brand'],
        ]);

        $plan = (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');

        self::assertSame(['title' => 'Page brand'], $plan->configuration['branding']);
        self::assertSame('content/docs/deep/install.page.json', $plan->provenance['/branding/title']);
        self::assertArrayNotHasKey('/branding/logo', $plan->provenance);
        self::assertArrayNotHasKey('/branding/label', $plan->provenance);
    }

    public function test_moving_framework_references_are_rejected(): void
    {
        foreach (['main', 'latest', 'refs/heads/main'] as $index => $movingReference) {
            $lock = $this->frameworkLock();

            if ($index === 0) {
                $lock['runtime']['ui']['commit'] = $movingReference;
            } elseif ($index === 1) {
                $lock['runtime']['ui_smart']['tag'] = $movingReference;
            } else {
                $lock['manifests']['ui.alert']['provider_revision'] = $movingReference;
            }

            $this->writeJson('framework.lock.json', $lock);

            try {
                (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');
                self::fail("Moving reference [$movingReference] unexpectedly passed validation.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }
    }

    public function test_non_commit_manifest_provider_revisions_are_rejected(): void
    {
        $lock = $this->frameworkLock();
        $lock['manifests']['ui.button']['provider_revision'] = str_repeat('a', 39);

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('[SCHEMA_VALIDATION_FAILED]');

        (new SchemaRepository)->assertValid($lock, 'framework-lock.schema.json');
    }

    public function test_base_url_and_page_slug_use_the_same_portable_path_alphabet(): void
    {
        foreach (['/', '/project', '/project/', '/project~/docs/', '/A_b-1.2'] as $baseUrl) {
            (new SchemaRepository)->assertValid([
                'schema' => 'docara.site.v1',
                'preset' => 'docs',
                'framework_lock' => 'framework.lock.json',
                'base_url' => $baseUrl,
            ], 'site.schema.json');
            $this->addToAssertionCount(1);
        }

        foreach (['//', '/./', '/../', '/project//docs', '/project?x', '/project#x', '/project%20', '/project\\docs'] as $baseUrl) {
            try {
                (new SchemaRepository)->assertValid([
                    'schema' => 'docara.site.v1',
                    'preset' => 'docs',
                    'framework_lock' => 'framework.lock.json',
                    'base_url' => $baseUrl,
                ], 'site.schema.json');
                self::fail("Unsafe base_url [$baseUrl] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }

        foreach (['Guide', 'guide.', 'guide space', '_docara', '.docara', 'guide%20'] as $slug) {
            try {
                (new SchemaRepository)->assertValid([
                    'schema' => 'docara.page.v1',
                    'slug' => $slug,
                ], 'page.schema.json');
                self::fail("Unsafe slug [$slug] unexpectedly passed.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SCHEMA_VALIDATION_FAILED', $exception->errorCode);
            }
        }
    }

    public function test_framework_asset_projection_semantics_are_centralized_and_fail_closed(): void
    {
        $lock = $this->frameworkLock();
        $lock['asset_projection']['source']['revision'] = str_repeat('a', 40);
        $this->writeJson('framework.lock.json', $lock);

        try {
            (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');
            self::fail('A mismatched Framework projection unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('FRAMEWORK_ASSET_PROJECTION_INVALID', $exception->errorCode);
        }
    }

    public function test_lexically_disguised_root_symlink_and_parent_traversal_are_rejected(): void
    {
        $link = $this->root . '-link';
        if (! @symlink($this->root, $link)) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }

        try {
            foreach ([$link, $link . '/', $link . '/.'] as $root) {
                try {
                    new PortableConfigurationLoader($root);
                    self::fail("Symlink root [$root] unexpectedly passed.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame('ROOT_SYMLINK_FORBIDDEN', $exception->errorCode);
                }
            }

            try {
                new PortableConfigurationLoader($this->root . '/content/..');
                self::fail('A root containing parent traversal unexpectedly passed.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('ROOT_PATH_INVALID', $exception->errorCode);
            }
        } finally {
            @unlink($link);
        }
    }

    public function test_unreadable_required_markdown_fails_with_a_controlled_error(): void
    {
        $path = $this->path('content/docs/deep/install.md');
        chmod($path, 0000);
        clearstatcache(true, $path);
        if (is_readable($path)) {
            chmod($path, 0644);
            self::markTestSkipped('The current user can still read mode-0000 files.');
        }

        try {
            (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/install.md');
            self::fail('Unreadable Markdown unexpectedly passed.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('PORTABLE_FILE_READ_FAILED', $exception->errorCode);
        } finally {
            chmod($path, 0644);
        }
    }

    public function test_parent_segments_and_absolute_paths_are_rejected(): void
    {
        $loader = new PortableConfigurationLoader($this->root);

        foreach (['../outside.md', '/tmp/outside.md', 'guides/../outside.md'] as $path) {
            try {
                $loader->resolve($path);
                self::fail("Unsafe path [$path] unexpectedly resolved.");
            } catch (PortableConfigurationException $exception) {
                self::assertContains($exception->errorCode, ['PATH_ESCAPE_FORBIDDEN', 'ABSOLUTE_PATH_FORBIDDEN']);
            }
        }
    }

    public function test_pages_outside_the_configured_content_root_are_rejected(): void
    {
        $this->write('outside.md', "# Outside\n");

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('[PAGE_OUTSIDE_CONTENT_ROOT]');

        (new PortableConfigurationLoader($this->root))->resolve('outside.md');
    }

    public function test_symbolic_links_are_rejected_even_when_the_target_is_inside_the_root(): void
    {
        $target = $this->path('content/docs/deep/install.md');
        $link = $this->path('content/docs/deep/link.md');

        if (! @symlink($target, $link)) {
            self::markTestSkipped('Symbolic links are not supported by this test environment.');
        }

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('[SYMLINK_FORBIDDEN]');

        (new PortableConfigurationLoader($this->root))->resolve('content/docs/deep/link.md');
    }

    private function createValidSite(): void
    {
        $this->writeJson('docara.json', [
            'schema' => 'docara.site.v1',
            'preset' => 'docs',
            'framework_lock' => 'framework.lock.json',
            'content_root' => 'content',
            'base_url' => '/',
            'default_locale' => 'en',
            'title' => 'Portable docs',
            'locale' => 'en',
            'layout' => [
                'max_width' => 'normal',
            ],
            'settings' => [
                'theme' => 'system',
            ],
            'branding' => [
                'title' => 'Portable brand',
                'logo' => 'assets/logo.svg',
                'logo_dark' => 'assets/logo-dark.svg',
                'favicon' => 'assets/favicon.svg',
            ],
            'navigation' => [
                'hidden' => false,
            ],
        ]);
        $this->writeJson('framework.lock.json', $this->frameworkLock());
        $this->writeJson('_section.json', [
            'schema' => 'docara.section.v1',
        ]);
        $this->writeJson('content/_section.json', [
            'schema' => 'docara.section.v1',
            'layout' => ['max_width' => 'compact'],
        ]);
        $this->writeJson('content/docs/_section.json', [
            'schema' => 'docara.section.v1',
            'layout' => ['max_width' => 'wide'],
            'navigation' => ['hidden' => true, 'order' => 20],
        ]);
        $this->writeJson('content/docs/deep/_section.json', [
            'schema' => 'docara.section.v1',
            'settings' => ['theme' => 'dark'],
            'branding' => ['label' => 'Deep documentation'],
        ]);
        $this->write('content/docs/deep/install.md', "# Install\n\nPortable content.\n");
        $this->writeJson('content/docs/deep/install.page.json', [
            'schema' => 'docara.page.v1',
            'preset' => 'landing',
            'slug' => 'docs/install',
            'locale' => 'en',
            'layout' => ['max_width' => 'full'],
            'navigation' => ['order' => 5],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function frameworkLock(): array
    {
        return [
            'schema' => 'docara.framework_lock.v1',
            'runtime' => [
                'schema' => 'larena.ui.frontend_runtime_lock.v3',
                'runtime' => 'simai-framework',
                'pair_id' => 'sf-v5.3.2-7e836d8a-dd786bba',
                'bundle_id' => 'sf-v5.3.2-7e836d8a-dd786bba-registry-2c596327-verified-release-artifact-v1',
                'publication_profile' => 'verified-release-artifact-v1',
                'tag' => 'v5.3.2',
                'ui' => [
                    'tag' => 'v5.3.2',
                    'commit' => '7e836d8a9414d5da553fb1ab0404721e5b48769a',
                    'tree' => 'distr',
                    'mount' => 'ui',
                    'sha256' => '481eabfafc259ab71cd11aff19f9358cdbd2b6709f85e7e8c39620ce9cace8d7',
                    'files' => 2596,
                ],
                'ui_smart' => [
                    'tag' => 'v5.3.1',
                    'commit' => 'dd786bbae98391fb21df9b4e1e6cd402ead0614c',
                    'tree' => 'smart',
                    'mount' => 'smart',
                    'sha256' => '1c2eacbc58f3deb1d351b11dfb5da6755502386bb1224554754477bc700c9262',
                    'files' => 112,
                ],
                'framework_registry' => [
                    'schema_id' => 'simai.framework.contract-registry',
                    'compatibility_id' => 'sf-v5.3.2-7e836d8a-dd786bba',
                    'profile' => 'plain-assets-v1',
                    'relative_path' => 'contract/contracts/generated/framework-contract-registry.json',
                    'file_sha256' => '2c5963276d31af09770fe41cad04826c04b634f7b2d798d9b0e32864517346b7',
                    'source' => [
                        'commit' => 'b7e8a2e810c0d49e31cb749a7ab34c373dd48bc6',
                        'tree' => 'contracts/generated',
                        'tree_oid' => 'ed200af53182334542b48fb7402e219068091bd4',
                        'mount' => 'contract',
                        'sha256' => '0f915061c3664571f8ce522793ed7192889b8eb19e4c62490440926449a13f9b',
                        'files' => 1,
                    ],
                ],
                'boot' => [
                    'css' => 'ui/distr/core/css/core.css',
                    'javascript' => 'ui/distr/core/js/core.js',
                    'smart_base' => 'ui/distr/core/js/smart-base.js',
                    'ui_base' => 'ui/distr/',
                    'smart_base_path' => 'smart/',
                ],
                'components' => [
                    'sf-button' => [
                        'source' => 'smart/buttons',
                        'javascript' => 'smart/smart/buttons/js/buttons.js',
                        'css' => null,
                        'attributes' => ['template', 'text', 'type'],
                    ],
                    'sf-alert' => [
                        'source' => 'smart/alert',
                        'javascript' => 'smart/smart/alert/js/alert.js',
                        'css' => null,
                        'requires' => ['sf-icon'],
                        'attributes' => ['type', 'variant', 'title'],
                    ],
                ],
            ],
            'manifests' => [
                'ui.button' => [
                    'provider' => 'larena/ui',
                    'provider_revision' => '4b055d09926fec4c32f2ae43b2e7e0a6f64d7663',
                    'sha256' => str_repeat('2', 64),
                ],
                'ui.alert' => [
                    'provider' => 'larena/ui',
                    'provider_revision' => '4b055d09926fec4c32f2ae43b2e7e0a6f64d7663',
                    'sha256' => str_repeat('3', 64),
                ],
            ],
            'asset_projection' => [
                'schema' => 'docara.framework_asset_projection.v1',
                'mount' => '_docara/framework',
                'source' => [
                    'provider' => 'simai/ui-smart',
                    'revision' => 'dd786bbae98391fb21df9b4e1e6cd402ead0614c',
                ],
                'files' => [
                    'smart/alert/js/alert.js' => [
                        'sha256' => 'e994066dd2a7f9c4d15c573ea66bb47ccb0f12c24f4cf2e7dedee29eaddf9f1c',
                    ],
                    'smart/buttons/js/buttons.js' => [
                        'sha256' => 'fe977fc7c608b7bacb79b7641a302c30a6195659ac2351594ae5aef0656d0a27',
                    ],
                    'smart/icons/js/icons.js' => [
                        'sha256' => 'c810be681b51f98002e01fb8852e992e454fa607af005033f9cc10309016fa09',
                    ],
                ],
            ],
            'nonclaims' => [
                'production_ready',
                'all_framework_components_ready',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeJson(string $relative, array $data): void
    {
        $this->write(
            $relative,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
    }

    private function write(string $relative, string $contents): void
    {
        $path = $this->path($relative);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $contents);
    }

    private function path(string $relative): string
    {
        return $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function deletePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            if ($entry->isLink() || $entry->isFile()) {
                @unlink($entry->getPathname());
            } else {
                @rmdir($entry->getPathname());
            }
        }

        @rmdir($path);
    }
}
