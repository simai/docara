<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Documentation\FrameworkLinkageReconciler;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Parity of Docara's reconcile with the Framework's (simai/ui-builder
 * scripts/reconcile.mjs). tests/fixtures/framework-linkage/conformance holds
 * ui-builder's own fixtures (tests/linkage/fixtures, usage-dated.json adds
 * brought_up_on) and the reports reconcile.mjs wrote for them with --json;
 * real/ holds an excerpt of the real ui-cb287da78ab1 -> ui-b4a77681f0a7
 * inventories, change report and three ui-source journal records, with the
 * report reconcile.mjs wrote for a four-page docs record. Regenerate the
 * expected-*.json files with reconcile.mjs when the Framework changes it.
 */
final class FrameworkLinkageReconcilerTest extends TestCase
{
    private const CONFORMANCE = __DIR__ . '/../fixtures/framework-linkage/conformance/';

    /** @return iterable<string,array{0:string,1:string,2:list<string>,3:list<string>,4:?string,5:string,6:string}> */
    public static function goldenReports(): iterable
    {
        yield 'since inventory and journal' => ['usage.json', 'ui-bbbbbbbbbbbb.inventory.json', ['ui-aaaaaaaaaaaa.inventory.json'], [], 'journal.json', '2026-11-09', 'expected-since.json'];
        yield 'change report and dated record' => ['usage-dated.json', 'ui-bbbbbbbbbbbb.inventory.json', [], ['ui-bbbbbbbbbbbb.changes.json'], 'journal.json', '2026-11-09', 'expected-changes.json'];
        yield 'nothing but the inventory' => ['usage.json', 'ui-bbbbbbbbbbbb.inventory.json', [], [], null, '2026-11-09', 'expected-bare.json'];
        yield 'one foundation moved, a former name expired' => ['usage.json', 'ui-cccccccccccc.inventory.json', ['ui-aaaaaaaaaaaa.inventory.json'], [], 'journal.json', '2026-11-30', 'expected-colour.json'];
    }

    /** @param list<string> $since @param list<string> $changes */
    #[Test]
    #[DataProvider('goldenReports')]
    public function it_gives_the_report_the_framework_reconcile_gives(string $usage, string $inventory, array $since, array $changes, ?string $journal, string $today, string $expected): void
    {
        $report = (new FrameworkLinkageReconciler)->reconcile(
            $this->json($usage),
            [$this->json($inventory)],
            array_map($this->json(...), $since),
            array_map($this->json(...), $changes),
            $journal === null ? null : $this->json($journal),
            $today,
        );

        self::assertEquals($this->json($expected), $report);
    }

    #[Test]
    public function it_quotes_the_journal_and_follows_foundations_on_real_release_data(): void
    {
        $real = __DIR__ . '/../fixtures/framework-linkage/real/';
        $expected = $this->json($real . 'expected-report.json');
        $report = (new FrameworkLinkageReconciler)->reconcile(
            $this->recordOf($expected),
            [$this->json($real . 'ui-b4a77681f0a7.inventory.json')],
            [$this->json($real . 'ui-cb287da78ab1.inventory.json')],
            [],
            $this->json($real . 'journal.json'),
            '2026-11-09',
        );

        self::assertEquals($expected, $report);
        $button = $this->place($report, 'docs:/ru/smart/button/')['units'][0];
        self::assertSame('action-needed', $button['status']);
        self::assertSame(['token-button-loading-stripe', 'path-smart-buttons-to-button'], array_column($button['journal'], 'id'));
        self::assertStringStartsWith('Delete the variable: a loading button shows the spinner', $button['journal'][0]['instruction']);
        $accordion = $this->place($report, 'docs:/ru/components/accordion/')['units'][0];
        self::assertSame('update-available', $accordion['status']);
        self::assertSame(['tokens.colour', 'tokens.elevation', 'tokens.space', 'tokens.typography'], array_column($accordion['via'], 'id'));
        self::assertSame(['component.accordion', 'tokens.colour', 'tokens.elevation'], $accordion['via'][1]['chain']);
    }

    #[Test]
    public function an_invalid_record_or_inventory_fails_closed(): void
    {
        $record = $this->json('usage.json');
        $record['places'][0]['units'][0]['content_sha256'] = 'not-a-hash';
        $record['extra'] = true;
        self::assertSame([
            'extra: is not a field of the record',
            'places[0].units[0].content_sha256: must be 64 lowercase hex digits',
        ], FrameworkLinkageReconciler::checkRecord($record));
        try {
            (new FrameworkLinkageReconciler)->reconcile($record, [$this->json('ui-bbbbbbbbbbbb.inventory.json')]);
            self::fail('An invalid usage record was reconciled.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('DOCUMENTATION_LINKAGE_USAGE_INVALID', $exception->errorCode);
        }

        $inventory = $this->json('ui-bbbbbbbbbbbb.inventory.json');
        $inventory['units'][0]['version'] = '1.0';
        $this->assertCode('DOCUMENTATION_LINKAGE_INVENTORY_INVALID', static fn () => FrameworkLinkageReconciler::checkInventory($inventory, 'x.json'));
        $this->assertCode('DOCUMENTATION_LINKAGE_INVENTORY_INVALID', static fn () => FrameworkLinkageReconciler::checkInventory(['schema' => 'simai.framework.unit-inventory', 'schema_version' => 2, 'source' => 's', 'units' => []], 'x.json'));
        $this->assertCode('DOCUMENTATION_LINKAGE_CHANGES_INVALID', static fn () => FrameworkLinkageReconciler::checkChangeReport(['schema' => 'simai.framework.unit-inventory'], 'x.json'));
        $this->assertCode('DOCUMENTATION_LINKAGE_JOURNAL_INVALID', static fn () => FrameworkLinkageReconciler::checkJournal(['schema' => 'simai.change-journal', 'records' => ['a' => []]], 'x.json'));
        $this->assertCode('DOCUMENTATION_LINKAGE_INVENTORY_INVALID', fn () => (new FrameworkLinkageReconciler)->reconcile($this->json('usage.json'), []));
    }

    #[Test]
    public function versions_step_as_a_caret_range_reads_them(): void
    {
        self::assertSame('major', FrameworkLinkageReconciler::versionStep('1.4.0', '2.0.0'));
        self::assertSame('major', FrameworkLinkageReconciler::versionStep('0.1.0', '0.2.0'));
        self::assertSame('minor', FrameworkLinkageReconciler::versionStep('1.9.0', '1.10.0'));
        self::assertSame('patch', FrameworkLinkageReconciler::versionStep('1.0.3', '1.0.4'));
        self::assertSame('none', FrameworkLinkageReconciler::versionStep('1.0.0', '1.0.0'));
        self::assertSame(1, FrameworkLinkageReconciler::compareVersions('1.10.0', '1.9.0'));
        self::assertSame('action-needed', FrameworkLinkageReconciler::worst(['current', 'action-needed', 'update-available']));
        self::assertSame('current', FrameworkLinkageReconciler::worst([]));
    }

    /** The usage record the real expected report was made from: its sources and places. @param array<string,mixed> $report @return array<string,mixed> */
    private function recordOf(array $report): array
    {
        $places = [];
        foreach ($report['places'] as $place) {
            $places[] = ['place' => $place['place'], 'note' => $place['note'], 'units' => array_map(
                static fn (array $unit): array => ['id' => $unit['id'], 'version' => $unit['version'], 'content_sha256' => $unit['content_sha256']],
                $place['units'],
            )];
        }

        return ['schema' => 'simai.framework.unit-usage', 'schema_version' => 1, 'consumer' => $report['consumer'], 'sources' => $report['record_sources'], 'places' => $places];
    }

    /** @param array<string,mixed> $report @return array<string,mixed> */
    private function place(array $report, string $locator): array
    {
        foreach ($report['places'] as $place) {
            if ($place['place'] === $locator) {
                return $place;
            }
        }
        self::fail("No place $locator.");
    }

    private function assertCode(string $code, callable $call): void
    {
        try {
            $call();
            self::fail("Expected $code.");
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode);
        }
    }

    /** @return array<string,mixed> */
    private function json(string $file): array
    {
        $path = str_starts_with($file, '/') ? $file : self::CONFORMANCE . $file;

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
