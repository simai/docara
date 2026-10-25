<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use Simai\Docara\ComponentCatalog\ComponentParameterModel;
use Simai\Docara\ComponentCatalog\ParameterDeprecation;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Finds former parameter names and values in a page, with their line.
 *
 * The renderer already maps them to the current names (the page renders the
 * same); this scan only tells the author what to change. It reads component
 * attribute blocks the way the renderer does: directive openings, inline
 * components, image and quote attribute blocks. Code is not scanned, except
 * the Markdown source of an :::example, which the example renders.
 */
final readonly class ParameterDeprecationScanner
{
    public function __construct(private ?ComponentParameterModel $parameters = null) {}

    /** @return list<array{line:int, deprecation:ParameterDeprecation}> */
    public function scan(string $source): array
    {
        $parameters = $this->parameters ?? ComponentParameterModel::bundled();
        $parser = new AuthoringAttributeParser($parameters);
        $lines = preg_split('/\r\n|\n|\r/u', $source) ?: [];
        $found = [];
        $fence = null;
        $fenceScanned = false;
        $example = null;
        $start = 0;
        if (($lines[0] ?? null) !== null && rtrim($lines[0]) === '---') {
            for ($index = 1, $count = count($lines); $index < $count; $index++) {
                if (rtrim($lines[$index]) === '---') {
                    $start = $index + 1;
                    break;
                }
            }
        }
        for ($index = $start, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];
            if ($fence !== null) {
                if (preg_match('/^\s*' . preg_quote($fence[0], '/') . '{' . strlen($fence) . ',}\s*$/', $line) === 1) {
                    $fence = null;

                    continue;
                }
                if (! $fenceScanned) {
                    continue;
                }
            } elseif (preg_match('/^\s*(?<fence>`{3,}|~{3,})\s*(?<info>[A-Za-z0-9_+-]*)/', $line, $opening) === 1) {
                $fence = $opening['fence'];
                $fenceScanned = $example !== null && in_array(strtolower($opening['info']), ['markdown', 'md'], true);

                continue;
            }
            if ($fence === null && preg_match('/^(?<fence>:{3,})example(?:\s|\{|$)/', $line, $opening) === 1) {
                $example = $opening['fence'];
            } elseif ($fence === null && $example !== null && trim($line) === $example) {
                $example = null;
            }
            foreach ($this->attributeBlocks($line, $parameters) as [$component, $attributes]) {
                try {
                    [, $deprecations] = $parser->parseWithDeprecations($attributes, $component);
                } catch (PortableConfigurationException) {
                    // An invalid block is the build's error to report.
                    continue;
                }
                foreach ($deprecations as $deprecation) {
                    $found[] = ['line' => $index + 1, 'deprecation' => $deprecation];
                }
            }
        }

        return $found;
    }

    /** @return list<array{0:string,1:string}> component name and attribute source */
    private function attributeBlocks(string $line, ComponentParameterModel $parameters): array
    {
        $line = $this->withoutCodeSpans($line);
        $blocks = [];
        if (preg_match('/^\s*:{3,}(?<name>[a-z][a-z0-9_-]*)\s+\{(?<attributes>.*)\}\s*$/', $line, $directive) === 1) {
            if ($parameters->has($directive['name'])) {
                $blocks[] = [$directive['name'], $directive['attributes']];
            }

            return $blocks;
        }
        if (preg_match_all('/(?<![\pL\pN_]):(?<name>[a-z][a-z0-9_-]*)\[[^\]\r\n]*\]\{(?<attributes>[^}\r\n]*)\}/u', $line, $inline, PREG_SET_ORDER) > 0) {
            foreach ($inline as $match) {
                if ($parameters->has($match['name'])) {
                    $blocks[] = [$match['name'], $match['attributes']];
                }
            }
        }
        if (preg_match_all('/!\[[^\]]*\]\([^)]*\)\{(?<attributes>[^{}]+)\}/u', $line, $images, PREG_SET_ORDER) > 0) {
            foreach ($images as $match) {
                $blocks[] = ['image', $this->straightQuotes($match['attributes'])];
            }
        }
        if (preg_match('/^\s*(?:>\s*)*.*\{(?<attributes>(?=[^{}]*(?:author|source)\s*=)[^{}]+)\}\s*$/u', $line, $quote) === 1
            && preg_match('/!\[[^\]]*\]\([^)]*\)\{/u', $line) !== 1
        ) {
            $blocks[] = ['quote', $this->straightQuotes($quote['attributes'])];
        }

        return $blocks;
    }

    private function straightQuotes(string $attributes): string
    {
        return str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], $attributes);
    }

    private function withoutCodeSpans(string $line): string
    {
        return preg_replace_callback(
            '/(?<!`)(`+)(?!`).+?(?<!`)\1(?!`)/u',
            static fn (array $match): string => str_repeat(' ', strlen($match[0])),
            $line,
        ) ?? $line;
    }
}
