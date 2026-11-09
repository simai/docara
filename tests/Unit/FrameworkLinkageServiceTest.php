<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\DocumentationCommand;
use Simai\Docara\Documentation\DocumentationStatusService;
use Simai\Docara\Documentation\FrameworkLinkageService;
use Simai\Docara\Portable\PortableConfigurationException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

/**
 * Framework linkage tracking, phase 1 (docs/developer/framework-linkage-tracking.md):
 * a site whose documentation lock binds four pages to units, with the real
 * ui-cb287da78ab1 -> ui-b4a77681f0a7 excerpt in tests/fixtures/framework-linkage/real.
 */
final class FrameworkLinkageServiceTest extends TestCase
{
    private const REAL = __DIR__ . '/../fixtures/framework-linkage/real/';

    #[Test]
    public function the_record_derived_from_the_documentation_lock_reconciles_like_the_framework(): void
    {
        $this->project();
        $report = (new FrameworkLinkageService)->report($this->tmp, null, '2026-11-09');

        self::assertSame('docara.framework_linkage_status.v1', $report['schema']);
        self::assertSame('action-needed', $report['status']);
        self::assertSame(['current' => 1, 'update-available' => 1, 'former-name' => 0, 'action-needed' => 2, 'missing' => 0], $report['summary']['places']);
        $source = $report['sources'][0];
        self::assertSame('ui-b4a77681f0a7', $source['inventory']['release']);
        self::assertSame('ui-cb287da78ab1', $source['since']['release']);
        self::assertSame([['key' => 'core.design-tokens', 'route' => '/ru/tokens/']], $source['unmatched']);
        self::assertSame('docs', $source['usage']['consumer']['kind']);
        // The very report the Framework's reconcile.mjs wrote for the same record.
        self::assertEquals(json_decode((string) file_get_contents(self::REAL . 'expected-report.json'), true, 512, JSON_THROW_ON_ERROR), $source['report']);

        $lines = FrameworkLinkageService::lines($report, 1);
        self::assertStringContainsString('2 action-needed', $lines[0]);
        self::assertStringContainsString('core.design-tokens', implode("\n", $lines));
        self::assertStringContainsString('"Delete the variable: a loading button', implode("\n", $lines));
        self::assertStringStartsWith('... and 2 more page(s)', $lines[array_key_last($lines)]);
    }

    #[Test]
    public function the_cli_prints_the_report_and_its_json(): void
    {
        $this->project();
        $tester = $this->command();
        self::assertSame(0, $tester->execute(['action' => 'linkage', '--today' => '2026-11-09']), $tester->getDisplay());
        self::assertStringContainsString('[action-needed] docs:/ru/smart/tag/', $tester->getDisplay());
        self::assertStringContainsString('journal path-smart-tags-to-tag (2026-10-27)', $tester->getDisplay());

        self::assertSame(0, $tester->execute(['action' => 'linkage', '--json' => true, '--today' => '2026-11-09']));
        $report = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('simai.framework.unit-reconcile-report', $report['sources'][0]['report']['schema']);

        self::assertSame(1, $tester->execute(['action' => 'linkage', '--today' => '9 Nov']));
    }

    #[Test]
    public function missing_baseline_unconfigured_sites_and_corrupt_inputs_fail_closed(): void
    {
        $this->project(['since' => null]);
        $this->assertCode('DOCUMENTATION_LINKAGE_BASELINE_REQUIRED');

        $this->project(['inventory' => 'contracts/linkage/journal.json']);
        $this->assertCode('DOCUMENTATION_LINKAGE_INVENTORY_INVALID');

        $this->project(['changes' => ['contracts/linkage/ui-b4a77681f0a7.inventory.json']]);
        $this->assertCode('DOCUMENTATION_LINKAGE_CHANGES_INVALID');

        $this->project(['journal' => 'contracts/linkage/ui-b4a77681f0a7.changes.json']);
        $this->assertCode('DOCUMENTATION_LINKAGE_JOURNAL_INVALID');

        $this->project(['usage' => 'contracts/linkage/journal.json']);
        $this->assertCode('DOCUMENTATION_LINKAGE_USAGE_INVALID');

        $this->project(['inventory' => '../outside.json']);
        $this->assertCode('DOCUMENTATION_LINKAGE_INVENTORY_INVALID');

        $this->project(false);
        self::assertFalse(FrameworkLinkageService::configured(json_decode((string) file_get_contents($this->tmpPath('docara.json')), true)));
        $this->assertCode('DOCUMENTATION_LINKAGE_NOT_CONFIGURED');
        self::assertSame(1, $this->command()->execute(['action' => 'linkage']));
    }

    #[Test]
    public function a_hand_kept_usage_record_replaces_the_derived_one(): void
    {
        $this->project(['since' => null, 'usage' => 'framework-usage.json']);
        file_put_contents($this->tmpPath('framework-usage.json'), json_encode([
            'schema' => 'simai.framework.unit-usage', 'schema_version' => 1,
            'consumer' => ['id' => 'site', 'kind' => 'docs'],
            'sources' => [['source' => 'simai/ui-source', 'release' => 'ui-b4a77681f0a7']],
            'places' => [['place' => 'docs:/ru/', 'units' => [
                ['id' => 'smart.tag', 'version' => '1.3.1', 'content_sha256' => $this->unit('smart.tag')['content_sha256']],
                ['id' => 'smart.tags', 'version' => '1.2.1', 'content_sha256' => str_repeat('a', 64)],
            ]]],
        ], JSON_THROW_ON_ERROR));
        $report = (new FrameworkLinkageService)->report($this->tmp, 'simai-framework', '2026-11-09');
        $units = $report['sources'][0]['report']['places'][0]['units'];

        self::assertSame('current', $units[0]['status']);
        self::assertSame('former-name', $units[1]['status']);
        self::assertSame('smart.tag', $units[1]['former']['to']);
        self::assertSame([], $report['sources'][0]['unmatched']);
    }

    /** @param array<string,mixed>|false $linkage overrides of the linkage object, or false for none */
    private function project(array|false $linkage = []): void
    {
        $this->filesystem->deleteDirectory($this->tmp);
        $this->filesystem->ensureDirectoryExists($this->tmp);
        $configuration = ['id' => 'simai-framework', 'provider' => 'contract_json', 'file' => 'documentation-source.json'];
        if ($linkage !== false) {
            $configuration['linkage'] = array_filter($linkage + [
                'inventory' => 'contracts/linkage/ui-b4a77681f0a7.inventory.json',
                'since' => 'contracts/linkage/ui-cb287da78ab1.inventory.json',
                'journal' => 'contracts/linkage/journal.json',
                'brought_up_on' => '2026-10-15',
            ], static fn (mixed $value): bool => $value !== null);
        }
        $entities = [];
        $entries = [];
        foreach (['component.accordion' => 'components/accordion', 'smart.button' => 'smart/button', 'smart.tag' => 'smart/tag', 'utility.gap' => 'utilities/gap', 'core.design-tokens' => 'tokens'] as $key => $slug) {
            $entities[] = ['key' => $key, 'kind' => explode('.', $key)[0], 'title' => $key, 'public_contract' => ['key' => $key], 'example_cases' => [], 'provenance' => ['src']];
            $entries[] = [
                'source' => 'simai-framework', 'key' => $key, 'kind' => explode('.', $key)[0], 'route' => "/ru/$slug/", 'page_path' => "content/ru/$slug.md",
                'examples' => [], 'source_sha256' => str_repeat('0', 64), 'page_sha256' => str_repeat('1', 64), 'examples_sha256' => str_repeat('2', 64), 'review' => 'ai_verified',
            ];
        }
        $files = [
            'docara.json' => json_encode([
                'schema' => 'docara.site.v1', 'preset' => 'docs', 'framework_lock' => 'framework.lock.json',
                'default_locale' => 'ru', 'locales' => [
                    'missing_page_policy' => 'skip',
                    'ru' => ['label' => 'Русский', 'direction' => 'ltr', 'content_root' => 'content/ru', 'public_prefix' => 'ru'],
                ],
                'locale_routing' => ['strategy' => 'prefixed', 'root' => 'redirect', 'detect_browser_language' => false],
                'documentation_tracking' => [
                    'enabled' => true, 'source_locale' => 'ru', 'mode' => 'report', 'lock_file' => 'documentation.lock.json',
                    'sources' => [$configuration],
                ],
            ], JSON_THROW_ON_ERROR),
            'documentation-source.json' => json_encode([
                'schema' => 'docara.documentation_source.v1', 'id' => 'simai-framework', 'provider' => 'contract_json', 'revision' => '1', 'entities' => $entities,
            ], JSON_THROW_ON_ERROR),
            'documentation.lock.json' => json_encode(['schema' => 'docara.documentation_lock.v1', 'source_locale' => 'ru', 'entries' => $entries, 'exclusions' => []], JSON_THROW_ON_ERROR),
            'content/ru/lang.json' => '{}',
        ];
        foreach (['ui-cb287da78ab1.inventory.json', 'ui-b4a77681f0a7.inventory.json', 'ui-b4a77681f0a7.changes.json', 'journal.json'] as $file) {
            $files['contracts/linkage/' . $file] = (string) file_get_contents(self::REAL . $file);
        }
        $this->createSource($files);
    }

    /** @return array<string,mixed> */
    private function unit(string $id): array
    {
        $inventory = json_decode((string) file_get_contents(self::REAL . 'ui-b4a77681f0a7.inventory.json'), true, 512, JSON_THROW_ON_ERROR);

        return array_values(array_filter($inventory['units'], static fn (array $unit): bool => $unit['id'] === $id))[0];
    }

    private function command(): CommandTester
    {
        $application = new Application;
        $application->addCommand((new DocumentationCommand(new DocumentationStatusService))->setBase($this->tmp));

        return new CommandTester($application->find('documentation'));
    }

    private function assertCode(string $code): void
    {
        try {
            (new FrameworkLinkageService)->report($this->tmp, null, '2026-11-09');
            self::fail("Expected $code.");
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode, $exception->getMessage());
        }
    }
}
