<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Content\LegacyPublicSourceAllowlist;
use Simai\Docara\Portable\PortableConfigurationException;

final class LegacyPublicSourceAllowlistTest extends TestCase
{
    public function test_exact_m0_generated_routes_are_finite_and_cannot_grow(): void
    {
        $root = dirname(__DIR__, 2);
        $inventory = json_decode(
            (string) file_get_contents(
                $root . '/source/workflow/evidence/2026-08-01-docara-unified-architecture/m0-route-inventory.json',
            ),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $generated = array_values(array_filter(
            $inventory['routes'],
            static fn (array $route): bool => $route['page_source_kind'] === 'generated_projection'
                && ! in_array($route['url'], [
                    '/ru/components/alert/',
                    '/ru/components/headings-and-text/',
                    '/ru/components/lists-and-quotes/',
                ], true),
        ));
        $allowlist = new LegacyPublicSourceAllowlist;

        self::assertCount(41, $generated);
        self::assertCount(41, $allowlist->generatedRoutes());
        foreach (['alert', 'headings-and-text', 'lists-and-quotes'] as $slug) {
            self::assertNotContains("/ru/components/$slug/", $allowlist->generatedRoutes());
        }
        self::assertNotContains('/ru/components/badge/', $allowlist->generatedRoutes());
        $allowlist->assertGeneratedPages($generated);

        $generated[] = [
            'url' => '/ru/generated-growth/',
            'page_source_kind' => 'generated_projection',
        ];
        $this->assertError(
            'LEGACY_GENERATED_ROUTE_GROWTH_FORBIDDEN',
            fn () => $allowlist->assertGeneratedPages($generated),
        );
    }

    public function test_bundled_language_pack_component_prose_counts_can_only_decrease(): void
    {
        $root = dirname(__DIR__, 2);
        $counts = [];
        foreach (glob($root . '/resources/language-packs/*.json') ?: [] as $path) {
            $pack = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $counts[pathinfo($path, PATHINFO_FILENAME)] = count($pack['components'] ?? []);
        }

        $allowlist = new LegacyPublicSourceAllowlist;
        $allowlist->assertLanguagePackComponentCounts($counts);
        self::assertSame(['ar' => 8, 'en' => 42, 'fr-CA' => 8, 'ru' => 39, 'zh-Hans' => 8], $counts);
        $russian = json_decode(
            (string) file_get_contents($root . '/resources/language-packs/ru.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        )['components'];
        foreach (['docara.alert', 'native.headings_and_text', 'native.lists_and_quotes'] as $id) {
            self::assertArrayNotHasKey($id, $russian);
        }

        $counts['ru']++;
        $this->assertError(
            'LEGACY_LANGUAGE_PACK_PROSE_GROWTH_FORBIDDEN',
            fn () => $allowlist->assertLanguagePackComponentCounts($counts),
        );
    }

    private function assertError(string $code, callable $callback): void
    {
        try {
            $callback();
            self::fail("Expected [$code].");
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode);
        }
    }
}
