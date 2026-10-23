<?php

declare(strict_types=1);

namespace Simai\Docara\Authoring;

/**
 * Guesses the page profile of a page that has none from front matter, a path
 * rule or default_profile. The guess is deterministic: the same path and
 * Markdown always give the same profile and reason. It drives advice only and
 * never fails a build.
 *
 * Order of the rules, first match wins:
 *   1. index.md in any directory            → landing     (index_page)
 *   2. a path segment names the page type   → that type   (path:<segment>)
 *   3. three or more numbered steps          → how_to      (ordered_steps)
 *   4. tables, lists and definitions make up
 *      at least 60% of the text lines        → reference   (structured_content)
 *   5. anything else                         → article     (fallback)
 */
final readonly class ProfileInference
{
    /** Path segments, without extension, that name a page type. */
    public const PATH_HINTS = [
        'tutorial' => 'tutorial', 'tutorials' => 'tutorial', 'getting-started' => 'tutorial',
        'quickstart' => 'tutorial', 'quick-start' => 'tutorial', 'learn' => 'tutorial',
        'how-to' => 'how_to', 'howto' => 'how_to', 'how_to' => 'how_to', 'how-tos' => 'how_to',
        'reference' => 'reference', 'api' => 'reference', 'glossary' => 'reference',
        'explanation' => 'explanation', 'explanations' => 'explanation', 'concepts' => 'explanation',
        'architecture' => 'explanation',
    ];

    public const MIN_ORDERED_STEPS = 3;

    public const MIN_STRUCTURED_SHARE = 0.6;

    public const MIN_STRUCTURED_LINES = 3;

    /** @return array{profile:string,reason:string} */
    public function infer(string $relativePath, string $markdown): array
    {
        $path = strtolower(str_replace('\\', '/', $relativePath));
        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
        $file = (string) end($segments);
        if ($file === 'index.md' || $segments === []) {
            return ['profile' => 'landing', 'reason' => 'index_page'];
        }
        $segments[count($segments) - 1] = (string) preg_replace('/\.md$/', '', $file);
        foreach ($segments as $segment) {
            if (isset(self::PATH_HINTS[$segment])) {
                return ['profile' => self::PATH_HINTS[$segment], 'reason' => 'path:' . $segment];
            }
        }

        $lines = $this->contentLines($markdown);
        $ordered = count(array_filter($lines, static fn (string $line): bool => preg_match('/^\d+[.)]\s+\S/', $line) === 1));
        if ($ordered >= self::MIN_ORDERED_STEPS) {
            return ['profile' => 'how_to', 'reason' => 'ordered_steps'];
        }
        $structured = 0;
        foreach ($lines as $index => $line) {
            if (preg_match('/^(?:\||[-*+]\s+\S|\d+[.)]\s+\S|:\s+\S)/', $line) === 1
                || (isset($lines[$index + 1]) && preg_match('/^:\s+\S/', $lines[$index + 1]) === 1)
            ) {
                $structured++;
            }
        }
        if ($lines !== [] && $structured >= self::MIN_STRUCTURED_LINES && $structured / count($lines) >= self::MIN_STRUCTURED_SHARE) {
            return ['profile' => 'reference', 'reason' => 'structured_content'];
        }

        return ['profile' => 'article', 'reason' => 'fallback'];
    }

    /**
     * Non-empty text lines outside fenced code, headings and directive
     * fences, trimmed of indentation. Paragraph lines count once each.
     *
     * @return list<string>
     */
    private function contentLines(string $markdown): array
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $markdown = preg_replace('/^```[^\n]*\n.*?^```\s*$/ms', '', $markdown) ?? $markdown;
        $lines = [];
        foreach (explode("\n", $markdown) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ':::') || preg_match('/^\|?\s*:?-{3,}/', $line) === 1) {
                continue;
            }
            $lines[] = $line;
        }

        return $lines;
    }
}
