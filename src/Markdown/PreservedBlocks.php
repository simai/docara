<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The `:::preserved` block keeps text in the Markdown source that the built
 * site never shows: notes for authors, former wording, text kept for a later
 * release. The build drops the whole block, its attributes included, before
 * anything reads the page; the author gets the warning PRESERVED_NOT_SHOWN
 * so the hidden text is a choice, not an accident.
 *
 * Blocks are found line by line, outside fenced code, at any depth: a
 * `:::preserved` line inside another block's body is dropped the same way.
 * The block closes on the first line that repeats its opening fence; a block
 * that holds other blocks opens with a longer fence, as containers do.
 */
final class PreservedBlocks
{
    public const NAME = 'preserved';

    public const CODE = 'PRESERVED_NOT_SHOWN';

    /** The opening fence of a `:::preserved` line, or null. */
    public static function opening(string $line): ?string
    {
        $pattern = '/^(?<fence>:{3,})' . self::NAME
            . '(?:[ \t]+\{(?:' . DirectiveOpeningMatcher::ATTRIBUTES . ')\})?[ \t]*$/u';

        return preg_match($pattern, $line, $match) === 1 ? $match['fence'] : null;
    }

    /**
     * The Markdown without its preserved blocks. Each dropped line becomes an
     * empty line, so every later line keeps its number in diagnostics.
     *
     * @throws PortableConfigurationException MARKDOWN_BLOCK_UNCLOSED for a block without its closing line
     */
    public static function strip(string $markdown, ?string $source = null): string
    {
        if (! str_contains($markdown, ':::' . self::NAME)) {
            return $markdown;
        }
        $lines = preg_split('/(\r\n|\n|\r)/u', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($lines)) {
            return $markdown;
        }
        // Even keys are lines, odd keys their line breaks.
        $text = [];
        foreach ($lines as $key => $line) {
            if ($key % 2 === 0) {
                $text[] = $line;
            }
        }
        foreach (self::blocks($text) as $block) {
            if ($block['end'] === null) {
                $line = $block['start'] + 1;
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_UNCLOSED',
                    'Markdown block [' . self::NAME . "] at line [$line] is not closed.",
                    diagnosticPath: $source,
                    diagnosticPointer: '/markdown',
                    diagnosticLine: $source === null ? null : $line,
                    diagnosticColumn: $source === null ? null : 1,
                );
            }
            for ($index = $block['start']; $index <= $block['end']; $index++) {
                $lines[$index * 2] = '';
            }
        }

        return implode('', $lines);
    }

    /**
     * The 1-based line of each preserved block in a page file, front matter
     * counted, for the PRESERVED_NOT_SHOWN warning.
     *
     * @return list<int>
     */
    public static function scan(string $source): array
    {
        $lines = preg_split('/\r\n|\n|\r/u', $source) ?: [];
        $start = 0;
        if (($lines[0] ?? null) !== null && rtrim($lines[0]) === '---') {
            for ($index = 1, $count = count($lines); $index < $count; $index++) {
                if (rtrim($lines[$index]) === '---') {
                    $start = $index + 1;
                    break;
                }
            }
        }

        return array_map(
            static fn (array $block): int => $block['start'] + 1,
            self::blocks($lines, $start),
        );
    }

    /**
     * @param  list<string>  $lines
     * @return list<array{start:int, end:int|null}> 0-based line indexes
     */
    private static function blocks(array $lines, int $from = 0): array
    {
        $blocks = [];
        $code = null;
        $open = null;
        for ($index = $from, $count = count($lines); $index < $count; $index++) {
            $line = $lines[$index];
            if ($code !== null) {
                if (preg_match('/^ {0,3}' . preg_quote($code[0], '/') . '{' . strlen($code) . ',}[ \t]*$/', $line) === 1) {
                    $code = null;
                }

                continue;
            }
            if (preg_match('/^ {0,3}(?<fence>`{3,}|~{3,})/', $line, $fence) === 1
                && ! ($fence['fence'][0] === '`' && str_contains(substr($line, strlen($fence[0])), '`'))
            ) {
                $code = $fence['fence'];

                continue;
            }
            if ($open !== null) {
                if (trim($line) === $open['fence']) {
                    $blocks[] = ['start' => $open['start'], 'end' => $index];
                    $open = null;
                }

                continue;
            }
            $fence = self::opening($line);
            if ($fence !== null) {
                $open = ['start' => $index, 'fence' => $fence];
            }
        }
        if ($open !== null) {
            $blocks[] = ['start' => $open['start'], 'end' => null];
        }

        return $blocks;
    }
}
