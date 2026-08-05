<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\PortableSchemaReferenceHydrator;
use Simai\Docara\PortableSite\PortableSchemaReferenceProjector;
use Tests\TestCase;

final class PortableSchemaReferenceProjectorTest extends TestCase
{
    #[Test]
    public function schema_reference_is_exhaustive_deterministic_and_provenance_bound(): void
    {
        $projector = new PortableSchemaReferenceProjector;
        $first = $projector->project('site', 'site');
        $second = $projector->project('site', 'site');
        self::assertSame($first, $second);
        $paths = array_column($first, 'path');
        foreach (['/framework_lock', '/locales', '/locale_routing', '/branding', '/layout', '/settings', '/reader_preferences', '/search', '/reading', '/smart'] as $path) {
            self::assertContains($path, $paths);
        }
        foreach ($first as $record) {
            self::assertSame('site', $record['scope']);
            self::assertArrayHasKey('has_default', $record);
            self::assertNotSame('', $record['validation']);
            self::assertMatchesRegularExpression('~^resources/schemas/[a-z0-9.-]+\.schema\.json#~', $record['provenance']);
        }
        $receipt = $projector->receipt();
        self::assertSame('docara.public_schema_reference.v1', $receipt['schema']);
        self::assertCount(5, $receipt['sources']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $receipt['content_sha256']);
    }

    #[Test]
    public function selected_defs_compositions_conditionals_and_exact_pointers_are_projected(): void
    {
        $presentation = (new PortableSchemaReferenceProjector)->project('presentation', 'shared');
        self::assertNotEmpty($presentation);
        $byPath = [];
        foreach ($presentation as $record) {
            $byPath[$record['path']][] = $record;
            self::assertDoesNotMatchRegularExpression('~#$~', $record['provenance']);
            self::assertMatchesRegularExpression('~\.schema\.json#/~', $record['provenance']);
        }
        foreach ([
            '/branding/title',
            '/layout/regions/<^[a-z][a-z0-9_.-]+$>/sections/*/blocks/*/smart',
            '/layout/regions/<^[a-z][a-z0-9_.-]+$>/sections/*/blocks/*/element/utilities',
        ] as $path) {
            self::assertArrayHasKey($path, $byPath);
        }
        self::assertStringContainsString('uniqueItems=true', $byPath['/layout/regions/<^[a-z][a-z0-9_.-]+$>/sections/*/blocks/*/element/utilities'][0]['validation']);
        self::assertStringContainsString('minimum=0', $byPath['/layout/content/gap'][0]['validation']);
        self::assertTrue((bool) array_filter($presentation, static fn (array $record): bool => str_contains($record['validation'], 'oneOf=')));
        self::assertTrue((bool) array_filter($presentation, static fn (array $record): bool => str_contains($record['provenance'], '/if') || str_contains($record['provenance'], '/then')));
        self::assertArrayNotHasKey('/region', $byPath, 'Internal definitions are traversed through admitted public surfaces, not published as duplicate roots.');
    }

    #[Test]
    public function typed_placeholder_hydrates_scope_default_validation_and_provenance(): void
    {
        $placeholder = (new PortableMarkdownRenderer)->render(":::schema_reference {schema=page scope=page}\n:::\n");
        $html = (new PortableSchemaReferenceHydrator)->hydrate([['content_html' => $placeholder]])[0]['content_html'];
        foreach (['Поле', 'Scope', 'Default', 'Validation', 'Provenance', '/description', 'resources/schemas/page.schema.json'] as $marker) {
            self::assertStringContainsString($marker, $html);
        }
        self::assertStringContainsString('effective value определяется inheritance/runtime', $html);
    }

    #[Test]
    public function unadmitted_schema_name_fails_before_projection(): void
    {
        $this->expectException(PortableConfigurationException::class);
        (new PortableMarkdownRenderer)->render(":::schema_reference {schema=../../secret scope=site}\n:::\n");
    }
}
