<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\SchemaRepository;
use Tests\TestCase;

final class ApplicationContractTest extends TestCase
{
    #[Test]
    public function golden_results_are_canonical_and_match_the_single_operation_schema(): void
    {
        $root = dirname(__DIR__, 2);
        $validator = new JsonSchemaValidator(new SchemaRepository($root . '/resources/schemas'));

        foreach (glob($root . '/tests/fixtures/application/golden/*.json') ?: [] as $path) {
            $contents = (string) file_get_contents($path);
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            $validator->assertValid($decoded, 'operation-result.schema.json');
            self::assertSame(CanonicalJson::encodePretty($decoded), $contents, basename($path));
        }
    }

    #[Test]
    public function golden_human_results_are_nonempty_and_have_json_twins(): void
    {
        $root = dirname(__DIR__, 2) . '/tests/fixtures/application/golden';

        foreach (glob($root . '/*.txt') ?: [] as $path) {
            self::assertFileExists(substr($path, 0, -4) . '.json');
            self::assertNotSame('', trim((string) file_get_contents($path)));
        }
    }
}
