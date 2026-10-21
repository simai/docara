<?php

declare(strict_types=1);

namespace Tests\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Collects the diagnostic codes a Docara user can meet, straight from the
 * source, so the error reference cannot silently fall behind the code.
 *
 * A code is an UPPER_SNAKE string literal in src/ (thrown, returned in a
 * diagnostic array or passed through a variable), the CODE of a
 * "CODE:message" or "[CODE] message" literal, or a code assembled from a
 * literal prefix and suffix ('MARKDOWN_' . $block . '_LINK_REQUIRED'), which
 * is kept as a pattern. Static verifier markers are the '@marker' literals of
 * scripts/verify-static-build.php.
 */
final class DiagnosticCodeInventory
{
    public const CODE = '[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)+';

    /**
     * UPPER_SNAKE literals in src/ that are not diagnostic codes.
     *
     * @var array<string,string>
     */
    public const NOT_CODES = [
        'DOCARA_NODE_BINARY' => 'environment variable',
        'DOCARA_SIMAI_UI_ROOT' => 'environment variable',
        'SIMAI_UI_ROOT' => 'environment variable',
        'SF_ICON_TYPE' => 'Framework runtime global',
    ];

    /**
     * Literals that are only the first half of a code: the file appends a
     * suffix at runtime ($codePrefix . '_UNSAFE'). Each prefix yields one code
     * per such suffix of the same file.
     *
     * @var array<string,string>
     */
    public const PREFIXES = [
        'DECLARATIVE_PUBLISHER_ASSET' => 'src/PortableSite/PortablePublisherAssetPublisher.php',
        'DECLARATIVE_SMART_ASSET' => 'src/PortableSite/PortablePublisherAssetPublisher.php',
        'MARKDOWN_HERO_BACKGROUND' => 'src/PortableSite/PortableMarkdownRenderer.php',
        'MARKDOWN_HERO_SIDE_IMAGE' => 'src/PortableSite/PortableMarkdownRenderer.php',
        'MARKDOWN_LOCAL_IMAGE' => 'src/PortableSite/PortableMarkdownRenderer.php',
        'MARKDOWN_SURFACE_BACKGROUND' => 'src/PortableSite/PortableMarkdownRenderer.php',
    ];

    /** The error reference pages: a code named there has a cause and a fix. */
    public const REFERENCE_PAGES = 'docs/site/content/ru/reference/errors';

    /** Where the list of codes without a reference entry is kept. */
    public const COVERAGE_FILE = 'docs/diagnostic-code-coverage.json';

    /** @var array<string,list<string>>|null */
    private ?array $codes = null;

    /** @var list<array{prefix:string,suffix:string,example:string,source:string}>|null */
    private ?array $patterns = null;

    public function __construct(private readonly string $root) {}

    /** @return array<string,list<string>> code => source files (relative), sorted */
    public function codes(): array
    {
        $this->scan();

        return $this->codes;
    }

    /**
     * Codes with a runtime middle part: 'MARKDOWN_' . $block . '_LINK_REQUIRED'.
     *
     * @return list<array{prefix:string,suffix:string,example:string,source:string}>
     */
    public function patterns(): array
    {
        $this->scan();

        return $this->patterns;
    }

    /** @return list<string> verify-static markers such as @missing-fragment */
    public function verifierMarkers(): array
    {
        $source = (string) file_get_contents($this->root . '/scripts/verify-static-build.php');
        preg_match_all("/'(@[a-z][a-z0-9-]+):?'/", $source, $matches);
        $markers = array_values(array_diff(array_unique($matches[1]), ['@build']));
        sort($markers, SORT_STRING);

        return $markers;
    }

    public function knows(string $code): bool
    {
        if (str_starts_with($code, '@')) {
            return in_array($code, $this->verifierMarkers(), true);
        }
        if (isset($this->codes()[$code])) {
            return true;
        }
        foreach ($this->patterns() as $pattern) {
            if (! str_ends_with($code, $pattern['suffix']) || strlen($code) <= strlen($pattern['prefix'] . $pattern['suffix'])) {
                continue;
            }
            $middle = substr($code, strlen($pattern['prefix']), -strlen($pattern['suffix']));
            if (str_starts_with($code, $pattern['prefix']) && preg_match('/^[A-Z0-9]+(?:_[A-Z0-9]+)*$/', $middle) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Codes named in backticks in Markdown files under $directory.
     *
     * @return array<string,list<string>> code => files (relative to the root)
     */
    public function mentionedIn(string $directory): array
    {
        $mentions = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root . '/' . $directory, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($this->root) + 1);
            preg_match_all('/`(' . self::CODE . '|@[a-z][a-z0-9-]+)`/', (string) file_get_contents($file->getPathname()), $matches);
            foreach (array_unique($matches[1]) as $code) {
                $mentions[$code][] = $relative;
            }
        }
        ksort($mentions, SORT_STRING);
        foreach ($mentions as &$files) {
            sort($files, SORT_STRING);
        }

        return $mentions;
    }

    /**
     * Codes and markers named on the error reference pages.
     *
     * @return list<string>
     */
    public function documented(): array
    {
        $documented = array_keys($this->mentionedIn(self::REFERENCE_PAGES));
        $page = $this->root . '/' . self::REFERENCE_PAGES . '.md';
        preg_match_all('/`(' . self::CODE . '|@[a-z][a-z0-9-]+)`/', (string) file_get_contents($page), $matches);
        $documented = array_values(array_unique(array_merge($documented, $matches[1])));
        sort($documented, SORT_STRING);

        return $documented;
    }

    /**
     * What the error reference covers and what it does not yet, grouped by
     * the first word of the code. Written to COVERAGE_FILE so the gap is
     * visible in review; a new code shows up there as undocumented.
     *
     * @return array<string,mixed>
     */
    public function coverage(): array
    {
        $documented = array_fill_keys($this->documented(), true);
        $undocumented = [];
        $count = 0;
        foreach (array_keys($this->codes()) as $code) {
            if (isset($documented[$code])) {
                $count++;

                continue;
            }
            $undocumented[explode('_', $code, 2)[0]][] = $code;
        }
        ksort($undocumented, SORT_STRING);
        $markers = $this->verifierMarkers();
        $undocumentedMarkers = array_values(array_filter($markers, static fn (string $marker): bool => ! isset($documented[$marker])));

        return [
            'schema' => 'docara.diagnostic_code_coverage.v1',
            'note' => 'Generated by scripts/capture-diagnostic-code-coverage.php from src/ and docs/site/content/ru/reference/errors*. Codes listed under undocumented have no cause-and-fix entry yet.',
            'codes' => [
                'in_source' => count($this->codes()),
                'documented' => $count,
                'undocumented' => count($this->codes()) - $count,
            ],
            'assembled_patterns' => array_map(static fn (array $pattern): string => $pattern['example'], $this->patterns()),
            'verify_static_markers' => [
                'in_source' => count($markers),
                'documented' => count($markers) - count($undocumentedMarkers),
                'undocumented' => $undocumentedMarkers,
            ],
            'undocumented' => $undocumented,
        ];
    }

    private function scan(): void
    {
        if ($this->codes !== null) {
            return;
        }
        $codes = [];
        $patterns = [];
        $files = [$this->root . '/scripts/verify-static-build.php'];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root . '/src', RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        foreach ($files as $path) {
            $relative = substr($path, strlen($this->root) + 1);
            $isSource = str_starts_with($relative, 'src/');
            $tokens = array_values(array_filter(
                token_get_all((string) file_get_contents($path)),
                static fn (mixed $token): bool => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
            ));
            foreach ($tokens as $index => $token) {
                if (is_array($token) && $token[0] === T_ENCAPSED_AND_WHITESPACE && ($tokens[$index - 1] ?? null) === '"') {
                    if (preg_match('/^\[(' . self::CODE . ')\]/', $token[1], $match) === 1
                        || preg_match('/^(' . self::CODE . '):/', $token[1], $match) === 1
                    ) {
                        $codes[$match[1]][$relative] = true;
                    }

                    continue;
                }
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }
                $value = substr($token[1], 1, -1);
                if (preg_match('/^\[(' . self::CODE . ')\]/', $value, $match) === 1
                    || preg_match('/^(' . self::CODE . '):/', $value, $match) === 1
                ) {
                    $codes[$match[1]][$relative] = true;

                    continue;
                }
                if (! $isSource) {
                    continue;
                }
                if (preg_match('/^' . self::CODE . '$/', $value) === 1) {
                    if (! isset(self::NOT_CODES[$value]) && ! isset(self::PREFIXES[$value]) && ! $this->isEnvironmentName($tokens, $index)) {
                        $codes[$value][$relative] = true;
                    }

                    continue;
                }
                if (preg_match('/^[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)*_$/', $value) === 1 && ($tokens[$index + 1] ?? null) === '.') {
                    $suffix = $this->suffixAfterOperand($tokens, $index + 2);
                    if ($suffix !== null) {
                        $patterns[] = ['prefix' => $value, 'suffix' => $suffix, 'example' => $value . '{…}' . $suffix, 'source' => $relative];
                    }

                    continue;
                }
                if (preg_match('/^_[A-Z0-9]+(?:_[A-Z0-9]+)*$/', $value) === 1
                    && ($tokens[$index - 1] ?? null) === '.'
                    && is_array($tokens[$index - 2] ?? null)
                    && $tokens[$index - 2][0] === T_VARIABLE
                    && ($tokens[$index - 3] ?? null) !== '.'
                ) {
                    $patterns[] = ['prefix' => '', 'suffix' => $value, 'example' => '{CODE}' . $value, 'source' => $relative];
                }
            }
        }

        $assembled = [];
        foreach ($patterns as $index => $pattern) {
            if ($pattern['prefix'] !== '') {
                continue;
            }
            foreach (self::PREFIXES as $prefix => $file) {
                if ($file === $pattern['source']) {
                    $codes[$prefix . $pattern['suffix']][$file] = true;
                }
            }
            $assembled[] = $index;
        }
        foreach ($assembled as $index) {
            unset($patterns[$index]);
        }

        ksort($codes, SORT_STRING);
        $this->codes = array_map(static function (array $files): array {
            $files = array_keys($files);
            sort($files, SORT_STRING);

            return $files;
        }, $codes);

        $unique = [];
        foreach ($patterns as $pattern) {
            $unique[$pattern['example'] . '@' . $pattern['source']] = $pattern;
        }
        ksort($unique, SORT_STRING);
        $this->patterns = array_values($unique);
    }

    /** @param list<mixed> $tokens */
    private function isEnvironmentName(array $tokens, int $index): bool
    {
        $previous = $tokens[$index - 1] ?? null;
        $name = $tokens[$index - 2] ?? null;

        return $previous === '(' && is_array($name) && in_array(strtolower($name[1]), ['getenv', 'putenv'], true);
    }

    /**
     * Skips one concatenation operand from $index and returns the literal
     * suffix that follows it ('_LINK_REQUIRED'), if any.
     *
     * @param  list<mixed>  $tokens
     */
    private function suffixAfterOperand(array $tokens, int $index): ?string
    {
        $depth = 0;
        for ($cursor = $index, $count = count($tokens); $cursor < $count; $cursor++) {
            $token = $tokens[$cursor];
            if (in_array($token, ['(', '['], true)) {
                $depth++;
            } elseif (in_array($token, [')', ']'], true)) {
                if ($depth === 0) {
                    return null;
                }
                $depth--;
            } elseif ($depth === 0 && in_array($token, [',', ';'], true)) {
                return null;
            } elseif ($depth === 0 && $token === '.') {
                $next = $tokens[$cursor + 1] ?? null;
                if (is_array($next) && $next[0] === T_CONSTANT_ENCAPSED_STRING) {
                    $suffix = substr($next[1], 1, -1);

                    return preg_match('/^_[A-Z0-9]+(?:_[A-Z0-9]+)*$/', $suffix) === 1 ? $suffix : null;
                }

                return null;
            }
        }

        return null;
    }
}
