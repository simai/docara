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
