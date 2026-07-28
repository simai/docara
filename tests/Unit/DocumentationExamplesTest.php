<?php

declare(strict_types=1);

namespace Tests\Unit;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;
use SplFileInfo;

final class DocumentationExamplesTest extends TestCase
{
    private const MARKED_JSON_PATTERN = <<<'REGEX'
~^<!-- docara-example (?<kind>valid|invalid) schema=(?<schema>[a-z0-9._-]+\.schema\.json)(?: error=(?<error>[A-Z][A-Z0-9_]*))? -->\R```json[ \t]*\R(?<json>.*?)\R```[ \t]*$~ms
REGEX;

    #[Test]
    public function marked_json_examples_are_executed_against_the_real_schema_validator(): void
    {
        $schemas = new SchemaRepository;
        $validExamples = 0;
        $invalidExamples = 0;

        foreach ($this->markdownDocuments() as $path) {
            $markdown = (string) file_get_contents($path);
            $declaredMarkers = substr_count($markdown, '<!-- docara-example ');
            preg_match_all(self::MARKED_JSON_PATTERN, $markdown, $matches, PREG_SET_ORDER);

            self::assertCount(
                $declaredMarkers,
                $matches,
                $this->relativeToRepository($path)
                . ' contains a malformed marker or a marker not immediately followed by a fenced JSON block.',
            );

            foreach ($matches as $index => $match) {
                $context = sprintf(
                    '%s marked example %d',
                    $this->relativeToRepository($path),
                    $index + 1,
                );
                $document = $this->decodeObject($match['json'], $context);
                $errorCode = $match['error'] ?? '';

                if ($match['kind'] === 'valid') {
                    self::assertSame('', $errorCode, "$context: valid markers cannot declare an error.");
                    $schemas->assertValid($document, $match['schema']);
                    $validExamples++;

                    continue;
                }

                self::assertNotSame('', $errorCode, "$context: invalid markers require an exact error code.");
                try {
                    $schemas->assertValid($document, $match['schema']);
                    self::fail("$context unexpectedly passed schema validation.");
                } catch (PortableConfigurationException $exception) {
                    self::assertSame($errorCode, $exception->errorCode, $context);
                }
                $invalidExamples++;
            }
        }

        self::assertGreaterThan(0, $validExamples, 'Documentation needs at least one executed valid example.');
        self::assertGreaterThan(0, $invalidExamples, 'Documentation needs at least one executed invalid example.');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function decodeObject(string $json, string $context): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded, "$context must decode to a JSON object.");
        self::assertFalse(array_is_list($decoded), "$context must decode to a JSON object.");

        return $decoded;
    }

    /** @return list<string> */
    private function markdownDocuments(): array
    {
        $paths = [];
        $root = $this->repositoryRoot() . '/docs/site/content';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }

    private function relativeToRepository(string $path): string
    {
        return ltrim(str_replace($this->repositoryRoot(), '', $path), '/');
    }

    private function repositoryRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
