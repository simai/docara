<?php

declare(strict_types=1);

namespace Simai\Docara\Documentation;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The reconcile of standard simai.linkage 1.0.0 (requirements 11-16), a PHP
 * twin of simai/ui-builder scripts/reconcile.mjs: a usage record
 * (simai.framework.unit-usage v1) against one or more unit inventories gives
 * every unit of every place one status -- current, update-available,
 * former-name, action-needed, missing -- with the change journal's
 * instruction quoted and the foundations (tokens, fonts, icons) followed.
 *
 * The output is a simai.framework.unit-reconcile-report v1. Parity with the
 * Framework's command is held by tests/Unit/FrameworkLinkageReconcilerTest.php
 * on golden reports reconcile.mjs wrote for its own fixtures.
 */
final class FrameworkLinkageReconciler
{
    public const USAGE_SCHEMA = 'simai.framework.unit-usage';

    public const REPORT_SCHEMA = 'simai.framework.unit-reconcile-report';

    public const INVENTORY_SCHEMA = 'simai.framework.unit-inventory';

    public const CHANGE_REPORT_SCHEMA = 'simai.framework.unit-change-report';

    public const JOURNAL_SCHEMA = 'simai.change-journal';

    public const CONSUMER_KINDS = ['figma-kit', 'docs', 'product', 'project', 'example', 'other'];

    /** From the best to the worst; a place takes its worst unit's. */
    public const STATUSES = ['current', 'update-available', 'former-name', 'action-needed', 'missing'];

    public const FOUNDATION_KINDS = ['tokens', 'font', 'icons'];

    /** Shared infrastructure: its dependencies are not a place's foundations. */
    public const INFRASTRUCTURE_KINDS = ['core', 'module'];

    private const ID = '/^[a-z][a-z0-9-]*\.[a-z0-9][a-z0-9.-]*$/D';

    private const VERSION = '/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D';

    private const SHA256 = '/^[a-f0-9]{64}$/D';

    private const RELEASE = '/^[a-z][a-z0-9-]*-[a-f0-9]{12}$/D';

    private const DATE = '/^\d{4}-\d{2}-\d{2}$/D';

    /**
     * @param  array<string,mixed>  $record
     * @param  list<array<string,mixed>>  $inventories
     * @param  list<array<string,mixed>>  $since
     * @param  list<array<string,mixed>>  $changes
     * @param  array<string,mixed>|null  $journal
     * @return array<string,mixed>
     */
    public function reconcile(array $record, array $inventories, array $since = [], array $changes = [], ?array $journal = null, ?string $today = null): array
    {
        $problems = self::checkRecord($record);
        if ($problems !== []) {
            throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_USAGE_INVALID', "The usage record is not valid:\n  " . implode("\n  ", $problems));
        }
        if ($inventories === []) {
            throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_INVENTORY_INVALID', 'Give at least one unit inventory.');
        }
        $today ??= gmdate('Y-m-d');
        $targets = array_map(self::indexed(...), $inventories);
        $before = array_map(self::indexed(...), $since);
        $journalIndex = $journal === null ? null : self::indexJournal($journal);
        $recordSources = [];
        foreach ($record['sources'] as $source) {
            $recordSources[$source['source']] = $source;
        }
        $context = [
            'targets' => $targets, 'before' => $before, 'changes' => $changes, 'journal' => $journalIndex,
            'today' => $today, 'sources' => $recordSources,
        ];

        $notes = [];
        foreach ($targets as $inventory) {
            $recorded = $recordSources[$inventory['source']] ?? null;
            if ($recorded === null) {
                $notes[] = "the record lists no source {$inventory['source']}; its units are matched by id";
            }
            if ($this->foundationContext($inventory, $context) === null) {
                $notes[] = "foundations of {$inventory['source']} are unchecked: give --since <inventory of " . ($recorded['release'] ?? "the record's release") . '> or --changes <reports>';
            }
        }
        if ($journalIndex === null) {
            $notes[] = 'no change journal given: former names are reported missing and no instruction is quoted (--journal)';
        } elseif (! array_filter($record['sources'], static fn (array $source): bool => ($source['brought_up_on'] ?? null) !== null && $source['brought_up_on'] !== '')) {
            $notes[] = 'journal entries without a unit version are placed by date: give sources[].brought_up_on to include them';
        }

        $totals = array_fill_keys(self::STATUSES, 0);
        $placeTotals = array_fill_keys(self::STATUSES, 0);
        $places = [];
        foreach ($record['places'] as $place) {
            $units = [];
            foreach ($place['units'] as $unit) {
                $result = $this->reconcileUnit($unit, $context);
                $totals[$result['status']]++;
                $units[] = $result;
            }
            $status = self::worst(array_column($units, 'status'));
            $placeTotals[$status]++;
            $entry = ['place' => $place['place'], 'status' => $status];
            if (($place['note'] ?? '') !== '') {
                $entry['note'] = $place['note'];
            }
            $entry['units'] = $units;
            $places[] = $entry;
        }

        return [
            'schema' => self::REPORT_SCHEMA,
            'schema_version' => 1,
            'checked_on' => $today,
            'consumer' => ['id' => $record['consumer']['id'], 'kind' => $record['consumer']['kind']],
            'record_sources' => $record['sources'],
            'inventories' => array_map(self::identity(...), $targets),
            'since' => array_map(self::identity(...), $before),
            'journal' => $journalIndex === null ? null : ['records' => count($journalIndex['records'])],
            'status' => self::worst(array_column($places, 'status')),
            'totals' => ['places' => $placeTotals, 'units' => $totals],
            'places' => $places,
            'notes' => $notes,
        ];
    }

    /** @param array<string,mixed> $inventory @return array<string,mixed> */
    public static function checkInventory(mixed $inventory, string $location): array
    {
        if (! is_array($inventory) || ($inventory['schema'] ?? null) !== self::INVENTORY_SCHEMA || ($inventory['schema_version'] ?? null) !== 1
            || ! is_string($inventory['source'] ?? null) || ! is_array($inventory['units'] ?? null) || ! array_is_list($inventory['units'])
        ) {
            throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_INVENTORY_INVALID', "$location is not a " . self::INVENTORY_SCHEMA . ' v1 inventory.');
        }
        foreach ($inventory['units'] as $unit) {
            if (! is_array($unit) || ! self::matches(self::ID, $unit['id'] ?? null) || ! self::matches(self::VERSION, $unit['version'] ?? null)
                || ! self::matches(self::SHA256, $unit['content_sha256'] ?? null)
            ) {
                $id = is_array($unit) ? json_encode($unit['id'] ?? null) : 'null';
                throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_INVENTORY_INVALID', "$location: unit $id has no valid id, version or content_sha256.");
            }
        }

        return $inventory;
    }

    /** @return array<string,mixed> */
    public static function checkChangeReport(mixed $report, string $location): array
    {
        if (! is_array($report) || ($report['schema'] ?? null) !== self::CHANGE_REPORT_SCHEMA || ($report['schema_version'] ?? null) !== 1
            || ! is_string($report['source'] ?? null) || ! is_array($report['changed'] ?? null)
        ) {
            throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_CHANGES_INVALID', "$location is not a " . self::CHANGE_REPORT_SCHEMA . ' v1 report.');
        }
        foreach ($report['changed'] as $change) {
            if (! is_array($change) || ! self::matches(self::ID, $change['id'] ?? null)
                || ! self::matches(self::VERSION, $change['from_version'] ?? null) || ! self::matches(self::VERSION, $change['to_version'] ?? null)
            ) {
                throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_CHANGES_INVALID', "$location: a changed unit has no valid id or versions.");
            }
        }

        return $report;
    }

    /** @return array<string,mixed> */
    public static function checkJournal(mixed $journal, string $location): array
    {
        if (! is_array($journal) || ($journal['schema'] ?? null) !== self::JOURNAL_SCHEMA || ! is_array($journal['records'] ?? null) || ! array_is_list($journal['records'])) {
            throw new PortableConfigurationException('DOCUMENTATION_LINKAGE_JOURNAL_INVALID', "$location is not a " . self::JOURNAL_SCHEMA . '.');
        }

        return $journal;
    }

    /**
     * The problems of a usage record against simai.framework.unit-usage v1; empty when it is valid.
     *
     * @return list<string>
     */
    public static function checkRecord(mixed $record): array
    {
        if (! is_array($record) || array_is_list($record) && $record !== []) {
            return ['the record is not a JSON object'];
        }
        $problems = [];
        $at = static function (string $where, string $message) use (&$problems): void {
            $problems[] = "$where: $message";
        };
        $only = static function (mixed $value, string $where, array $keys) use ($at): void {
            foreach (is_array($value) ? array_keys($value) : [] as $key) {
                if (! in_array($key, $keys, true)) {
                    $at($where . $key, 'is not a field of the record');
                }
            }
        };
        $only($record, '', ['$schema', 'schema', 'schema_version', 'consumer', 'sources', 'places']);
        $only($record['consumer'] ?? null, 'consumer.', ['id', 'kind', 'title']);
        if (($record['schema'] ?? null) !== self::USAGE_SCHEMA) {
            $at('schema', 'must be ' . self::USAGE_SCHEMA);
        }
        if (($record['schema_version'] ?? null) !== 1) {
            $at('schema_version', 'must be 1');
        }
        $consumer = $record['consumer'] ?? null;
        if (! is_array($consumer) || ! is_string($consumer['id'] ?? null) || $consumer['id'] === '') {
            $at('consumer.id', 'must be a non-empty string');
        }
        if (! is_array($consumer) || ! in_array($consumer['kind'] ?? null, self::CONSUMER_KINDS, true)) {
            $at('consumer.kind', 'must be one of ' . implode(', ', self::CONSUMER_KINDS));
        }
        $sources = $record['sources'] ?? null;
        if (! is_array($sources) || $sources === [] || ! array_is_list($sources)) {
            $at('sources', 'must list at least one source');
        }
        foreach (is_array($sources) && array_is_list($sources) ? $sources : [] as $index => $source) {
            if (! is_array($source) || ! is_string($source['source'] ?? null) || $source['source'] === '') {
                $at("sources[$index].source", 'must be a non-empty string');
            }
            $only($source, "sources[$index].", ['source', 'release', 'revision', 'brought_up_on']);
            if (! is_array($source) || ! array_key_exists('release', $source)) {
                $at("sources[$index].release", 'must be given (a release id, or null)');
            } elseif ($source['release'] !== null && ! self::matches(self::RELEASE, $source['release'])) {
                $at("sources[$index].release", 'must be a release id such as ui-<12 hex>, or null');
            }
            if (is_array($source) && ($source['brought_up_on'] ?? null) !== null && ! self::matches(self::DATE, $source['brought_up_on'])) {
                $at("sources[$index].brought_up_on", 'must be a date yyyy-mm-dd');
            }
        }
        $places = $record['places'] ?? null;
        if (! is_array($places) || ! array_is_list($places)) {
            $at('places', 'must be an array');
        }
        foreach (is_array($places) && array_is_list($places) ? $places : [] as $index => $place) {
            $only($place, "places[$index].", ['place', 'note', 'units']);
            if (! is_array($place) || ! is_string($place['place'] ?? null) || $place['place'] === '') {
                $at("places[$index].place", 'must be a non-empty locator');
            }
            if (! is_array($place) || ! is_array($place['units'] ?? null) || $place['units'] === [] || ! array_is_list($place['units'])) {
                $at("places[$index].units", 'must list at least one unit');

                continue;
            }
            foreach ($place['units'] as $position => $unit) {
                $where = "places[$index].units[$position]";
                $only($unit, "$where.", ['id', 'version', 'content_sha256', 'source']);
                if (! is_array($unit) || ! self::matches(self::ID, $unit['id'] ?? null)) {
                    $at("$where.id", 'must be a unit id <kind>.<name>');
                }
                if (! is_array($unit) || ! self::matches(self::VERSION, $unit['version'] ?? null)) {
                    $at("$where.version", 'must be major.minor.patch');
                }
                if (! is_array($unit) || ! self::matches(self::SHA256, $unit['content_sha256'] ?? null)) {
                    $at("$where.content_sha256", 'must be 64 lowercase hex digits');
                }
            }
        }

        return $problems;
    }

    public static function compareVersions(string $a, string $b): int
    {
        return array_map('intval', explode('.', $a)) <=> array_map('intval', explode('.', $b));
    }

    /** major, minor, patch or none; below 1.0.0 a minor step is a major one, as a caret range reads it. */
    public static function versionStep(string $from, string $to): string
    {
        $a = array_map('intval', explode('.', $from));
        $b = array_map('intval', explode('.', $to));
        if ($a[0] !== $b[0]) {
            return 'major';
        }
        if ($a[1] !== $b[1]) {
            return $a[0] === 0 ? 'major' : 'minor';
        }

        return $a[2] !== $b[2] ? 'patch' : 'none';
    }

    /** @param list<string> $statuses */
    public static function worst(array $statuses): string
    {
        $worst = 'current';
        foreach ($statuses as $status) {
            if (array_search($status, self::STATUSES, true) > array_search($worst, self::STATUSES, true)) {
                $worst = $status;
            }
        }

        return $worst;
    }

    /** What the entry asks a consumer to do, quoted from the journal. @param array<string,mixed> $record */
    public static function instructionOf(array $record): string
    {
        if (is_string($record['manual'] ?? null) && trim($record['manual']) !== '') {
            return trim($record['manual']);
        }
        $examples = is_array($record['examples'] ?? null) ? $record['examples'] : [];
        $example = ($examples['before'] ?? '') !== '' && ($examples['after'] ?? '') !== ''
            ? " ({$examples['before']} -> {$examples['after']})"
            : '';

        return trim((string) ($record['summary'] ?? $record['id'] ?? '')) . $example;
    }

    /** @param array<string,mixed> $journal @return array{records:list<array<string,mixed>>,former:array<string,array{to:string,record:array<string,mixed>}>,asking:list<array{record:array<string,mixed>,units:list<array{id:string,version:?string}>,tags:array<string,true>}>} */
    private static function indexJournal(array $journal): array
    {
        $records = array_values(array_filter($journal['records'], 'is_array'));
        $former = [];
        $asking = [];
        foreach ($records as $record) {
            foreach (is_array($record['items'] ?? null) ? $record['items'] : [] as $item) {
                if (is_array($item) && self::matches(self::ID, $item['from'] ?? null) && self::matches(self::ID, $item['to'] ?? null) && $item['from'] !== $item['to']) {
                    $former[$item['from']] = ['to' => $item['to'], 'record' => $record];
                }
            }
            if (($record['rewrite'] ?? null) === 'none') {
                continue;
            }
            $asking[] = ['record' => $record, 'units' => self::journalUnits($record), 'tags' => self::journalTags($record)];
        }

        return ['records' => $records, 'former' => $former, 'asking' => $asking];
    }

    /** @param array<string,mixed> $record @return list<array{id:string,version:?string}> */
    private static function journalUnits(array $record): array
    {
        $named = [];
        $add = static function (mixed $id, mixed $version) use (&$named): void {
            if (self::matches(self::ID, $id)) {
                $named[] = ['id' => $id, 'version' => self::matches(self::VERSION, $version) ? $version : null];
            }
        };
        foreach (is_array($record['units'] ?? null) ? $record['units'] : [] as $entry) {
            is_string($entry) ? $add($entry, $record['version'] ?? null) : $add($entry['id'] ?? null, $entry['version'] ?? $record['version'] ?? null);
        }
        if (is_string($record['unit'] ?? null)) {
            $add($record['unit'], $record['version'] ?? null);
        }
        foreach (is_array($record['items'] ?? null) ? $record['items'] : [] as $item) {
            if (is_array($item) && is_string($item['unit'] ?? null)) {
                $add($item['unit'], $item['version'] ?? $record['version'] ?? null);
            }
        }

        return $named;
    }

    /** @param array<string,mixed> $record @return array<string,true> */
    private static function journalTags(array $record): array
    {
        $tags = [];
        foreach (is_array($record['items'] ?? null) ? $record['items'] : [] as $item) {
            $tag = is_array($item) ? ($item['component'] ?? null) : null;
            if (is_string($tag) && str_starts_with($tag, 'sf-')) {
                $tags[$tag] = true;
            }
        }

        return $tags;
    }

    /** @param array<string,mixed> $record @return array<string,mixed> */
    private static function journalEntry(array $record, ?string $version): array
    {
        $entry = ['id' => $record['id'] ?? null, 'recorded_on' => $record['recorded_on'] ?? null, 'stage' => $record['stage'] ?? null];
        if ($version !== null) {
            $entry['version'] = $version;
        }
        if (($record['remove_after'] ?? null) !== null && $record['remove_after'] !== '') {
            $entry['remove_after'] = $record['remove_after'];
        }
        $entry['instruction'] = self::instructionOf($record);

        return $entry;
    }

    /**
     * The journal entries between a used and a target version of one unit
     * that ask for a change: a versioned entry counts when its version is
     * above the used one and not above the target; an entry without a version
     * (matched by a Smart component's tag) when it was recorded after the day
     * the consumer was brought up to the source.
     *
     * @param  array<string,mixed>|null  $index
     * @return list<array<string,mixed>>
     */
    private static function journalBetween(?array $index, string $id, ?string $used, ?string $target, ?string $broughtUpOn): array
    {
        if ($index === null) {
            return [];
        }
        $found = [];
        $tag = str_starts_with($id, 'smart.') ? 'sf-' . substr($id, strlen('smart.')) : null;
        foreach ($index['asking'] as ['record' => $record, 'units' => $units, 'tags' => $tags]) {
            $mine = array_values(array_filter($units, static fn (array $unit): bool => $unit['id'] === $id));
            $versioned = null;
            foreach ($mine as $unit) {
                if ($unit['version'] !== null) {
                    $versioned = $unit;
                    break;
                }
            }
            if ($versioned !== null) {
                if ($used !== null && $target !== null && self::compareVersions($used, $versioned['version']) < 0 && self::compareVersions($versioned['version'], $target) <= 0) {
                    $found[] = self::journalEntry($record, $versioned['version']);
                }

                continue;
            }
            $named = $mine !== [] || ($units === [] && $tag !== null && isset($tags[$tag]));
            if ($named && $broughtUpOn !== null && is_string($record['recorded_on'] ?? null) && strcmp($record['recorded_on'], $broughtUpOn) > 0) {
                $found[] = self::journalEntry($record, null);
            }
        }
        usort($found, static fn (array $a, array $b): int => strcmp((string) $a['recorded_on'], (string) $b['recorded_on']) ?: strcmp((string) $a['id'], (string) $b['id']));

        return $found;
    }

    /**
     * Every unit an id reaches through the inventory's dependencies, without
     * itself, each with the shortest chain that reaches it. Shared
     * infrastructure (core, module) is neither entered nor returned.
     *
     * @param  array<string,array<string,mixed>>  $byId
     * @return array<string,list<string>>
     */
    private static function closure(array $byId, string $id): array
    {
        $chains = [$id => [$id]];
        $queue = [$id];
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($byId[$current]['dependencies'] ?? [] as $dependency) {
                $dependencyId = (string) ($dependency['id'] ?? '');
                $unit = $byId[$dependencyId] ?? null;
                if (isset($chains[$dependencyId]) || ($unit !== null && in_array($unit['kind'] ?? null, self::INFRASTRUCTURE_KINDS, true))) {
                    continue;
                }
                $chains[$dependencyId] = [...$chains[$current], $dependencyId];
                $queue[] = $dependencyId;
            }
        }
        unset($chains[$id]);
        ksort($chains, SORT_STRING);

        return $chains;
    }

    /** @param list<array<string,mixed>> $reports @return array<string,array{from_version:string,to_version:string}> */
    private static function changesFrom(array $reports): array
    {
        $moved = [];
        foreach ($reports as $report) {
            foreach ($report['changed'] ?? [] as $change) {
                $known = $moved[$change['id']] ?? null;
                if ($known === null) {
                    $moved[$change['id']] = ['from_version' => $change['from_version'], 'to_version' => $change['to_version']];

                    continue;
                }
                if (self::compareVersions($change['from_version'], $known['from_version']) < 0) {
                    $moved[$change['id']]['from_version'] = $change['from_version'];
                }
                if (self::compareVersions($change['to_version'], $known['to_version']) > 0) {
                    $moved[$change['id']]['to_version'] = $change['to_version'];
                }
            }
        }

        return $moved;
    }

    private static function statusOfStep(string $step): string
    {
        return $step === 'major' ? 'action-needed' : 'update-available';
    }

    /** How one foundation moved between the record's release and the target, or null. @param array<string,mixed> $target @param array<string,mixed> $foundations @return array<string,mixed>|null */
    private static function foundationMove(string $id, array $target, array $foundations): ?array
    {
        $now = $target['byId'][$id];
        if (isset($foundations['since'])) {
            $before = $foundations['since']['byId'][$id] ?? null;
            if ($before === null || ($before['content_sha256'] === $now['content_sha256'] && $before['version'] === $now['version'])) {
                return null;
            }
            $beforeVersion = (string) $before['version'];
        } elseif (isset($foundations['moved'])) {
            if (! isset($foundations['moved'][$id])) {
                return null;
            }
            $beforeVersion = $foundations['moved'][$id]['from_version'];
        } else {
            return null;
        }
        $step = self::versionStep($beforeVersion, (string) $now['version']);
        $via = ['id' => $id, 'kind' => $now['kind'] ?? null, 'from_version' => $beforeVersion, 'to_version' => $now['version'], 'step' => $step];
        $reasons = [];
        $status = $step === 'none' ? 'update-available' : self::statusOfStep($step);
        if ($step === 'none') {
            $reasons[] = "$id {$now['version']}: its hash moved while its version did not";
        }
        $journal = self::journalBetween($foundations['journal'], $id, $beforeVersion, (string) $now['version'], null);
        if ($journal !== []) {
            $status = 'action-needed';
            $via['journal'] = $journal;
        }
        $via['status'] = $status;
        if ($reasons !== []) {
            $via['reasons'] = $reasons;
        }

        return $via;
    }

    /** @param array<string,mixed> $used @param array<string,mixed> $context @return array<string,mixed> */
    private function reconcileUnit(array $used, array $context): array
    {
        $result = ['id' => $used['id'], 'version' => $used['version'], 'content_sha256' => $used['content_sha256']];
        if (($used['source'] ?? '') !== '') {
            $result['source'] = $used['source'];
        }
        $reasons = [];
        $status = 'current';
        $id = $used['id'];
        $inventory = $this->inventoryFor($used, $context['targets']);

        if ($inventory === null || ! isset($inventory['byId'][$id])) {
            $former = $context['journal']['former'][$id] ?? null;
            $owner = null;
            if ($former !== null) {
                foreach ($context['targets'] as $candidate) {
                    if (isset($candidate['byId'][$former['to']])) {
                        $owner = $candidate;
                        break;
                    }
                }
            }
            if ($former === null || $owner === null) {
                $release = $inventory === null ? null : ($inventory['release'] ?? substr((string) ($inventory['revision'] ?? ''), 0, 12));
                $result['status'] = 'missing';
                $result['reasons'] = [$inventory === null ? "no inventory carries $id" : "{$inventory['source']} $release has no unit $id"];

                return $result;
            }
            $record = $former['record'];
            $result['former'] = [
                'to' => $former['to'],
                'version' => $owner['byId'][$former['to']]['version'],
                'journal' => $record['id'] ?? null,
                'until' => $record['removed_on'] ?? $record['remove_after'] ?? null,
            ];
            if (($record['stage'] ?? null) === 'removed') {
                $status = 'missing';
                $reasons[] = "$id was renamed to {$former['to']} and the former name was removed on " . ($record['removed_on'] ?? $record['remove_after'] ?? '');
            } else {
                $status = 'former-name';
                $reasons[] = "$id is a former name of {$former['to']}; it works until " . ($record['remove_after'] ?? 'an undated removal');
                if (($record['remove_after'] ?? '') !== '' && strcmp($context['today'], (string) $record['remove_after']) > 0) {
                    $result['former']['expired'] = true;
                    $reasons[] = "the former name stopped on {$record['remove_after']}";
                }
            }
            $result['former']['instruction'] = self::instructionOf($record);
            $renamed = $owner['byId'][$former['to']];
            if ($status !== 'missing' && self::compareVersions($renamed['version'], $used['version']) > 0 && self::versionStep($used['version'], $renamed['version']) === 'major') {
                $status = 'action-needed';
                $reasons[] = "major change {$used['version']} -> {$renamed['version']} under the new id";
            }
            $id = $former['to'];
            $inventory = $owner;
        }

        $target = $inventory['byId'][$id];
        $result['current'] = ['id' => $id, 'version' => $target['version'], 'content_sha256' => $target['content_sha256']];
        if ($id === $used['id']) {
            if ($target['content_sha256'] !== $used['content_sha256']) {
                $order = self::compareVersions($target['version'], $used['version']);
                if ($order > 0) {
                    $step = self::versionStep($used['version'], $target['version']);
                    $result['step'] = $step;
                    $status = self::statusOfStep($step);
                    $reasons[] = $step === 'major' ? "major change {$used['version']} -> {$target['version']}" : "$step {$used['version']} -> {$target['version']}";
                } elseif ($order === 0) {
                    $status = 'update-available';
                    $reasons[] = "$id {$target['version']}: the hash moved while the version did not";
                } else {
                    $status = 'action-needed';
                    $reasons[] = "the record names {$used['version']}, newer than the inventory's {$target['version']}";
                }
            }
            $broughtUpOn = $context['sources'][$inventory['source']]['brought_up_on'] ?? null;
            $journal = self::journalBetween($context['journal'], $id, $used['version'], $target['version'], is_string($broughtUpOn) ? $broughtUpOn : null);
            if ($journal !== []) {
                $result['journal'] = $journal;
                $status = self::worst([$status, 'action-needed']);
                $reasons[] = count($journal) . ' journal ' . (count($journal) === 1 ? 'entry asks' : 'entries ask') . ' for a change';
            }
        }

        $foundations = $this->foundationContext($inventory, $context);
        if ($foundations !== null) {
            $via = [];
            foreach (self::closure($inventory['byId'], $id) as $dependency => $chain) {
                $unit = $inventory['byId'][$dependency] ?? null;
                if ($unit === null || ! in_array($unit['kind'] ?? null, self::FOUNDATION_KINDS, true)) {
                    continue;
                }
                $move = self::foundationMove((string) $dependency, $inventory, $foundations);
                if ($move !== null) {
                    $move['chain'] = $chain;
                    $via[] = $move;
                }
            }
            if ($via !== []) {
                $result['via'] = $via;
                $status = self::worst([$status, ...array_column($via, 'status')]);
                $reasons[] = 'foundations moved: ' . implode(', ', array_map(
                    static fn (array $move): string => "{$move['id']} {$move['from_version']} -> {$move['to_version']}",
                    $via,
                ));
            }
        } else {
            $result['foundations'] = 'unchecked';
        }

        $result['status'] = $status;
        if ($reasons !== []) {
            $result['reasons'] = $reasons;
        }

        return $result;
    }

    /** @param array<string,mixed> $unit @param list<array<string,mixed>> $targets @return array<string,mixed>|null */
    private function inventoryFor(array $unit, array $targets): ?array
    {
        if (($unit['source'] ?? '') !== '') {
            foreach ($targets as $inventory) {
                if ($inventory['source'] === $unit['source']) {
                    return $inventory;
                }
            }

            return null;
        }
        foreach ($targets as $inventory) {
            if (isset($inventory['byId'][$unit['id']])) {
                return $inventory;
            }
        }

        return count($targets) === 1 ? $targets[0] : null;
    }

    /** @param array<string,mixed> $inventory @param array<string,mixed> $context @return array<string,mixed>|null */
    private function foundationContext(array $inventory, array $context): ?array
    {
        foreach ($context['before'] as $candidate) {
            if ($candidate['source'] === $inventory['source']) {
                return ['since' => $candidate, 'journal' => $context['journal']];
            }
        }
        $reports = array_values(array_filter($context['changes'], static fn (array $report): bool => ($report['source'] ?? null) === $inventory['source']));
        if ($reports !== []) {
            return ['moved' => self::changesFrom($reports), 'journal' => $context['journal']];
        }
        $recorded = $context['sources'][$inventory['source']]['release'] ?? null;
        if ($recorded !== null && $recorded === ($inventory['release'] ?? null)) {
            return ['since' => $inventory, 'journal' => $context['journal']];
        }

        return null;
    }

    /** @param array<string,mixed> $inventory @return array<string,mixed> */
    private static function indexed(array $inventory): array
    {
        $byId = [];
        foreach ($inventory['units'] as $unit) {
            $byId[$unit['id']] = $unit;
        }

        return $inventory + ['byId' => $byId];
    }

    /** @param array<string,mixed> $inventory @return array<string,mixed> */
    private static function identity(array $inventory): array
    {
        return ['source' => $inventory['source'], 'release' => $inventory['release'] ?? null, 'revision' => $inventory['revision'] ?? null];
    }

    private static function matches(string $pattern, mixed $value): bool
    {
        return is_string($value) && preg_match($pattern, $value) === 1;
    }
}
