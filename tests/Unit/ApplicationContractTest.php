<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Simai\Docara\Console\ApplicationFactory;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\JsonSchemaValidator;
use Simai\Docara\Portable\SchemaRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

final class ApplicationContractTest extends TestCase
{
    #[Test]
    public function golden_results_are_canonical_and_match_the_single_operation_schema(): void
    {
        $root = dirname(__DIR__, 2);
        $validator = new JsonSchemaValidator(new SchemaRepository($root . '/resources/schemas'));

        foreach ($this->actualCases($this->tmpPath('projects')) as $name => $actual) {
            $path = $root . '/tests/fixtures/application/golden/' . $name . '.json';
            $contents = (string) file_get_contents($path);
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            $validator->assertValid($decoded, 'operation-result.schema.json');
            self::assertSame(CanonicalJson::encodePretty($decoded), $contents, basename($path));
            self::assertSame($contents, CanonicalJson::encodePretty($this->normalize($actual['json'], $name)), $name);
            self::assertSame(
                (string) file_get_contents($root . '/tests/fixtures/application/golden/' . $name . '.txt'),
                $this->normalizeHuman($actual['human'], $name),
                $name,
            );
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

    /** @return array<string, array{json:array<string,mixed>,human:string}> */
    private function actualCases(string $projects): array
    {
        $cases = [];
        foreach ([
            'doctor.success' => ['doctor', [], null],
            'inspect.missing' => ['inspect', ['kind' => 'smart', 'id' => 'project.missing'], null],
            'doctor.malformed-config' => ['doctor', [], 'config'],
            'inspect.malformed-manifest' => ['inspect', ['kind' => 'smart', 'id' => 'project.notice'], 'manifest'],
        ] as $name => [$command, $arguments, $mutation]) {
            $project = $projects . '/' . $name;
            $this->filesystem->copyDirectory(dirname(__DIR__, 2) . '/stubs/portable', $project);
            if ($mutation === 'config') {
                $this->filesystem->put($project . '/docara.json', "{\n    \"schema\": \"docara.site.v1\",\n}\n");
            } elseif ($mutation === 'manifest') {
                $this->filesystem->put($project . '/smart/project.notice/manifest.json', "{\n    project: true\n}\n");
            }
            $json = new CommandTester(ApplicationFactory::create($project)->find($command));
            $json->execute($arguments + ['--json' => true]);
            $human = new CommandTester(ApplicationFactory::create($project)->find($command));
            $human->execute($arguments);
            $cases[$name] = [
                'json' => json_decode($json->getDisplay(), true, 512, JSON_THROW_ON_ERROR),
                'human' => $human->getDisplay(),
            ];
        }

        return $cases;
    }

    /** @param array<string,mixed> $value @return array<string,mixed> */
    private function normalize(array $value, string $name): array
    {
        $hash = str_repeat(match ($name) {
            'doctor.success' => 'a',
            'inspect.missing' => 'b',
            'doctor.malformed-config' => 'c',
            'inspect.malformed-manifest' => 'd',
        }, 64);
        $value['provenance']['engine_revision'] = 'fixture-revision';
        $value['provenance']['input_sha256'] = $hash;
        foreach ($value['diagnostics'] as &$diagnostic) {
            $diagnostic['provenance']['engine_revision'] = 'fixture-revision';
            $diagnostic['provenance']['input_sha256'] = $hash;
        }
        unset($diagnostic);

        return $value;
    }

    private function normalizeHuman(string $value, string $name): string
    {
        $status = $name === 'doctor.success' ? 'SUCCESS:' : 'ERROR:';
        $offset = strpos($value, $status);
        if ($offset !== false) {
            $value = substr($value, $offset);
        }
        $hash = str_repeat(match ($name) {
            'doctor.success' => 'a',
            'inspect.missing' => 'b',
            'doctor.malformed-config' => 'c',
            'inspect.malformed-manifest' => 'd',
        }, 64);
        $value = preg_replace('/sha256:[a-f0-9]{64}/', 'fixture-revision', $value) ?? $value;
        $value = preg_replace(
            '/("engine_revision":")[a-f0-9]{40}("?)/',
            '$1fixture-revision$2',
            $value,
        ) ?? $value;

        return preg_replace('/[a-f0-9]{64}/', $hash, $value) ?? $value;
    }
}
