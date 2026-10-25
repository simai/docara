<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogBuilder;
use Simai\Docara\ComponentCatalog\EffectiveComponentCatalogValidator;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * A requirement record makes a missing component visible without admitting
 * it. development/smart-components.md asks each one for an owner, the reason
 * it is missing, the fallback authors use meanwhile and the admission
 * condition that replaces it with a supported component.
 */
final class ComponentRequirementRecordTest extends TestCase
{
    #[Test]
    public function every_requirement_record_states_owner_reason_fallback_and_admission(): void
    {
        $paths = glob(dirname(__DIR__, 2) . '/resources/component-catalog/requirements/*.json') ?: [];
        self::assertNotSame([], $paths);
        foreach ($paths as $path) {
            $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('requirement', $record['family'], $path);
            self::assertNotSame('supported', $record['lifecycle'], $path);
            self::assertSame(['owner', 'reason', 'fallback', 'admission'], array_keys($record['gap']), $path);
            foreach ($record['gap'] as $field => $text) {
                self::assertIsString($text);
                self::assertGreaterThanOrEqual($field === 'owner' ? 3 : 40, mb_strlen(trim($text)), "$path gap.$field is too short to explain anything.");
            }
        }

        $catalog = EffectiveComponentCatalogBuilder::bundled(FrameworkLock::fromJsonFile(
            dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json',
        ))->build();
        $index = array_search('ui.dataview', array_column($catalog['entries'], 'id'), true);
        self::assertIsInt($index);
        foreach (['reason', 'fallback', 'admission'] as $field) {
            $broken = $catalog;
            unset($broken['entries'][$index]['gap'][$field]);
            try {
                (new EffectiveComponentCatalogValidator)->assertValid($broken);
                self::fail("A requirement without gap.$field passed validation.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame('COMPONENT_CATALOG_GAP_INCOMPLETE', $exception->errorCode);
            }
        }
    }
}
