<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Support;

use Larena\Ui\Frontend\FrameworkContractRegistry;

final class FrameworkContractFixture
{
    public static function registry(): FrameworkContractRegistry
    {
        return FrameworkContractRegistry::fromArray(self::data(), str_repeat('a', 64));
    }

    /** @return array<string, mixed> */
    public static function data(): array
    {
        $entries = [
            self::entry('component.buttons', 'component'),
            self::entry('recipe.admin.collection', 'recipe', [
                'smart.table',
                'utility.display',
                'utility.flex-direction',
                'utility.gap',
                'utility.overflow',
                'utility.width',
            ]),
            self::entry('smart.table', 'smart-component', ['component.buttons']),
            self::entry('utility.display', 'utility'),
            self::entry('utility.flex-direction', 'utility'),
            self::entry('utility.gap', 'utility'),
            self::entry('utility.overflow', 'utility'),
            self::entry('utility.width', 'utility'),
        ];
        $byKind = [
            'utility' => [],
            'component' => [],
            'smart-component' => [],
            'recipe' => [],
        ];
        foreach ($entries as $entry) {
            $byKind[$entry['kind']][] = $entry['id'];
        }
        foreach ($byKind as &$ids) {
            sort($ids, SORT_STRING);
        }
        unset($ids);

        return [
            'schema_id' => 'simai.framework.contract-registry',
            'schema_version' => 1,
            'compatibility' => [
                'id' => 'sf-v5.3.2-7e836d8a-dd786bba',
                'status' => 'bounded',
                'profile' => 'plain-assets-v1',
                'runtime_sources' => [],
                'exclusions' => [],
                'claims' => [
                    'full_compatible' => false,
                    'production_ready' => false,
                    'all_items_ready' => false,
                ],
            ],
            'source_manifests' => array_map(static fn (string $kind): array => [
                'kind' => $kind,
                'owner' => $kind === 'smart-component' ? 'simai/ui-smart' : 'simai/ui',
                'path' => 'contracts/owners/' . $kind . '.manifest.json',
                'sha256' => str_repeat(match ($kind) {
                    'utility' => '1',
                    'component' => '2',
                    'smart-component' => '3',
                    default => '4',
                }, 64),
            ], ['utility', 'component', 'smart-component', 'recipe']),
            'counts' => [
                'utility' => 5,
                'component' => 1,
                'smart-component' => 1,
                'recipe' => 1,
                'total' => 8,
            ],
            'entries' => $entries,
            'indexes' => [
                'by_kind' => $byKind,
                'safe_to_suggest' => array_column($entries, 'id'),
                'blocked' => [],
                'recipe_closure' => [
                    'recipe.admin.collection' => [
                        'component.buttons',
                        'smart.table',
                        'utility.display',
                        'utility.flex-direction',
                        'utility.gap',
                        'utility.overflow',
                        'utility.width',
                    ],
                ],
            ],
            'nonclaims' => [
                'production_ready' => false,
                'full_compatibility' => false,
                'all_items_ready' => false,
            ],
        ];
    }

    /** @param list<string> $requires @return array<string, mixed> */
    private static function entry(string $id, string $kind, array $requires = []): array
    {
        return [
            'id' => $id,
            'kind' => $kind,
            'owner' => $kind === 'smart-component' ? 'simai/ui-smart' : 'simai/ui',
            'lifecycle' => 'released',
            'readiness' => [
                'status' => 'ready',
                'safe_to_suggest' => true,
                'profiles' => ['plain-assets-v1'],
                'blockers' => [],
            ],
            'provenance' => ['source' => 'test-fixture'],
            'documentation_refs' => [],
            'example_refs' => [],
            'runtime' => $id === 'smart.table' ? ['tags' => ['sf-table']] : [],
            'requires' => $requires,
            'curated_for' => ['admin.collection'],
        ];
    }
}
