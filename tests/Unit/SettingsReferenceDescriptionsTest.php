<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\I18n\LocaleMissingPagePolicy;
use Simai\Docara\Portable\ConfigurationDefaults;
use Simai\Docara\PortableSite\PortableSchemaDescriptions;
use Simai\Docara\PortableSite\PortableSchemaReferenceProjector;
use Simai\Docara\Preferences\ReaderPreferenceCompiler;
use Tests\TestCase;

final class SettingsReferenceDescriptionsTest extends TestCase
{
    private const DESCRIBED = ['site' => 'site', 'section' => 'section', 'page' => 'page', 'presentation' => 'shared'];

    #[Test]
    public function every_docara_section_and_page_field_has_a_russian_description(): void
    {
        $catalogue = (new PortableSchemaDescriptions)->catalogue('ru');
        $missing = [];
        foreach (self::DESCRIBED as $schema => $scope) {
            foreach ((new PortableSchemaReferenceProjector)->project($schema, $scope) as $record) {
                $key = substr($record['provenance'], strlen('resources/schemas/'));
                $text = $catalogue['fields'][$key] ?? null;
                if (! is_string($text) || trim($text) === '') {
                    $missing[] = "$schema {$record['path']} ($key)";
                }
            }
        }

        self::assertSame([], $missing, 'Add these fields to resources/schema-descriptions/ru.json.');
    }

    #[Test]
    public function the_catalogue_names_only_fields_that_exist(): void
    {
        $pointers = [];
        foreach (self::DESCRIBED as $schema => $scope) {
            foreach ((new PortableSchemaReferenceProjector)->project($schema, $scope) as $record) {
                $pointers[substr($record['provenance'], strlen('resources/schemas/'))] = true;
            }
        }
        $catalogue = (new PortableSchemaDescriptions)->catalogue('ru');

        self::assertSame([], array_keys(array_diff_key($catalogue['fields'], $pointers)));
        self::assertSame([], array_keys(array_diff_key($catalogue['derived_defaults'], $pointers)));
        foreach ($catalogue['fields'] as $key => $text) {
            self::assertMatchesRegularExpression('/[А-Яа-яЁё]/u', $text, $key);
            self::assertSame(0, substr_count($text, '`') % 2, "Unbalanced code span in [$key].");
        }
    }

    #[Test]
    public function a_language_without_a_catalogue_uses_the_schema_text(): void
    {
        $descriptions = new PortableSchemaDescriptions;
        $record = ['provenance' => 'resources/schemas/site.schema.json#/properties/site_url', 'description' => 'English text.'];

        self::assertSame('English text.', $descriptions->description($record, 'en'));
        self::assertStringStartsWith('Полный публичный адрес сайта', (string) $descriptions->description($record, 'ru-RU'));
        self::assertNull($descriptions->derivedDefault($record, 'de'));
    }

    #[Test]
    public function documented_defaults_are_the_ones_the_build_applies(): void
    {
        $layout = RegionCompositionResolver::defaults();
        self::assertSame($layout['key'], ConfigurationDefaults::value('layout.key'));
        self::assertSame($layout['container']['max'], ConfigurationDefaults::value('layout.container.max'));
        self::assertSame($layout['scrollbar']['preset'], ConfigurationDefaults::value('layout.scrollbar.preset'));
        self::assertSame($layout['content']['gap'], ConfigurationDefaults::value('layout.content.gap'));
        self::assertSame(LocaleMissingPagePolicy::fromSite([])->value, ConfigurationDefaults::value('locales.missing_page_policy'));
        self::assertSame(ReaderPreferenceCompiler::defaultConfiguration(), ConfigurationDefaults::value('reader_preferences'));
        self::assertSame(3, ConfigurationDefaults::value('reading.toc_depth'));
        self::assertArrayNotHasKey('layout', ConfigurationDefaults::all(), 'Objects are listed through their fields.');
    }
}
