<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\ComponentCatalog\PublicComponentPage;
use Simai\Docara\Framework\ComponentDirectiveParser;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkComponentName;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Markdown\DirectiveOpeningMatcher;
use Simai\Docara\Portable\PortableConfigurationException;

final class FrameworkComponentNameTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function acceptedNames(): iterable
    {
        yield 'plain' => ['ui.button'];
        yield 'underscore' => ['ui.data_view'];
        yield 'hyphen' => ['ui.progress-bar'];
        yield 'two hyphens apart' => ['ui.reference-link-card'];
        yield 'digit after hyphen' => ['ui.grid-2'];
        yield 'nested segment' => ['ui.form.input-lookup'];
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedNames(): iterable
    {
        yield 'leading hyphen' => ['ui.-x'];
        yield 'trailing hyphen' => ['ui.x-'];
        yield 'double hyphen' => ['ui.x--y'];
        yield 'hyphen before underscore' => ['ui.x-_y'];
        yield 'upper case' => ['ui.Progress-bar'];
        yield 'empty segment' => ['ui..x'];
        yield 'no segment' => ['ui'];
        yield 'trailing hyphen in a nested segment' => ['ui.x-.y'];
    }

    #[Test]
    #[DataProvider('acceptedNames')]
    public function framework_names_may_hold_single_hyphens_inside_a_segment(string $name): void
    {
        self::assertTrue(FrameworkComponentName::isFramework($name));
        self::assertTrue(FrameworkComponentName::isDotted($name));
        self::assertTrue(DirectiveOpeningMatcher::isCanonicalFrameworkName($name));
        self::assertSame($name, PublicComponentPage::id(['id' => $name]));
        foreach ($this->schemaPatterns() as $schema => $pattern) {
            self::assertSame(1, preg_match('/' . str_replace('/', '\/', $pattern) . '/u', $name), $schema);
        }
    }

    #[Test]
    #[DataProvider('rejectedNames')]
    public function malformed_hyphens_and_other_shapes_stay_rejected(string $name): void
    {
        self::assertFalse(FrameworkComponentName::isFramework($name));
        self::assertFalse(DirectiveOpeningMatcher::isCanonicalFrameworkName($name));
        foreach ($this->schemaPatterns() as $schema => $pattern) {
            self::assertSame(0, preg_match('/' . str_replace('/', '\/', $pattern) . '/u', $name), $schema);
        }

        try {
            (new ComponentDirectiveParser([$name, 'ui.button']))->parse(":::{$name}\n:::\n", 'page.md');
            self::fail('The directive name was accepted: ' . $name);
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_COMPONENT_ID_INVALID', $exception->errorCode);
        }

        try {
            FrameworkManifestRepository::manifestRelativePath($name);
            self::fail('The manifest key was accepted: ' . $name);
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_COMPONENT_UNSUPPORTED', $exception->errorCode);
        }
    }

    #[Test]
    public function a_hyphenated_directive_reaches_the_supported_component(): void
    {
        $parsed = (new ComponentDirectiveParser(['ui.progress-bar']))
            ->parse(":::ui.progress-bar\n{\"value\": 40}\n:::\n", 'page.md');

        self::assertSame('ui.progress-bar', $parsed->directives[0]->component);
        self::assertSame('manifests/ui-progress-bar.json', FrameworkManifestRepository::manifestRelativePath('ui.progress-bar'));
    }

    #[Test]
    public function a_catalogue_id_with_a_doubled_hyphen_keeps_its_diagnostic(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('COMPONENT_CATALOG_ID_INVALID');

        PublicComponentPage::id(['id' => 'ui.x--y']);
    }

    /** @return array<string, string> */
    private function schemaPatterns(): array
    {
        $root = dirname(__DIR__, 2) . '/resources/schemas/';
        $read = static fn (string $file): array => json_decode((string) file_get_contents($root . $file), true, 512, JSON_THROW_ON_ERROR);

        return [
            'component-call' => $read('component-call.schema.json')['properties']['component']['pattern']
                ?? self::findPattern($read('component-call.schema.json'), '^ui('),
            'framework-lock' => self::findPattern($read('framework-lock.schema.json'), '^ui('),
            'declarative-smart-view' => $read('declarative-smart-view.schema.json')['properties']['smart']['pattern'],
            'component-catalog-entry' => self::findPattern($read('component-catalog-entry.schema.json'), '^[a-z][a-z0-9_]*(?:-'),
        ];
    }

    /** @param array<mixed> $schema */
    private static function findPattern(array $schema, string $prefix): string
    {
        $found = null;
        array_walk_recursive($schema, static function (mixed $value, int|string $key) use ($prefix, &$found): void {
            if ($found === null && $key === 'pattern' && is_string($value) && str_starts_with($value, $prefix)) {
                $found = $value;
            }
        });
        self::assertIsString($found, 'No schema pattern starts with ' . $prefix);

        return $found;
    }
}
