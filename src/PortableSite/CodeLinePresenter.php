<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use League\CommonMark\Util\Xml;
use Simai\Docara\Portable\PortableConfigurationException;

/**
 * Does a code block's line work at build time: line numbers, highlighted
 * and focused lines, diff lines from [!code ++] / [!code --] markers and
 * the prompt lines of a terminal block.
 *
 * The Framework highlighter colours a block by reading its text and
 * replacing its HTML, which would drop any line markup written into it. A
 * block with line options therefore arrives with its lines already wrapped
 * and marks itself highlighted (data-highlighted="lines"); the page script
 * colours it with the Framework's own engine and writes each coloured line
 * back into the line the build made. A block without line options renders
 * exactly as before and the Framework colours it as it always did.
 *
 * Everything here is a pure function of the source and the options, so the
 * same page builds to the same bytes.
 */
final class CodeLinePresenter
{
    /** The words a screen reader hears before a changed line; the site builder puts them in the page's language. */
    public const DIFF_LABEL_ATTRIBUTE = 'data-docara-code-diff-label';

    public const DIFF_LABELS = ['added' => 'added', 'removed' => 'removed'];

    /** The SIMAI Framework loader's opt-out from its [!name] shortcode scan. */
    public const SHORTCODE_GUARD = 'data-sf-observer="ignore"';

    /**
     * A marker is a comment at the end of a line, in whichever comment
     * syntax the language uses, as VitePress writes it: `// [!code ++]`,
     * `# [!code --]`, `<!-- [!code focus] -->`.
     */
    private const MARKER = '/[ \t]*(?:\/\/|\/\*|#|<!--|--|;|%|\')[ \t]*\[!code[ \t]+(\+\+|--|focus|highlight)\][ \t]*(?:\*\/|-->)?[ \t]*$/D';

    /** Languages whose source shows Markdown, where a marker is text to show, not an instruction. */
    private const LITERAL_LANGUAGES = ['markdown', 'md'];

    /**
     * @param  array{highlight?: string, numbers?: string, focus?: string, layout?: string}  $options
     * @param  list<int>|null  $lineNumbers  the number of each line when `numbers` is true and the lines are not consecutive (a file region)
     * @return array{enhanced: bool, html: string, lines: int, digits: int, focus: bool, terminal: bool, diff: bool}
     */
    public function present(string $source, string $language, array $options, ?array $lineNumbers = null): array
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $trailing = str_ends_with($source, "\n");
        if ($trailing) {
            $source = substr($source, 0, -1);
        }
        $texts = explode("\n", $source);
        $count = count($texts);
        $markers = ! in_array($language, self::LITERAL_LANGUAGES, true);

        $lines = [];
        foreach ($texts as $text) {
            $line = ['text' => $text, 'diff' => '', 'highlight' => false, 'focus' => false, 'prompt' => false, 'output' => false];
            if ($markers) {
                if (preg_match(self::MARKER, $text, $match, PREG_OFFSET_CAPTURE) === 1) {
                    $line['text'] = substr($text, 0, (int) $match[0][1]);
                    match ((string) $match[1][0]) {
                        '++' => $line['diff'] = 'add',
                        '--' => $line['diff'] = 'remove',
                        'focus' => $line['focus'] = true,
                        default => $line['highlight'] = true,
                    };
                }
                // [!!code ++] shows a marker as text instead of applying it.
                $line['text'] = str_replace('[!!code ', '[!code ', $line['text']);
            }
            $lines[] = $line;
        }

        foreach (self::ranges($options['highlight'] ?? '', 'highlight', $count) as $index) {
            $lines[$index - 1]['highlight'] = true;
        }
        foreach (self::ranges($options['focus'] ?? '', 'focus', $count) as $index) {
            $lines[$index - 1]['focus'] = true;
        }

        $terminal = ($options['layout'] ?? 'default') === 'terminal';
        $commands = false;
        if ($terminal) {
            foreach ($lines as &$line) {
                if (preg_match('/\A\$(?: |\z)/', $line['text']) === 1) {
                    $line['prompt'] = true;
                    $line['text'] = (string) substr($line['text'], 2);
                    $commands = true;
                } else {
                    $line['output'] = true;
                }
            }
            unset($line);
        }

        $numbers = $options['numbers'] ?? 'false';
        $start = match (true) {
            $numbers === 'false' => null,
            $numbers === 'true' => 1,
            preg_match('/\A[1-9][0-9]{0,5}\z/D', $numbers) === 1 => (int) $numbers,
            default => throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
                "Code option [numbers] must be true, false or the first line number, not [$numbers].",
            ),
        };

        $diff = in_array(true, array_map(static fn (array $line): bool => $line['diff'] !== '', $lines), true);
        $focus = in_array(true, array_column($lines, 'focus'), true);
        $highlight = in_array(true, array_column($lines, 'highlight'), true);
        // The Framework loader reads [!name …] in page text as a shortcode and
        // replaces it, inside code too. A line that shows such text is
        // wrapped on its own and marked data-sf-observer="ignore", the
        // Framework's opt-out, so the text stays as written.
        $guard = in_array(true, array_map(static fn (array $line): bool => str_contains($line['text'], '[!'), $lines), true);
        $enhanced = $start !== null || $diff || $focus || $highlight || $terminal || $guard;
        // `numbers` alone on a file excerpt numbers it as the file does.
        if ($numbers !== 'true' || $lineNumbers === []) {
            $lineNumbers = null;
        }
        $numberOf = static fn (int $index): int => $lineNumbers[$index] ?? ((int) $start + $index);
        $digits = $start === null ? 0 : strlen((string) $numberOf($count - 1));

        if (! $enhanced) {
            return [
                'enhanced' => false,
                'html' => Xml::escape(implode("\n", array_column($lines, 'text')) . ($trailing ? "\n" : '')),
                'lines' => $count,
                'digits' => 0,
                'focus' => false,
                'terminal' => false,
                'diff' => false,
            ];
        }

        $html = '';
        foreach ($lines as $index => $line) {
            $attributes = '';
            if ($line['diff'] !== '') {
                $attributes .= ' data-line-diff="' . $line['diff'] . '"';
            }
            if ($line['highlight']) {
                $attributes .= ' data-line-highlight';
            }
            if ($line['focus']) {
                $attributes .= ' data-line-focus';
            }
            if ($line['prompt']) {
                $attributes .= ' data-line-prompt';
            }
            if ($line['output']) {
                $attributes .= ' data-line-output';
            }
            // The copy button copies the code as it reads after the change:
            // without removed lines, and in a terminal block with prompts
            // only the commands, never their output.
            if ($line['diff'] === 'remove' || ($commands && $line['output'])) {
                $attributes .= ' data-copy-skip';
            }
            $html .= '<span class="docara-code-line"' . $attributes . '>';
            if ($start !== null) {
                $html .= '<span class="docara-code-line__number" aria-hidden="true" data-line-number="' . $numberOf($index) . '"></span>';
            }
            if ($diff) {
                $html .= '<span class="docara-code-line__sign" aria-hidden="true"></span>';
            }
            if ($line['diff'] !== '') {
                $label = $line['diff'] === 'add' ? 'added' : 'removed';
                $html .= '<span class="docara-code-line__status sr-only" ' . self::DIFF_LABEL_ATTRIBUTE . '="' . $label . '">'
                    . self::DIFF_LABELS[$label] . ': </span>';
            }
            if ($line['prompt']) {
                $html .= '<span class="docara-code-line__prompt" aria-hidden="true"></span>';
            }
            $html .= '<span class="docara-code-line__content"'
                . (str_contains($line['text'], '[!') ? ' ' . self::SHORTCODE_GUARD : '')
                . '>' . $this->escape($line['text']) . '</span></span>';
        }

        return [
            'enhanced' => true,
            'html' => $html,
            'lines' => $count,
            'digits' => $digits,
            'focus' => $focus,
            'terminal' => $terminal,
            'diff' => $diff,
        ];
    }

    /**
     * Line numbers named by a range list: `1,4-6`, counted from the
     * block's first line. Commas or spaces separate the parts.
     *
     * @return list<int>
     */
    public static function ranges(string $value, string $option, int $count): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }
        $indexes = [];
        foreach (preg_split('/\s*[,\s]\s*/', $value) ?: [] as $part) {
            if (preg_match('/\A(?<start>[1-9][0-9]*)(?:-(?<end>[1-9][0-9]*))?\z/D', $part, $match) !== 1) {
                throw new PortableConfigurationException(
                    'MARKDOWN_CODE_RANGE_INVALID',
                    "Code option [$option] must list line numbers and ranges such as 1,4-6, not [$value].",
                );
            }
            $start = (int) $match['start'];
            $end = isset($match['end']) && $match['end'] !== '' ? (int) $match['end'] : $start;
            if ($end < $start || $end > $count) {
                throw new PortableConfigurationException(
                    'MARKDOWN_CODE_RANGE_INVALID',
                    "Code option [$option] names lines [$part], but the block has $count line(s) and a range must not run backwards.",
                );
            }
            for ($index = $start; $index <= $end; $index++) {
                $indexes[$index] = $index;
            }
        }
        ksort($indexes);

        return array_values($indexes);
    }

    private function escape(string $value): string
    {
        return Xml::escape($value);
    }
}
