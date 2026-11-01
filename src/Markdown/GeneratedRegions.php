<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * A region between `<!-- docara:generated -->` and `<!-- /docara:generated -->`
 * is written by the project's own tooling, not by hand. Each marker stands on
 * its own line; a marker inside a fenced code block is sample text. Markers
 * open and close in pairs, without nesting.
 */
final readonly class GeneratedRegions
{
    public const OPEN = '<!-- docara:generated -->';

    public const CLOSE = '<!-- /docara:generated -->';

    /**
     * Each line of the Markdown with whether it is generated. Marker lines
     * themselves are left out.
     *
     * @return list<array{line:string,generated:bool}>
     */
    public function lines(string $markdown, string $path, string $code): array
    {
        $lines = [];
        $fence = null;
        $inRegion = false;
        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $match) === 1) {
                if ($fence === null) {
                    $fence = $match[1];
                } elseif ($match[1][0] === $fence[0] && strlen($match[1]) >= strlen($fence)) {
                    $fence = null;
                }
            } elseif ($fence === null) {
                $marker = trim($line);
                if ($marker === self::OPEN || $marker === self::CLOSE) {
                    if ($inRegion === ($marker === self::OPEN)) {
                        throw $this->unpaired($path, $code);
                    }
                    $inRegion = ! $inRegion;

                    continue;
                }
            }
            $lines[] = ['line' => $line, 'generated' => $inRegion];
        }
        if ($inRegion) {
            throw $this->unpaired($path, $code);
        }

        return $lines;
    }

    /** The Markdown without its generated regions and their markers. */
    public function strip(string $markdown, string $path, string $code): string
    {
        if (! str_contains($markdown, 'docara:generated')) {
            return $markdown;
        }
        $kept = [];
        foreach ($this->lines($markdown, $path, $code) as $line) {
            if (! $line['generated']) {
                $kept[] = $line['line'];
            }
        }

        return implode("\n", $kept);
    }

    private function unpaired(string $path, string $code): PortableConfigurationException
    {
        return new PortableConfigurationException($code, "Generated region markers in [$path] must open and close in pairs, without nesting.");
    }
}
