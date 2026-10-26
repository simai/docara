<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\ComponentParameterReference;

final class ComponentParameterReferenceTest extends TestCase
{
    #[Test]
    public function every_moved_component_page_holds_its_current_generated_parameter_table(): void
    {
        $root = dirname(__DIR__, 2);
        $reference = new ComponentParameterReference($root);

        foreach ($reference->updatedPages() as $page => $text) {
            self::assertSame($text, (string) file_get_contents($root . '/' . $page), "$page: run scripts/capture-component-parameters.php");
        }
        self::assertNotSame([], $reference->updatedPages());
    }

    #[Test]
    public function every_catalogue_parameter_has_exactly_one_description(): void
    {
        $root = dirname(__DIR__, 2);
        $file = json_decode((string) file_get_contents($root . '/resources/component-catalog/descriptions/ru.json'), true, 512, JSON_THROW_ON_ERROR);
        $described = array_keys($file['descriptions']);
        $parameters = [];
        foreach ((new ComponentParameterReference($root))->entries() as $entry) {
            foreach ($entry['authoring']['parameters'] ?? [] as $parameter) {
                $parameters[] = $entry['id'] . '.' . $parameter['name'];
            }
        }
        sort($described);
        sort($parameters);

        // A missing description fails the generator; a left-over one means a
        // parameter was renamed or removed without its description.
        self::assertSame($parameters, $described);
    }
}
