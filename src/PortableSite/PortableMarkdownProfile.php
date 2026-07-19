<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use JsonException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final readonly class PortableMarkdownProfile
{
    /** @var array<string, string> */
    private const CAPABILITY_FILES = [
        'native.code' => 'native.code.json',
        'native.headings_and_text' => 'native.headings_and_text.json',
        'native.links_and_images' => 'native.links_and_images.json',
        'native.lists_and_quotes' => 'native.lists_and_quotes.json',
        'native.table' => 'native.table.json',
    ];

    public function __construct(
        private string $resourceRoot,
        private SchemaRepository $schemas = new SchemaRepository,
    ) {}

    public static function bundled(): self
    {
        return new self(dirname(__DIR__, 2) . '/resources/component-catalog/native');
    }

    public function environment(): Environment
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new SmartPunctExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new TableExtension);

        return $environment;
    }

    /** @return list<array<string, mixed>> */
    public function entries(): array
    {
        if (is_link($this->resourceRoot) || ! is_dir($this->resourceRoot)) {
            throw new PortableConfigurationException(
                'NATIVE_MARKDOWN_PROFILE_INVALID',
                'The bundled native Markdown profile is missing or unsafe.',
            );
        }
        $actualFiles = array_map(
            'basename',
            glob($this->resourceRoot . '/*.json', GLOB_NOSORT) ?: [],
        );
        $expectedFiles = array_values(self::CAPABILITY_FILES);
        sort($actualFiles, SORT_STRING);
        sort($expectedFiles, SORT_STRING);
        if ($actualFiles !== $expectedFiles) {
            throw new PortableConfigurationException(
                'NATIVE_MARKDOWN_PROFILE_INVALID',
                'The bundled native Markdown capability inventory does not match the enabled profile.',
            );
        }

        $entries = [];
        foreach (self::CAPABILITY_FILES as $id => $file) {
            $path = $this->resourceRoot . '/' . $file;
            if (is_link($path) || ! is_file($path)) {
                throw new PortableConfigurationException(
                    'NATIVE_MARKDOWN_PROFILE_INVALID',
                    "Native Markdown capability [$id] is missing or unsafe.",
                );
            }
            try {
                $entry = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new PortableConfigurationException(
                    'NATIVE_MARKDOWN_PROFILE_INVALID',
                    "Native Markdown capability [$id] is not valid JSON.",
                    $exception,
                );
            }
            if (! is_array($entry)
                || ($entry['id'] ?? null) !== $id
                || ($entry['family'] ?? null) !== 'native_markdown'
                || ($entry['lifecycle'] ?? null) !== 'supported'
                || ($entry['authoring']['syntax'] ?? null) !== 'markdown'
                || ($entry['provenance']['source_kind'] ?? null) !== 'portable_markdown_profile'
                || ($entry['provenance']['profile_id'] ?? null) !== 'docara.portable_markdown_profile.v1'
            ) {
                throw new PortableConfigurationException(
                    'NATIVE_MARKDOWN_PROFILE_INVALID',
                    "Native Markdown capability [$id] does not match the enabled profile.",
                );
            }
            $this->schemas->assertValid($entry, 'component-catalog-entry.schema.json');
            $entries[] = $entry;
        }

        return $entries;
    }
}
