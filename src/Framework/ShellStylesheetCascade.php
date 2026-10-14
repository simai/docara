<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

/**
 * Decides which first-frame stylesheet segments may change places without
 * changing the cascade.
 *
 * The browser only consults source order to break a tie between two
 * declarations of the same property, in the same cascade layer, with the same
 * importance. Two segments therefore commute when no declaration of one can
 * tie with a declaration of the other: they declare no common property
 * (shorthands, logical/physical twins and vendor aliases count as the same
 * property) in a common layer, and they share no name-keyed global rule
 * (@keyframes, @property, @font-face, ...). Layer order is fixed by the
 * Framework foundation stylesheet, which every page links before the shell
 * stylesheets; a segment that introduces a layer the foundation does not
 * declare stays in order with every other such segment.
 *
 * The analysis is deliberately conservative: anything it does not
 * understand (an unknown at-rule, @import, an anonymous layer, a parse
 * error) makes the segment conflict with every other segment, which keeps it
 * exactly where the page put it.
 */
final class ShellStylesheetCascade
{
    /**
     * Properties that set the same computed values (logical/physical twins,
     * aliases, shorthand pairs that do not share a name prefix).
     */
    private const GROUPS = [
        ['top', 'right', 'bottom', 'left', 'inset', 'inset-block', 'inset-inline', 'inset-block-start', 'inset-block-end', 'inset-inline-start', 'inset-inline-end', 'position-area', 'inset-area'],
        ['width', 'height', 'inline-size', 'block-size', 'logical-width', 'logical-height'],
        ['min-width', 'min-height', 'min-inline-size', 'min-block-size', 'min-logical-width', 'min-logical-height'],
        ['max-width', 'max-height', 'max-inline-size', 'max-block-size', 'max-logical-width', 'max-logical-height'],
        ['gap', 'row-gap', 'column-gap', 'grid-gap', 'grid-row-gap', 'grid-column-gap', 'grid'],
        ['columns', 'column-width', 'column-count'],
        ['place-content', 'align-content', 'justify-content'],
        ['place-items', 'align-items', 'justify-items'],
        ['place-self', 'align-self', 'justify-self'],
        ['white-space', 'white-space-collapse', 'text-wrap', 'text-wrap-mode', 'text-wrap-style'],
        ['overflow-wrap', 'word-wrap'],
        ['vertical-align', 'alignment-baseline', 'baseline-shift', 'baseline-source'],
        ['page-break-before', 'break-before'],
        ['page-break-after', 'break-after'],
        ['page-break-inside', 'break-inside'],
        ['print-color-adjust', 'color-adjust'],
        ['line-clamp', 'max-lines', 'block-ellipsis', 'continue'],
    ];

    /** Families whose members are all treated as setting the same values. */
    private const MUTUAL = [
        'margin', 'padding', 'scroll-margin', 'scroll-padding', 'overflow', 'overscroll-behavior',
        'contain-intrinsic', 'grid', 'flex', 'mask', 'column', 'text-emphasis', 'font-synthesis',
        'offset', 'inset',
    ];

    /** Conditional group rules: their declarations still compete in their layer. */
    private const CONDITIONAL = ['media', 'supports', 'container', 'scope', 'starting-style', 'document'];

    /** Global rules keyed by name: two of them interact only when the names match. */
    private const NAMED = ['keyframes', 'property', 'counter-style', 'position-try', 'font-feature-values', 'font-palette-values'];

    /** Global rules without a usable key: any two of them may interact. */
    private const GLOBAL = ['font-face', 'page', 'view-transition'];

    /** @var array<string, true> */
    private array $declaredLayers = [];

    /** @var array<string, array{self: array<string, true>, anc: array<string, true>}> */
    private array $propertyTokens = [];

    /** @var array<string, int> */
    private array $groupIndex = [];

    private string $css = '';

    private int $length = 0;

    /** @var array{universal: bool, global: array<string, true>, layers: array<string, array{self: array<string, true>, anc: array<string, true>}>} */
    private array $signature = ['universal' => false, 'global' => [], 'layers' => []];

    /**
     * @param  list<string>  $declaredLayers  layer names the foundation
     *                                        stylesheet establishes, in any order
     */
    public function __construct(array $declaredLayers)
    {
        foreach ($declaredLayers as $name) {
            $parts = explode('.', $name);
            for ($i = 1; $i <= count($parts); $i++) {
                $this->declaredLayers[implode('.', array_slice($parts, 0, $i))] = true;
            }
        }
        foreach (self::GROUPS as $index => $group) {
            foreach ($group as $property) {
                $this->groupIndex[$property] = $index;
            }
        }
    }

    /**
     * The layer names established by the top-level `@layer a, b;`
     * statements of a stylesheet.
     *
     * @return list<string>
     */
    public static function declaredLayers(string $css): array
    {
        $reader = new self([]);
        $reader->css = $css;
        $reader->length = strlen($css);
        $names = [];
        try {
            $i = 0;
            while (true) {
                $i = $reader->skipSpace($i);
                if ($i >= $reader->length) {
                    break;
                }
                [$end, $prelude] = $reader->prelude($i);
                $terminator = $reader->css[$end] ?? '';
                if ($terminator === ';') {
                    if (preg_match('/^@layer\s+(.+)$/Ds', $prelude, $match) === 1) {
                        foreach (explode(',', $match[1]) as $name) {
                            $names[$reader->layerName('', trim($name))] = true;
                        }
                    }
                    $i = $end + 1;
                } elseif ($terminator === '{') {
                    $i = $reader->skipBlock($end + 1);
                } else {
                    break;
                }
            }
        } catch (\UnexpectedValueException) {
            return [];
        }

        return array_keys($names);
    }

    /**
     * @return array{universal: bool, global: array<string, true>, layers: array<string, array{self: array<string, true>, anc: array<string, true>}>}
     */
    public function signature(string $css): array
    {
        $this->css = $css;
        $this->length = strlen($css);
        $this->signature = ['universal' => false, 'global' => [], 'layers' => []];
        try {
            $end = $this->block(0, '', false, true);
            if ($end < $this->length) {
                throw new \UnexpectedValueException('unbalanced');
            }
        } catch (\UnexpectedValueException) {
            $this->signature = ['universal' => true, 'global' => [], 'layers' => []];
        }
        $signature = $this->signature;
        $this->css = '';
        $this->signature = ['universal' => false, 'global' => [], 'layers' => []];

        return $signature;
    }

    /**
     * @param  array{universal: bool, global: array<string, true>, layers: array<string, array{self: array<string, true>, anc: array<string, true>}>}  $a
     * @param  array{universal: bool, global: array<string, true>, layers: array<string, array{self: array<string, true>, anc: array<string, true>}>}  $b
     */
    public static function conflicts(array $a, array $b): bool
    {
        if ($a['universal'] || $b['universal']) {
            return true;
        }
        if (self::intersects($a['global'], $b['global'])) {
            return true;
        }
        foreach ($a['layers'] as $layer => $tokens) {
            $other = $b['layers'][$layer] ?? null;
            if ($other === null) {
                continue;
            }
            if (self::intersects($tokens['self'], $other['self'])
                || self::intersects($tokens['self'], $other['anc'])
                || self::intersects($tokens['anc'], $other['self'])
            ) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, true> $a @param array<string, true> $b */
    private static function intersects(array $a, array $b): bool
    {
        if (count($a) > count($b)) {
            [$a, $b] = [$b, $a];
        }
        foreach ($a as $token => $_) {
            if (isset($b[$token])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read the items of a block (or of the stylesheet when $topLevel) until
     * its closing brace; returns the offset after it.
     */
    private function block(int $i, string $layer, bool $inStyle, bool $topLevel = false): int
    {
        while (true) {
            $i = $this->skipSpace($i);
            if ($i >= $this->length) {
                if (! $topLevel) {
                    throw new \UnexpectedValueException('eof');
                }

                return $i;
            }
            if ($this->css[$i] === '}') {
                if ($topLevel) {
                    throw new \UnexpectedValueException('unbalanced');
                }

                return $i + 1;
            }
            [$end, $prelude] = $this->prelude($i);
            if ($end >= $this->length) {
                throw new \UnexpectedValueException('eof');
            }
            $terminator = $this->css[$end];
            if ($terminator === ';' || $terminator === '}') {
                $this->statement($prelude, $layer, $inStyle);
                $i = $terminator === ';' ? $end + 1 : $end;

                continue;
            }
            // A block follows the prelude.
            if (str_starts_with($prelude, '--')) {
                // A custom property whose value holds a {} block.
                throw new \UnexpectedValueException('custom property block');
            }
            if (str_starts_with($prelude, '@')) {
                $i = $this->atRuleBlock($prelude, $end + 1, $layer, $inStyle);

                continue;
            }
            $i = $this->block($end + 1, $layer, true);
        }
    }

    private function statement(string $prelude, string $layer, bool $inStyle): void
    {
        if ($prelude === '') {
            return;
        }
        if (str_starts_with($prelude, '@')) {
            if (preg_match('/^@layer\s+(.+)$/Ds', $prelude, $match) === 1) {
                foreach (explode(',', $match[1]) as $name) {
                    $this->useLayer($this->layerName($layer, trim($name)));
                }

                return;
            }
            if (preg_match('/^@charset\b/i', $prelude) === 1) {
                // Only meaningful as the first bytes of a file; a segment
                // starts with its source comment, so browsers ignore it.
                return;
            }
            throw new \UnexpectedValueException('statement at-rule');
        }
        if (! $inStyle
            || preg_match('/^(-{0,2}(?:[A-Za-z_]|\\\\.)(?:[A-Za-z0-9_-]|\\\\.)*)\s*:/Ds', $prelude, $match) !== 1
        ) {
            throw new \UnexpectedValueException('declaration');
        }
        $this->declare($layer, $match[1]);
    }

    private function atRuleBlock(string $prelude, int $bodyStart, string $layer, bool $inStyle): int
    {
        if (preg_match('/^@([A-Za-z-]+)\s*(.*)$/Ds', $prelude, $match) !== 1) {
            throw new \UnexpectedValueException('at-rule');
        }
        $name = strtolower($match[1]);
        $rest = trim($match[2]);
        if ($name === 'layer') {
            if ($rest === '' || str_contains($rest, ',')) {
                // Anonymous layers are ordered by appearance alone.
                throw new \UnexpectedValueException('anonymous layer');
            }
            $full = $this->layerName($layer, $rest);
            $this->useLayer($full);

            return $this->block($bodyStart, $full, false);
        }
        if (in_array($name, self::CONDITIONAL, true)) {
            return $this->block($bodyStart, $layer, $inStyle);
        }
        $unprefixed = (string) preg_replace('/^-[a-z]+-/', '', $name);
        if (in_array($unprefixed, self::NAMED, true)) {
            $this->signature['global']['@' . $unprefixed . ':' . $this->unescape($rest)] = true;

            return $this->skipBlock($bodyStart);
        }
        if (in_array($unprefixed, self::GLOBAL, true)) {
            $this->signature['global']['@' . $unprefixed] = true;

            return $this->skipBlock($bodyStart);
        }
        throw new \UnexpectedValueException('unknown at-rule');
    }

    private function layerName(string $parent, string $name): string
    {
        if (preg_match('/^[A-Za-z_-][A-Za-z0-9_-]*(?:\.[A-Za-z_-][A-Za-z0-9_-]*)*$/D', $name) !== 1) {
            throw new \UnexpectedValueException('layer name');
        }

        return $parent === '' ? $name : $parent . '.' . $name;
    }

    private function useLayer(string $name): void
    {
        if (! isset($this->declaredLayers[$name])) {
            $this->signature['global']['@layer-order'] = true;
        }
    }

    private function declare(string $layer, string $rawProperty): void
    {
        $tokens = $this->propertyTokens[$rawProperty] ??= $this->tokensFor($rawProperty);
        $this->signature['layers'][$layer] ??= ['self' => [], 'anc' => []];
        $this->signature['layers'][$layer]['self'] += $tokens['self'];
        $this->signature['layers'][$layer]['anc'] += $tokens['anc'];
    }

    /** @return array{self: array<string, true>, anc: array<string, true>} */
    private function tokensFor(string $rawProperty): array
    {
        if (str_starts_with($rawProperty, '--')) {
            return ['self' => ['--' . $this->unescape(substr($rawProperty, 2)) => true], 'anc' => []];
        }
        $property = (string) preg_replace('/^-[a-z]+-/', '', strtolower($this->unescape($rawProperty)));
        if ($property === 'all') {
            return ['self' => ['*all' => true], 'anc' => []];
        }
        $self = [];
        $ancestors = ['*all' => true];
        if (isset($this->groupIndex[$property])) {
            $self['group:' . $this->groupIndex[$property]] = true;
        }
        foreach (self::MUTUAL as $family) {
            if ($property === $family || str_starts_with($property, $family . '-')) {
                $self['family:' . $family] = true;
            }
        }
        if ($property === 'border' || str_starts_with($property, 'border-')) {
            foreach ($this->borderAspects($property) as $aspect) {
                $self['border:' . $aspect] = true;
            }

            return ['self' => $self, 'anc' => $ancestors];
        }
        $self[$property] = true;
        $parts = explode('-', $property);
        for ($i = 1; $i < count($parts); $i++) {
            $ancestors[implode('-', array_slice($parts, 0, $i))] = true;
        }
        if ($property === 'line-height') {
            $ancestors['font'] = true;
        }

        return ['self' => $self, 'anc' => $ancestors];
    }

    /** @return list<string> */
    private function borderAspects(string $property): array
    {
        if (str_contains($property, 'radius')) {
            return ['radius'];
        }
        if (str_starts_with($property, 'border-image')) {
            return ['image'];
        }
        if ($property === 'border-collapse' || $property === 'border-spacing') {
            return [$property];
        }
        foreach (['color', 'width', 'style'] as $aspect) {
            if (str_ends_with($property, '-' . $aspect)) {
                return [$aspect];
            }
        }

        return $property === 'border' ? ['color', 'width', 'style', 'image'] : ['color', 'width', 'style'];
    }

    /** Decode CSS escapes so two spellings of one name compare equal. */
    private function unescape(string $value): string
    {
        if (! str_contains($value, '\\')) {
            return $value;
        }

        return (string) preg_replace_callback(
            '/\\\\(?:([0-9A-Fa-f]{1,6})[ \t\n\r\f]?|(.))/s',
            static function (array $match): string {
                if (($match[1] ?? '') !== '') {
                    $codepoint = hexdec($match[1]);

                    return $codepoint === 0 || $codepoint > 0x10FFFF
                        ? "\u{FFFD}"
                        : (string) mb_chr((int) $codepoint, 'UTF-8');
                }

                return (string) $match[2];
            },
            $value,
        );
    }

    private function skipSpace(int $i): int
    {
        while ($i < $this->length) {
            $character = $this->css[$i];
            if ($character === ' ' || $character === "\n" || $character === "\t" || $character === "\r" || $character === "\f") {
                $i++;

                continue;
            }
            if ($character === '/' && ($this->css[$i + 1] ?? '') === '*') {
                $i = $this->commentEnd($i);

                continue;
            }
            break;
        }

        return $i;
    }

    private function commentEnd(int $i): int
    {
        $end = strpos($this->css, '*/', $i + 2);
        if ($end === false) {
            throw new \UnexpectedValueException('comment');
        }

        return $end + 2;
    }

    /**
     * Read a prelude or declaration up to `;`, `{` or `}` outside strings,
     * comments and parentheses.
     *
     * @return array{0: int, 1: string}
     */
    private function prelude(int $i): array
    {
        $text = '';
        $parentheses = 0;
        while ($i < $this->length) {
            $character = $this->css[$i];
            if ($character === '\\') {
                $text .= substr($this->css, $i, 2);
                $i += 2;

                continue;
            }
            if ($character === '/' && ($this->css[$i + 1] ?? '') === '*') {
                $i = $this->commentEnd($i);
                $text .= ' ';

                continue;
            }
            if ($character === '"' || $character === "'") {
                $end = $this->stringEnd($i);
                $text .= substr($this->css, $i, $end - $i);
                $i = $end;

                continue;
            }
            if ($character === '(' || $character === '[') {
                $parentheses++;
            } elseif ($character === ')' || $character === ']') {
                $parentheses--;
                if ($parentheses < 0) {
                    throw new \UnexpectedValueException('parenthesis');
                }
            } elseif ($parentheses === 0 && ($character === ';' || $character === '{' || $character === '}')) {
                return [$i, trim($text)];
            }
            $text .= $character;
            $i++;
        }

        return [$i, trim($text)];
    }

    private function stringEnd(int $i): int
    {
        $quote = $this->css[$i];
        $i++;
        while ($i < $this->length) {
            $character = $this->css[$i];
            if ($character === '\\') {
                $i += 2;

                continue;
            }
            if ($character === $quote) {
                return $i + 1;
            }
            if ($character === "\n") {
                throw new \UnexpectedValueException('string');
            }
            $i++;
        }
        throw new \UnexpectedValueException('string');
    }

    /** Skip a block whose opening brace was consumed; returns the offset after it. */
    private function skipBlock(int $i): int
    {
        $depth = 1;
        while ($i < $this->length) {
            $character = $this->css[$i];
            if ($character === '\\') {
                $i += 2;

                continue;
            }
            if ($character === '/' && ($this->css[$i + 1] ?? '') === '*') {
                $i = $this->commentEnd($i);

                continue;
            }
            if ($character === '"' || $character === "'") {
                $i = $this->stringEnd($i);

                continue;
            }
            if ($character === '{') {
                $depth++;
            } elseif ($character === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i + 1;
                }
            }
            $i++;
        }
        throw new \UnexpectedValueException('eof');
    }
}
