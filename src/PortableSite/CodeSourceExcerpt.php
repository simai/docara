<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Portable\PortableConfigurationException;

/**
 * The part of a project file a :::code block shows: a line range, a named
 * region, or the lines between two marker lines, optionally without the
 * indentation they share.
 *
 * A region is marked in the file in the language's own comment syntax, the
 * way VitePress and editors fold them: `// #region name` … `// #endregion`,
 * `# region name`, `<!-- #region name -->`, `/* #region name *\/`. The
 * marker lines are not shown, nor are the markers of other regions inside
 * the one shown.
 */
final class CodeSourceExcerpt
{
    private const COMMENT_OPEN = '(?:\/\/|\/\*|<!--|#|--|;|%|\'|REM\b)?';

    private const COMMENT_CLOSE = '(?:\*\/|-->)?';

    /**
     * @param  array<string, string>  $attributes  the block's options
     * @return array{text: string, numbers: list<int>}
     */
    public function extract(string $source, array $attributes): array
    {
        $lineRange = trim($attributes['lines'] ?? '');
        $region = trim($attributes['region'] ?? '');
        $startAfter = $attributes['start_after'] ?? '';
        $endBefore = $attributes['end_before'] ?? '';
        $selectors = array_filter([
            'lines' => $lineRange !== '',
            'region' => $region !== '',
            'start_after/end_before' => $startAfter !== '' || $endBefore !== '',
        ]);
        if (count($selectors) > 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_CODE_OPTIONS_CONFLICT',
                'A :::code block selects its lines one way only; it names [' . implode('] and [', array_keys($selectors)) . '].',
            );
        }

        $lines = preg_split('/\r\n|\n|\r/u', $source) ?: [];
        if ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }
        $numbers = $lines === [] ? [] : range(1, count($lines));
        if ($lineRange !== '') {
            if (preg_match('/\A(?<start>[1-9][0-9]*)(?:-(?<end>[1-9][0-9]*))?\z/D', $lineRange, $match) !== 1) {
                throw new PortableConfigurationException('MARKDOWN_CODE_LINES_INVALID', 'External code lines must use N or N-M.');
            }
            $start = (int) $match['start'];
            $end = isset($match['end']) && $match['end'] !== '' ? (int) $match['end'] : $start;
            if ($end < $start || $start > count($lines)) {
                throw new PortableConfigurationException('MARKDOWN_CODE_LINES_INVALID', 'External code line range is outside the source file.');
            }
            $lines = array_slice($lines, $start - 1, $end - $start + 1);
            $numbers = array_slice($numbers, $start - 1, $end - $start + 1);
        } elseif ($region !== '') {
            [$lines, $numbers] = $this->region($lines, $region);
        } elseif ($startAfter !== '' || $endBefore !== '') {
            [$lines, $numbers] = $this->between($lines, $startAfter, $endBefore);
        }

        if (($attributes['dedent'] ?? 'false') === 'true') {
            $lines = $this->dedent($lines);
        }

        return ['text' => implode("\n", $lines), 'numbers' => $numbers];
    }

    /**
     * @param  list<string>  $lines
     * @return array{0: list<string>, 1: list<int>} the shown lines and their file line numbers
     */
    private function region(array $lines, string $name): array
    {
        if (preg_match('/\A[A-Za-z0-9_.-]{1,64}\z/D', $name) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_CODE_REGION_UNKNOWN',
                "Code region name [$name] may use only Latin letters, digits, dot, dash and underscore.",
            );
        }
        $start = null;
        foreach ($lines as $index => $line) {
            $marker = $this->marker($line);
            if ($start === null) {
                if ($marker !== null && $marker[0] === 'start' && $marker[1] === $name) {
                    $start = $index;
                }

                continue;
            }
            if ($marker !== null && $marker[0] === 'end' && ($marker[1] === $name || $marker[1] === '')) {
                $shown = [];
                $numbers = [];
                for ($inner = $start + 1; $inner < $index; $inner++) {
                    if ($this->marker($lines[$inner]) === null) {
                        $shown[] = $lines[$inner];
                        $numbers[] = $inner + 1;
                    }
                }

                return [$shown, $numbers];
            }
            if ($marker !== null && $marker[0] === 'start' && $marker[1] === $name) {
                break;
            }
        }

        throw new PortableConfigurationException(
            'MARKDOWN_CODE_REGION_UNKNOWN',
            $start === null
                ? "The source file has no region [$name]; mark it with #region $name and #endregion in a comment."
                : "Region [$name] in the source file is not closed with #endregion.",
        );
    }

    /**
     * @param  list<string>  $lines
     * @return array{0: list<string>, 1: list<int>}
     */
    private function between(array $lines, string $startAfter, string $endBefore): array
    {
        $from = 0;
        if ($startAfter !== '') {
            $from = null;
            foreach ($lines as $index => $line) {
                if (str_contains($line, $startAfter)) {
                    $from = $index + 1;
                    break;
                }
            }
            if ($from === null) {
                throw new PortableConfigurationException(
                    'MARKDOWN_CODE_REGION_UNKNOWN',
                    "No line of the source file contains the start_after text [$startAfter].",
                );
            }
        }
        $to = count($lines);
        if ($endBefore !== '') {
            $to = null;
            for ($index = $from; $index < count($lines); $index++) {
                if (str_contains($lines[$index], $endBefore)) {
                    $to = $index;
                    break;
                }
            }
            if ($to === null) {
                throw new PortableConfigurationException(
                    'MARKDOWN_CODE_REGION_UNKNOWN',
                    "No line of the source file after the start contains the end_before text [$endBefore].",
                );
            }
        }
        if ($to <= $from) {
            throw new PortableConfigurationException(
                'MARKDOWN_CODE_RANGE_INVALID',
                'The start_after and end_before lines leave no source lines to show.',
            );
        }

        return [array_slice($lines, $from, $to - $from), range($from + 1, $to)];
    }

    /**
     * A region marker line: ['start'|'end', name].
     *
     * @return array{0: string, 1: string}|null
     */
    private function marker(string $line): ?array
    {
        if (preg_match(
            '/\A\s*' . self::COMMENT_OPEN . '\s*#?\s*(?<kind>end\s?region|region)\b\s*(?<name>[A-Za-z0-9_.-]*)\s*' . self::COMMENT_CLOSE . '\s*\z/iD',
            $line,
            $match,
        ) !== 1) {
            return null;
        }

        return [str_starts_with(strtolower((string) $match['kind']), 'end') ? 'end' : 'start', (string) $match['name']];
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function dedent(array $lines): array
    {
        $shared = null;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            preg_match('/\A[ \t]*/', $line, $match);
            $indent = (string) $match[0];
            if ($shared === null) {
                $shared = $indent;

                continue;
            }
            $length = 0;
            while ($length < strlen($shared) && $length < strlen($indent) && $shared[$length] === $indent[$length]) {
                $length++;
            }
            $shared = substr($shared, 0, $length);
        }
        if ($shared === null || $shared === '') {
            return $lines;
        }

        return array_map(
            static fn (string $line): string => str_starts_with($line, $shared) ? substr($line, strlen($shared)) : ltrim($line, " \t"),
            $lines,
        );
    }
}
