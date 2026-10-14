<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The Framework reduced the focus ring to one mechanism: an outline whose width,
 * style and colour come from the shared tokens, so a product that re-themes
 * `--sf-focus` changes every ring at once. The rule that holds components to it
 * lives in the Framework sources and never saw this repository, which is how
 * six hand-written rings survived here — drawn in `--sf-primary` at a width
 * typed in by hand. A person looking at a page found them, not a check. This is
 * that check, for the stylesheets Docara writes itself.
 */
final class ShellFocusRingTest extends TestCase
{
    /** The stylesheets this repository authors. Anything projected from the
     * Framework or vendored belongs to its owner and is verified there. */
    private const OWNED_ROOTS = [
        'resources/portable',
        'resources/smart/assets',
        'stubs/portable/smart',
        'docs/site/smart',
    ];

    private const EXCLUDED = [
        'resources/portable/vendor',
    ];

    #[Test]
    public function every_focus_ring_this_repository_draws_reads_the_shared_tokens(): void
    {
        $offenders = [];
        foreach ($this->ownedStylesheets() as $relativePath => $css) {
            foreach ($this->focusRules($css) as $selector => $body) {
                foreach ($this->outlineDeclarations($body) as $declaration) {
                    if ($this->readsSharedTokens($declaration)) {
                        continue;
                    }
                    $offenders[] = sprintf('%s: %s { %s }', $relativePath, $selector, $declaration);
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            'a focus ring must read --sf-focus--width, --sf-focus--style and --sf-focus--color, '
            . 'so re-theming the focus role reaches it',
        );
    }

    #[Test]
    public function no_focus_rule_paints_a_ring_with_a_shadow(): void
    {
        $offenders = [];
        foreach ($this->ownedStylesheets() as $relativePath => $css) {
            foreach ($this->focusRules($css) as $selector => $body) {
                if (preg_match('/(?:^|[;{\s])box-shadow\s*:\s*(?!none)/i', $body) !== 1) {
                    continue;
                }
                $offenders[] = sprintf('%s: %s', $relativePath, $selector);
            }
        }

        self::assertSame(
            [],
            $offenders,
            'a shadow shares its property with elevation, covers the neighbour '
            . 'and is dropped entirely in forced colours',
        );
    }

    /**
     * The one rule that has to stay findable: a ring may not be reintroduced by
     * naming a role that is not the focus role.
     */
    #[Test]
    public function no_focus_rule_names_a_colour_role_of_its_own(): void
    {
        $offenders = [];
        foreach ($this->ownedStylesheets() as $relativePath => $css) {
            foreach ($this->focusRules($css) as $selector => $body) {
                foreach ($this->outlineDeclarations($body) as $declaration) {
                    if (preg_match('/var\(\s*--sf-(?!focus)[a-z0-9-]+/i', $declaration) !== 1) {
                        continue;
                    }
                    $offenders[] = sprintf('%s: %s { %s }', $relativePath, $selector, $declaration);
                }
            }
        }

        self::assertSame([], $offenders, 'the ring is drawn in the focus role, not in another one');
    }

    /**
     * Where the ring sits is the element's own geometry, but the gap outside it
     * is the system's: an outward ring reads --sf-focus--offset. A ring drawn
     * inwards, flush against its neighbours, insets by exactly its own width.
     */
    #[Test]
    public function an_outward_ring_keeps_the_shared_gap(): void
    {
        $offenders = [];
        foreach ($this->ownedStylesheets() as $relativePath => $css) {
            foreach ($this->focusRules($css) as $selector => $body) {
                if (preg_match('/(?:^|;)\s*outline-offset\s*:\s*([^;]+)/i', $body, $offset) !== 1) {
                    continue;
                }
                $value = preg_replace('/\s+/', ' ', trim($offset[1])) ?? '';
                if (in_array($value, ['var(--sf-focus--offset)', 'calc(var(--sf-focus--width) * -1)'], true)) {
                    continue;
                }
                $offenders[] = sprintf('%s: %s { outline-offset: %s }', $relativePath, $selector, $value);
            }
        }

        self::assertSame([], $offenders, 'an outward ring keeps --sf-focus--offset; an inward one insets by its width');
    }

    /** @return array<string, string> */
    private function ownedStylesheets(): array
    {
        $root = dirname(__DIR__, 2);
        $found = [];
        foreach (self::OWNED_ROOTS as $owned) {
            $directory = $root . '/' . $owned;
            if (! is_dir($directory)) {
                continue;
            }
            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            ) as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'css' || $file->isLink()) {
                    continue;
                }
                $relativePath = substr($file->getPathname(), strlen($root) + 1);
                foreach (self::EXCLUDED as $excluded) {
                    if (str_starts_with($relativePath, $excluded)) {
                        continue 2;
                    }
                }
                if (str_contains($relativePath, '.min.')) {
                    continue;
                }
                $contents = file_get_contents($file->getPathname());
                if (is_string($contents)) {
                    $found[$relativePath] = $contents;
                }
            }
        }
        self::assertNotSame([], $found, 'the stylesheets this repository authors are still where the rule looks');

        return $found;
    }

    /**
     * The shell stylesheets are flat CSS, so one pass over `selector { body }` is
     * enough. Only rules a keyboard raises are judged; `:focus` without
     * `-visible` is left to the rule that says a pointer press draws nothing.
     *
     * @return array<string, string>
     */
    private function focusRules(string $css): array
    {
        $rules = [];
        if (preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER) === false) {
            return $rules;
        }
        foreach ($matches as $match) {
            $selector = trim(preg_replace('/\s+/', ' ', $match[1]) ?? '');
            if (! str_contains($selector, ':focus-visible')) {
                continue;
            }
            $rules[$selector] = $match[2];
        }

        return $rules;
    }

    /**
     * A ring is the `outline` shorthand or its parts, except where it is cleared.
     * `outline-offset` is the element's own geometry — inwards where it sits
     * flush against its neighbours — so it is not judged here.
     *
     * @return list<string>
     */
    private function outlineDeclarations(string $body): array
    {
        $declarations = [];
        foreach (explode(';', $body) as $declaration) {
            $declaration = trim($declaration);
            if (preg_match('/^outline(-color|-style|-width)?\s*:/i', $declaration) !== 1) {
                continue;
            }
            if (preg_match('/:\s*(none|0)\s*$/i', $declaration) === 1) {
                continue;
            }
            $declarations[] = $declaration;
        }

        return $declarations;
    }

    private function readsSharedTokens(string $declaration): bool
    {
        if (preg_match('/^outline-(color|style|width)\s*:/i', $declaration) === 1) {
            return str_contains($declaration, '--sf-focus--');
        }

        return str_contains($declaration, 'var(--sf-focus--width)')
            && str_contains($declaration, 'var(--sf-focus--style)')
            && str_contains($declaration, '--sf-focus--color');
    }
}
