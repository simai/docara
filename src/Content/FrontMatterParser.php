<?php

declare(strict_types=1);

namespace Simai\Docara\Content;

use Simai\Docara\Authoring\AuthoringProfileRegistry;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class FrontMatterParser
{
    private const KEYS = ['title', 'description', 'tags', 'draft', 'translation_key', 'profile'];

    public function parse(string $markdown, string $source): FrontMatterDocument
    {
        $lines = preg_split('/\R/u', $markdown);
        if ($lines === false || ($lines[0] ?? null) !== '---') {
            return new FrontMatterDocument($markdown, []);
        }

        $closing = null;
        foreach ($lines as $index => $line) {
            if ($index > 0 && $line === '---') {
                $closing = $index;

                break;
            }
        }
        if ($closing === null) {
            $this->fail('FRONT_MATTER_UNTERMINATED', $source, 1, 1, 'Add a closing --- delimiter.');
        }

        $metadata = [];
        for ($index = 1; $index < $closing; $index++) {
            $line = $lines[$index];
            if (trim($line) === '') {
                continue;
            }
            if (preg_match('/^([a-z_]+):(?:\h*(.*))$/u', $line, $match) !== 1) {
                $this->fail(
                    'FRONT_MATTER_SYNTAX_INVALID',
                    $source,
                    $index + 1,
                    1,
                    'Use one key: value pair per line.',
                );
            }
            $key = $match[1];
            if (! in_array($key, self::KEYS, true)) {
                $this->fail(
                    'FRONT_MATTER_KEY_UNKNOWN',
                    $source,
                    $index + 1,
                    1,
                    'Supported keys: ' . implode(', ', self::KEYS) . '.',
                );
            }
            if (array_key_exists($key, $metadata)) {
                $this->fail('FRONT_MATTER_KEY_DUPLICATE', $source, $index + 1, 1, "Remove duplicate key [$key].");
            }

            $column = (int) strpos($line, ':') + 2;
            $metadata[$key] = $this->value($key, trim($match[2]), $source, $index + 1, $column);
        }

        for ($index = 0; $index <= $closing; $index++) {
            $lines[$index] = '';
        }

        return new FrontMatterDocument(implode("\n", $lines), $metadata);
    }

    private function value(string $key, string $value, string $source, int $line, int $column): string|bool|array
    {
        if ($key === 'draft') {
            if (! in_array($value, ['true', 'false'], true)) {
                $this->fail('FRONT_MATTER_DRAFT_INVALID', $source, $line, $column, 'Use draft: true or draft: false.');
            }

            return $value === 'true';
        }
        if ($key === 'tags') {
            if (preg_match('/^\[(.*)]$/u', $value, $match) !== 1) {
                $this->fail('FRONT_MATTER_TAGS_INVALID', $source, $line, $column, 'Use an inline list such as tags: [ui, status].');
            }
            $inside = trim($match[1]);
            if ($inside === '') {
                return [];
            }
            $tags = array_map('trim', explode(',', $inside));
            foreach ($tags as $tag) {
                if (preg_match('/^[a-z0-9](?:[a-z0-9._-]{0,62}[a-z0-9])?$/D', $tag) !== 1) {
                    $this->fail('FRONT_MATTER_TAG_INVALID', $source, $line, $column, "Tag [$tag] must be a lowercase identifier.");
                }
            }
            if (count($tags) !== count(array_unique($tags))) {
                $this->fail('FRONT_MATTER_TAG_DUPLICATE', $source, $line, $column, 'Remove duplicate tags.');
            }

            return $tags;
        }

        $value = $this->scalar($value, $source, $line, $column);
        if ($key === 'translation_key'
            && preg_match('/^[a-z0-9](?:[a-z0-9._-]{0,126}[a-z0-9])?$/D', $value) !== 1
        ) {
            $this->fail('FRONT_MATTER_TRANSLATION_KEY_INVALID', $source, $line, $column, 'Use a stable lowercase identifier.');
        }
        if ($key === 'profile' && ! in_array($value, AuthoringProfileRegistry::IDS, true)) {
            $this->fail('FRONT_MATTER_PROFILE_INVALID', $source, $line, $column, 'Use a built-in Docara authoring profile.');
        }

        return $value;
    }

    private function scalar(string $value, string $source, int $line, int $column): string
    {
        if ($value === '') {
            $this->fail('FRONT_MATTER_VALUE_EMPTY', $source, $line, $column, 'Provide a non-empty value.');
        }
        if (($value[0] ?? '') === '"') {
            try {
                $decoded = json_decode($value, true, 2, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $this->fail('FRONT_MATTER_STRING_INVALID', $source, $line, $column, 'Use valid JSON double-quoted text.');
            }
            if (! is_string($decoded) || trim($decoded) === '') {
                $this->fail('FRONT_MATTER_VALUE_EMPTY', $source, $line, $column, 'Provide a non-empty string.');
            }

            return $decoded;
        }
        if (str_contains($value, '[') || str_contains($value, ']') || str_contains($value, '{') || str_contains($value, '}')) {
            $this->fail('FRONT_MATTER_STRING_INVALID', $source, $line, $column, 'Quote text containing brackets.');
        }

        return $value;
    }

    private function fail(string $code, string $source, int $line, int $column, string $message): never
    {
        throw new PortableConfigurationException(
            $code,
            "$message Source [$source], line [$line], column [$column].",
        );
    }
}
