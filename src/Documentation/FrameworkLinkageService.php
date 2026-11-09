<?php

declare(strict_types=1);

namespace Simai\Docara\Documentation;

use Simai\Docara\Application\ProjectRuntime;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

/**
 * Framework linkage tracking (docs/developer/framework-linkage-tracking.md),
 * phase 1: read only. For every documentation source with a `linkage`
 * object it reads the unit inventories, change reports and change journal the
 * site keeps (standard simai.linkage 1.0.0), builds the site's usage record
 * from documentation.lock.json -- one place per accepted page, its entity key
 * as the unit -- unless the source names a hand-kept one, and reconciles it.
 * Nothing is written to the project.
 */
final class FrameworkLinkageService
{
    public const SCHEMA = 'docara.framework_linkage_status.v1';

    public function __construct(
        private readonly DocumentationSourceRepository $files = new DocumentationSourceRepository,
        private readonly FrameworkLinkageReconciler $reconciler = new FrameworkLinkageReconciler,
    ) {}

    /** Whether any documentation source of the site configures linkage. @param array<string,mixed> $site */
    public static function configured(array $site): bool
    {
        $tracking = $site['documentation_tracking'] ?? null;
        if (! is_array($tracking) || ($tracking['enabled'] ?? false) !== true) {
            return false;
        }
        foreach ($tracking['sources'] ?? [] as $source) {
            if (is_array($source['linkage'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,mixed> */
    public function report(string $root, ?string $sourceFilter = null, ?string $today = null): array
    {
        $runtime = ProjectRuntime::load($root);
        if (! self::configured($runtime->site)) {
            throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_NOT_CONFIGURED', 'No documentation_tracking source configures linkage.');
        }
        $tracking = $runtime->site['documentation_tracking'];
        $lock = $this->lock($runtime->root, (string) $tracking['lock_file']);
        $sources = [];
        foreach ($tracking['sources'] as $configuration) {
            if (! is_array($configuration['linkage'] ?? null) || ($sourceFilter !== null && $configuration['id'] !== $sourceFilter)) {
                continue;
            }
            $sources[] = $this->source($runtime->root, $configuration, $lock, $today);
        }
        if ($sourceFilter !== null && $sources === []) {
            throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_NOT_CONFIGURED', "Documentation source [$sourceFilter] does not configure linkage.");
        }
        $summary = array_fill_keys(FrameworkLinkageReconciler::STATUSES, 0);
        foreach ($sources as $source) {
            foreach ($source['report']['totals']['places'] as $status => $count) {
                $summary[$status] += $count;
            }
        }
        $report = [
            'schema' => self::SCHEMA,
            'status' => FrameworkLinkageReconciler::worst(array_map(static fn (array $source): string => $source['report']['status'], $sources)),
            'summary' => ['places' => $summary, 'unmatched' => array_sum(array_map(static fn (array $source): int => count($source['unmatched']), $sources))],
            'sources' => $sources,
        ];
        $report['content_sha256'] = hash('sha256', CanonicalJson::encode($report));

        return $report;
    }

    /**
     * The report for a person: per source the totals, then every place that
     * is not current with its units, reasons and quoted journal instructions.
     * $limit caps the places listed (the build prints a few).
     *
     * @param  array<string,mixed>  $report
     * @return list<string>
     */
    public static function lines(array $report, ?int $limit = null): array
    {
        $lines = [];
        $listed = 0;
        $hidden = 0;
        foreach ($report['sources'] as $source) {
            $reconcile = $source['report'];
            $counts = [];
            foreach ($reconcile['totals']['places'] as $status => $count) {
                if ($count > 0) {
                    $counts[] = "$count $status";
                }
            }
            $lines[] = sprintf(
                'Framework linkage of %s against %s%s: %d page(s): %s.',
                $source['source'],
                $source['inventory']['release'] ?? substr((string) $source['inventory']['revision'], 0, 12),
                $source['since'] === null ? '' : ' since ' . ($source['since']['release'] ?? substr((string) $source['since']['revision'], 0, 12)),
                count($reconcile['places']),
                $counts === [] ? 'none' : implode(', ', $counts),
            );
            foreach ($source['diagnostics'] as $diagnostic) {
                $lines[] = "  {$diagnostic['code']}: {$diagnostic['message']}";
            }
            if ($source['unmatched'] !== []) {
                $lines[] = sprintf('  %d accepted page(s) document no unit of the baseline: %s.', count($source['unmatched']), implode(', ', array_column($source['unmatched'], 'key')));
            }
            $places = $reconcile['places'];
            usort($places, static fn (array $a, array $b): int => array_search($b['status'], FrameworkLinkageReconciler::STATUSES, true) <=> array_search($a['status'], FrameworkLinkageReconciler::STATUSES, true));
            foreach ($places as $place) {
                if ($place['status'] === 'current') {
                    continue;
                }
                if ($limit !== null && $listed >= $limit) {
                    $hidden++;

                    continue;
                }
                $listed++;
                $lines[] = "- [{$place['status']}] {$place['place']}" . (($place['note'] ?? '') !== '' ? " ({$place['note']})" : '');
                foreach ($place['units'] as $unit) {
                    if ($unit['status'] === 'current') {
                        continue;
                    }
                    $now = isset($unit['current']) ? ($unit['current']['id'] === $unit['id'] ? '' : $unit['current']['id'] . ' ') . $unit['current']['version'] : 'gone';
                    $lines[] = "    {$unit['id']} {$unit['version']} -> $now: {$unit['status']}" . (isset($unit['foundations']) ? ' (foundations unchecked)' : '');
                    foreach ($unit['reasons'] ?? [] as $reason) {
                        $lines[] = "      $reason";
                    }
                    if (isset($unit['former'])) {
                        $lines[] = "      journal {$unit['former']['journal']}: \"{$unit['former']['instruction']}\"";
                    }
                    foreach ([...($unit['journal'] ?? []), ...array_merge(...array_map(static fn (array $via): array => $via['journal'] ?? [], $unit['via'] ?? []))] as $entry) {
                        $lines[] = "      journal {$entry['id']} ({$entry['recorded_on']}): \"{$entry['instruction']}\"";
                    }
                }
            }
            foreach ($reconcile['notes'] as $note) {
                $lines[] = "  Note: $note";
            }
        }
        if ($hidden > 0) {
            $lines[] = "... and $hidden more page(s); run \"docara documentation linkage\" for the full list.";
        }

        return $lines;
    }

    /**
     * @param  array<string,mixed>  $configuration
     * @param  array<string,mixed>  $lock
     * @return array<string,mixed>
     */
    private function source(string $root, array $configuration, array $lock, ?string $today): array
    {
        $linkage = $configuration['linkage'];
        $id = (string) $configuration['id'];
        $inventory = $this->inventory($root, (string) $linkage['inventory']);
        $since = isset($linkage['since']) ? $this->inventory($root, (string) $linkage['since']) : null;
        $changes = [];
        foreach ($linkage['changes'] ?? [] as $path) {
            $changes[] = FrameworkLinkageReconciler::checkChangeReport(
                $this->files->decode($root, (string) $path, 'DOCUMENTATION_LINKAGE_CHANGES_INVALID'),
                (string) $path,
            );
        }
        $journal = isset($linkage['journal'])
            ? FrameworkLinkageReconciler::checkJournal($this->files->decode($root, (string) $linkage['journal'], 'DOCUMENTATION_LINKAGE_JOURNAL_INVALID'), (string) $linkage['journal'])
            : null;

        $diagnostics = [];
        if (($configuration['provider'] ?? null) === 'simai_framework') {
            $frameworkLock = $this->files->decode($root, (string) $configuration['framework_lock'], 'DOCUMENTATION_FRAMEWORK_LOCK_INVALID');
            $pinned = $frameworkLock['runtime']['pair_id'] ?? null;
            if (is_string($pinned) && $pinned !== ($inventory['release'] ?? null)) {
                $diagnostics[] = [
                    'code' => 'DOCUMENTATION_LINKAGE_RELEASE_MISMATCH',
                    'severity' => 'warning',
                    'source' => $id,
                    'message' => sprintf('The linkage inventory is %s but %s pins %s.', $inventory['release'] ?? 'unnamed', $configuration['framework_lock'], $pinned),
                ];
            }
        }

        $unmatched = [];
        if (isset($linkage['usage'])) {
            $usage = $this->files->decode($root, (string) $linkage['usage'], 'DOCUMENTATION_LINKAGE_USAGE_INVALID');
            $problems = FrameworkLinkageReconciler::checkRecord($usage);
            if ($problems !== []) {
                throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_USAGE_INVALID', "Usage record [{$linkage['usage']}] is not valid:\n  " . implode("\n  ", $problems));
            }
        } else {
            if ($since === null) {
                throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_BASELINE_REQUIRED', "Documentation source [$id] needs linkage.since (the inventory the pages were brought up to) or linkage.usage.");
            }
            [$usage, $unmatched] = $this->derivedUsage($id, $since, $lock, $linkage['brought_up_on'] ?? null);
        }

        return [
            'source' => $id,
            'inventory' => ['source' => $inventory['source'], 'release' => $inventory['release'] ?? null, 'revision' => $inventory['revision'] ?? null],
            'since' => $since === null ? null : ['source' => $since['source'], 'release' => $since['release'] ?? null, 'revision' => $since['revision'] ?? null],
            'diagnostics' => $diagnostics,
            'unmatched' => $unmatched,
            'usage' => $usage,
            'report' => $this->reconciler->reconcile($usage, [$inventory], $since === null ? [] : [$since], $changes, $journal, $today),
        ];
    }

    /**
     * The site's usage record (simai.framework.unit-usage v1): one place per
     * page the lock binds to a unit of the baseline inventory, with the
     * baseline's version and hash. Keys that are no unit there are returned
     * apart, never guessed.
     *
     * @param  array<string,mixed>  $since
     * @param  array<string,mixed>  $lock
     * @return array{0:array<string,mixed>,1:list<array{key:string,route:string}>}
     */
    private function derivedUsage(string $sourceId, array $since, array $lock, mixed $broughtUpOn): array
    {
        $units = [];
        foreach ($since['units'] as $unit) {
            $units[$unit['id']] = $unit;
        }
        $places = [];
        $unmatched = [];
        foreach ($lock['entries'] as $entry) {
            if ($entry['source'] !== $sourceId) {
                continue;
            }
            $unit = $units[$entry['key']] ?? null;
            if ($unit === null) {
                $unmatched[] = ['key' => $entry['key'], 'route' => $entry['route']];

                continue;
            }
            $places[] = [
                'place' => 'docs:' . $entry['route'],
                'note' => $entry['page_path'],
                'units' => [['id' => $unit['id'], 'version' => $unit['version'], 'content_sha256' => $unit['content_sha256']]],
            ];
        }
        usort($places, static fn (array $a, array $b): int => strcmp($a['place'], $b['place']));
        $source = ['source' => $since['source'], 'release' => $since['release'] ?? null];
        if (isset($since['revision'])) {
            $source['revision'] = $since['revision'];
        }
        if (is_string($broughtUpOn) && $broughtUpOn !== '') {
            $source['brought_up_on'] = $broughtUpOn;
        }

        return [[
            'schema' => FrameworkLinkageReconciler::USAGE_SCHEMA,
            'schema_version' => 1,
            'consumer' => ['id' => $sourceId, 'kind' => 'docs'],
            'sources' => [$source],
            'places' => $places,
        ], $unmatched];
    }

    /** @return array<string,mixed> */
    private function inventory(string $root, string $path): array
    {
        return FrameworkLinkageReconciler::checkInventory($this->files->decode($root, $path, 'DOCUMENTATION_LINKAGE_INVENTORY_INVALID'), $path);
    }

    /** @return array<string,mixed> */
    private function lock(string $root, string $relative): array
    {
        if (! is_file($root . '/' . $relative)) {
            return ['entries' => []];
        }
        $lock = $this->files->decode($root, $relative, 'DOCUMENTATION_LOCK_INVALID');
        (new SchemaRepository)->assertValid($lock, 'documentation-lock.schema.json');

        return $lock;
    }
}
