<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Application\DiscoveryService;
use Simai\Docara\Application\ScaffoldService;
use Simai\Docara\Documentation\DocumentationSourceRepository;
use Simai\Docara\Documentation\DocumentationStatusService;
use Simai\Docara\Portable\PortableConfigurationException;
use Tests\TestCase;

final class DocumentationTrackingTest extends TestCase
{
    #[Test]
    public function neutral_contract_reports_and_accepts_current_changed_and_unverified_states(): void
    {
        $this->project();
        $service = new DocumentationStatusService;
        self::assertSame('new', $this->item($service->report($this->tmp), 'component.buttons')['status']);

        $plan = $service->planAccept(
            $this->tmp,
            'product',
            'component.buttons',
            '/ru/components/buttons/',
            'ai_verified',
            ['default' => 'components/buttons/default'],
        );
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plan['plan_id']);
        $service->apply($this->tmp, $plan['plan_id']);
        self::assertSame('current', $this->item($service->report($this->tmp), 'component.buttons')['status']);
        $reviewPlan = $service->planAccept(
            $this->tmp,
            'product',
            'component.buttons',
            '/ru/components/buttons/',
            'human_reviewed',
            ['default' => 'components/buttons/default'],
        );
        $service->apply($this->tmp, $reviewPlan['plan_id']);
        self::assertSame('human_reviewed', $this->item($service->report($this->tmp), 'component.buttons')['review']);

        $contract = json_decode((string) file_get_contents($this->tmpPath('documentation-source.json')), true, 512, JSON_THROW_ON_ERROR);
        $contract['entities'][0]['public_contract']['variants'][] = 'text';
        file_put_contents($this->tmpPath('documentation-source.json'), json_encode($contract, JSON_THROW_ON_ERROR));
        self::assertSame('changed', $this->item($service->report($this->tmp), 'component.buttons')['status']);

        file_put_contents($this->tmpPath('content/ru/components/buttons.md'), "# Кнопки\n\nИзменено.\n");
        self::assertSame('unverified', $this->item($service->report($this->tmp), 'component.buttons')['status']);
    }

    #[Test]
    public function stale_acceptance_plans_and_duplicate_entity_keys_fail_closed(): void
    {
        $this->project();
        $service = new DocumentationStatusService;
        $plan = $service->planAccept($this->tmp, 'product', 'component.buttons', '/ru/components/buttons/', 'ai_verified', ['default' => 'components/buttons/default']);
        $contract = json_decode((string) file_get_contents($this->tmpPath('documentation-source.json')), true, 512, JSON_THROW_ON_ERROR);
        $contract['entities'][0]['public_contract']['variants'][] = 'text';
        file_put_contents($this->tmpPath('documentation-source.json'), json_encode($contract, JSON_THROW_ON_ERROR));
        try {
            $service->apply($this->tmp, $plan['plan_id']);
            self::fail('Stale documentation plan was applied.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DOCUMENTATION_PLAN_STALE', $exception->errorCode);
        }

        $contract['entities'][] = $contract['entities'][0];
        file_put_contents($this->tmpPath('documentation-source.json'), json_encode($contract, JSON_THROW_ON_ERROR));
        $this->expectException(PortableConfigurationException::class);
        (new DocumentationSourceRepository)->all($this->tmp);
    }

    #[Test]
    public function source_contract_rejects_symlinks_hardlinks_invalid_utf8_and_case_collisions(): void
    {
        $this->project();
        $path = $this->tmpPath('documentation-source.json');
        $original = (string) file_get_contents($path);
        $outside = $this->tmpPath('outside.json');
        file_put_contents($outside, $original);

        unlink($path);
        self::assertTrue(symlink($outside, $path));
        $this->assertSourceRejected();
        unlink($path);

        self::assertTrue(link($outside, $path));
        $this->assertSourceRejected();
        unlink($path);

        file_put_contents($path, "\xFF");
        $this->assertSourceRejected();
        file_put_contents($path, $original);

        rename($path, $this->tmpPath('case-change.tmp'));
        rename($this->tmpPath('case-change.tmp'), $this->tmpPath('Documentation-Source.json'));
        $this->assertSourceRejected();
    }

    #[Test]
    public function acceptance_plan_is_bound_to_page_example_configuration_and_lock_inputs(): void
    {
        $this->project();
        $service = new DocumentationStatusService;
        $pagePath = $this->tmpPath('content/ru/components/buttons.md');
        $examplePath = $this->tmpPath('examples/components/buttons/default/index.html');
        $configPath = $this->tmpPath('docara.json');

        foreach ([
            [$pagePath, "\nChanged after planning.\n"],
            [$examplePath, "\n<!-- changed -->\n"],
            [$configPath, "\n"],
        ] as [$path, $suffix]) {
            $original = (string) file_get_contents($path);
            $plan = $service->planAccept($this->tmp, 'product', 'component.buttons', '/ru/components/buttons/', 'ai_verified', ['default' => 'components/buttons/default']);
            file_put_contents($path, $original . $suffix);
            $this->assertPlanStale($service, $plan['plan_id']);
            file_put_contents($path, $original);
        }

        $plan = $service->planAccept($this->tmp, 'product', 'component.buttons', '/ru/components/buttons/', 'ai_verified', ['default' => 'components/buttons/default']);
        file_put_contents($this->tmpPath('documentation.lock.json'), json_encode([
            'schema' => 'docara.documentation_lock.v1', 'source_locale' => 'ru', 'entries' => [], 'exclusions' => [],
        ], JSON_THROW_ON_ERROR));
        $this->assertPlanStale($service, $plan['plan_id']);
    }

    #[Test]
    public function several_source_entities_may_share_one_page_and_explicit_example(): void
    {
        $this->project();
        $contractPath = $this->tmpPath('documentation-source.json');
        $contract = json_decode((string) file_get_contents($contractPath), true, 512, JSON_THROW_ON_ERROR);
        $second = $contract['entities'][0];
        $second['key'] = 'component.icon-buttons';
        $second['title'] = 'Icon buttons';
        $contract['entities'][] = $second;
        file_put_contents($contractPath, json_encode($contract, JSON_THROW_ON_ERROR));

        $service = new DocumentationStatusService;
        foreach (['component.buttons', 'component.icon-buttons'] as $key) {
            $plan = $service->planAccept($this->tmp, 'product', $key, '/ru/components/buttons/', 'ai_verified', ['default' => 'components/buttons/default']);
            $service->apply($this->tmp, $plan['plan_id']);
        }
        $report = $service->report($this->tmp);
        self::assertSame(2, $report['summary']['current']);
        self::assertSame(['/ru/components/buttons/'], array_values(array_unique(array_column($report['items'], 'route'))));
    }

    #[Test]
    public function missing_example_missing_orphan_and_excluded_are_distinct(): void
    {
        $this->project();
        $service = new DocumentationStatusService;
        $plan = $service->planAccept($this->tmp, 'product', 'component.buttons', '/ru/components/buttons/', 'human_reviewed', ['default' => 'components/buttons/default']);
        $service->apply($this->tmp, $plan['plan_id']);

        $example = (string) file_get_contents($this->tmpPath('examples/components/buttons/default/index.html'));
        $this->filesystem->deleteDirectory($this->tmpPath('examples/components/buttons/default'));
        self::assertSame('missing_example', $this->item($service->report($this->tmp), 'component.buttons')['status']);
        $this->createSource(['examples/components/buttons/default/index.html' => $example]);

        $page = (string) file_get_contents($this->tmpPath('content/ru/components/buttons.md'));
        unlink($this->tmpPath('content/ru/components/buttons.md'));
        self::assertSame('missing', $this->item($service->report($this->tmp), 'component.buttons')['status']);
        $this->createSource(['content/ru/components/buttons.md' => $page]);

        $contractPath = $this->tmpPath('documentation-source.json');
        $contract = (string) file_get_contents($contractPath);
        $empty = json_decode($contract, true, 512, JSON_THROW_ON_ERROR);
        $empty['entities'] = [];
        file_put_contents($contractPath, json_encode($empty, JSON_THROW_ON_ERROR));
        self::assertSame('orphan', $this->item($service->report($this->tmp), 'component.buttons')['status']);

        file_put_contents($contractPath, $contract);
        $excluded = $service->planAccept($this->tmp, 'product', 'component.buttons', '', 'ai_verified', [], 'Covered by a product manual.');
        $service->apply($this->tmp, $excluded['plan_id']);
        self::assertSame('excluded', $this->item($service->report($this->tmp), 'component.buttons')['status']);
    }

    #[Test]
    public function sdk_discovers_sources_and_source_aware_scaffold_uses_the_contract_title(): void
    {
        $this->project();
        $listed = (new DiscoveryService)->list($this->tmp, 'source')->toArray();
        self::assertSame('product', $listed['data']['items'][0]['id']);
        $inspected = (new DiscoveryService)->inspect($this->tmp, 'source', 'product:component.buttons')->toArray();
        self::assertSame('component.buttons', $inspected['data']['entity']['key']);

        $service = new ScaffoldService;
        $plan = $service->plan($this->tmp, 'page', 'components/new-buttons', [
            'locale' => 'ru', 'title' => null, 'profile' => 'reference', 'source' => 'product', 'entity' => 'component.buttons',
        ]);
        $service->apply($this->tmp, $plan->data['plan_id']);
        self::assertStringContainsString('# Buttons', (string) file_get_contents($this->tmpPath('content/ru/components/new-buttons.md')));
        self::assertFileExists($this->tmpPath('examples/components/new-buttons/default/index.html'));
        self::assertStringContainsString('components/new-buttons/default', (string) file_get_contents($this->tmpPath('content/ru/components/new-buttons.md')));
    }

    #[Test]
    public function framework_adapter_groups_public_rules_and_radius_semantics(): void
    {
        $this->createSource([
            'docara.json' => json_encode([
                'schema' => 'docara.site.v1', 'preset' => 'docs', 'framework_lock' => 'simai-framework.lock.json',
                'default_locale' => 'ru', 'locales' => [
                    'missing_page_policy' => 'skip',
                    'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => 'ru'],
                ],
                'locale_routing' => ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false],
                'documentation_tracking' => [
                    'enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'documentation.lock.json',
                    'sources' => [['id' => 'simai-framework', 'provider' => 'simai_framework', 'framework_lock' => 'simai-framework.lock.json']],
                ],
            ], JSON_THROW_ON_ERROR),
            'simai-framework.lock.json' => (string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
            'content/ru/index.md' => "# Framework\n",
            'content/ru/lang.json' => '{}',
        ]);
        $source = (new DocumentationSourceRepository)->source($this->tmp, 'simai-framework');
        self::assertTrue($source['compatibility_adapter']);
        self::assertSame('sf-v5.4.1-185ca062-23d00d92', $source['revision']);
        $entities = array_column($source['entities'], null, 'key');
        self::assertArrayHasKey('component.buttons', $entities);
        self::assertArrayHasKey('smart.cl-buttons', $entities);
        self::assertArrayHasKey('utility.display', $entities);
        $radius = $entities['core.design-tokens']['public_contract']['rules'][0]['semantic_radius'];
        self::assertSame('compact_controls', $radius['--sf-radius--ui']['scope']);
        self::assertSame('large_surfaces', $radius['--sf-radius-default']['scope']);
    }

    #[Test]
    public function framework_provider_prefers_the_exact_pinned_neutral_contract(): void
    {
        $contract = [
            'schema' => 'docara.documentation_source.v1', 'id' => 'simai-framework', 'provider' => 'simai_framework', 'revision' => 'exact-contract-1',
            'entities' => [[
                'key' => 'component.buttons', 'kind' => 'component', 'title' => 'Buttons',
                'public_contract' => ['classes' => ['sf-button']], 'example_cases' => ['default'], 'provenance' => ['simai/ui@exact:distr/component/buttons/css/buttons.css'],
            ]],
        ];
        $contractJson = json_encode($contract, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $lock = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'), true, 512, JSON_THROW_ON_ERROR);
        $lock['runtime']['framework_registry']['documentation_source'] = [
            'schema' => 'docara.documentation_source.v1',
            'relative_path' => 'contract/contracts/generated/documentation-source.json',
            'file_sha256' => hash('sha256', $contractJson),
        ];
        $this->createSource([
            'docara.json' => json_encode([
                'schema' => 'docara.site.v1', 'preset' => 'docs', 'framework_lock' => 'simai-framework.lock.json',
                'default_locale' => 'ru', 'locales' => [
                    'missing_page_policy' => 'skip',
                    'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => 'ru'],
                ],
                'locale_routing' => ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false],
                'documentation_tracking' => [
                    'enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'documentation.lock.json',
                    'sources' => [['id' => 'simai-framework', 'provider' => 'simai_framework', 'framework_lock' => 'simai-framework.lock.json']],
                ],
            ], JSON_THROW_ON_ERROR),
            'simai-framework.lock.json' => json_encode($lock, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'contract/contracts/generated/documentation-source.json' => $contractJson,
            'content/ru/index.md' => "# Framework\n",
            'content/ru/lang.json' => '{}',
        ]);

        $source = (new DocumentationSourceRepository)->source($this->tmp, 'simai-framework');
        self::assertFalse($source['compatibility_adapter']);
        self::assertSame('exact-contract-1', $source['revision']);
        self::assertSame(['sf-button'], $source['entities'][0]['public_contract']['classes']);
    }

    private function project(): void
    {
        $this->createSource([
            'docara.json' => json_encode([
                'schema' => 'docara.site.v1', 'preset' => 'docs', 'framework_lock' => 'framework.lock.json',
                'default_locale' => 'ru', 'locales' => [
                    'missing_page_policy' => 'skip',
                    'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => 'ru'],
                ],
                'locale_routing' => ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false],
                'documentation_tracking' => [
                    'enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'documentation.lock.json',
                    'sources' => [['id' => 'product', 'provider' => 'contract_json', 'file' => 'documentation-source.json']],
                ],
            ], JSON_THROW_ON_ERROR),
            'documentation-source.json' => json_encode([
                'schema' => 'docara.documentation_source.v1', 'id' => 'product', 'provider' => 'contract_json', 'revision' => '1.0.0',
                'entities' => [[
                    'key' => 'component.buttons', 'kind' => 'component', 'title' => 'Buttons',
                    'public_contract' => ['variants' => ['primary', 'secondary']], 'example_cases' => ['default'],
                    'provenance' => ['src/components/buttons.css'],
                    'scaffold' => ['summary' => 'Generated from the public contract.', 'sections' => ['Variants'], 'examples' => [
                        'default' => ['index.html' => '<button class="button">Button</button>'],
                    ]],
                ]],
            ], JSON_THROW_ON_ERROR),
            'content/ru/components/buttons.md' => "# Кнопки\n\nКраткое описание.\n",
            'content/ru/lang.json' => '{}',
            'examples/components/buttons/default/index.html' => '<button class="button">Кнопка</button>',
        ]);
    }

    /** @return array<string,mixed> */
    private function item(array $report, string $key): array
    {
        $items = array_values(array_filter($report['items'], static fn (array $item): bool => $item['key'] === $key));
        self::assertCount(1, $items);

        return $items[0];
    }

    private function assertSourceRejected(): void
    {
        try {
            (new DocumentationSourceRepository)->all($this->tmp);
            self::fail('An unsafe documentation source was accepted.');
        } catch (PortableConfigurationException) {
            self::assertTrue(true);
        }
    }

    private function assertPlanStale(DocumentationStatusService $service, string $planId): void
    {
        try {
            $service->apply($this->tmp, $planId);
            self::fail('A stale documentation acceptance plan was applied.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DOCUMENTATION_PLAN_STALE', $exception->errorCode);
        }
    }
}
