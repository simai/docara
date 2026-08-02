<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Content\LegacyPublicSourceAllowlist;
use Simai\Docara\Portable\PortableConfigurationException;

final class LegacyPublicSourceAllowlistTest extends TestCase
{
    public function test_m0_generated_routes_are_retired_and_cannot_return(): void
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
                    '/ru/components/',
                    '/ru/components/alert/',
                    '/ru/components/backlinks/',
                    '/ru/components/banner/',
                    '/ru/components/button/',
                    '/ru/components/card/',
                    '/ru/components/code/',
                    '/ru/components/code-from-file/',
                    '/ru/components/details/',
                    '/ru/components/diagram/',
                    '/ru/components/download/',
                    '/ru/components/embed/',
                    '/ru/components/example/',
                    '/ru/components/figure/',
                    '/ru/components/footnotes-and-sources/',
                    '/ru/components/headings-and-text/',
                    '/ru/components/grid/',
                    '/ru/components/icon/',
                    '/ru/components/kbd/',
                    '/ru/components/logos/',
                    '/ru/components/math/',
                    '/ru/components/hero/',
                    '/ru/components/html/',
                    '/ru/components/links-and-images/',
                    '/ru/components/lists-and-quotes/',
                    '/ru/components/media/',
                    '/ru/components/steps/',
                    '/ru/components/table/',
                    '/ru/components/tabs/',
                    '/ru/components/tree/',
                ], true),
        ));
        $allowlist = new LegacyPublicSourceAllowlist;

        self::assertCount(14, $generated);
        self::assertSame([], $allowlist->generatedRoutes());
        $allowlist->assertGeneratedPages([]);

        $generated = [[
            'url' => '/ru/generated-growth/',
            'page_source_kind' => 'generated_projection',
        ]];
        $this->assertError(
            'LEGACY_GENERATED_ROUTE_GROWTH_FORBIDDEN',
            fn () => $allowlist->assertGeneratedPages($generated),
        );
    }

    public function test_retired_public_language_pack_contract_is_absent(): void
    {
        $root = dirname(__DIR__, 2);
        self::assertDirectoryDoesNotExist($root . '/resources/language-packs');
        self::assertFileDoesNotExist($root . '/resources/schemas/language-pack.schema.json');
        self::assertFileDoesNotExist($root . '/src/I18n/LanguagePack.php');
        self::assertFileDoesNotExist($root . '/src/I18n/LanguagePackRepository.php');
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
