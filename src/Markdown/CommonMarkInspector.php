<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use Closure;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Parser\MarkdownParser;

final class CommonMarkInspector
{
    public const MAX_SOURCE_DIRECTIVE_MARKERS = 64;

    private MarkdownParser $parser;

    private DirectiveOpeningMatcher $directiveMatcher;

    /** @param null|Closure(string): void $onDirectiveIteration */
    public function __construct(
        private readonly ?Closure $onDirectiveIteration = null,
        ?DirectiveOpeningMatcher $directiveMatcher = null,
    ) {
        $this->directiveMatcher = $directiveMatcher ?? DirectiveOpeningMatcher::bundled();
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @return array{
     *     code_lines: array<int, true>,
     *     literal_code_lines: array<int, true>,
     *     nested_lines: array<int, true>,
     *     top_level_thematic_break_lines: array<int, true>,
     *     references: list<array{label: string, destination: string, title: string}>,
     *     directives: list<array{name: string, start_line: int, end_line: int, body: string, closed: bool, fence_length: int}>
     * }
     */
    public function inspect(string $markdown): array
    {
        $document = $this->parser->parse($markdown);

        return $this->inspectDocument($document);
    }

    /**
     * Detect a directive-shaped opener in a block body while keeping fenced,
     * indented, and raw-HTML examples opaque. A directive body is never a
     * second directive container: component-like examples belong in a fenced
     * code block.
     */
    public function containsDirectiveLikeOpening(string $markdown, ?string $family = null): bool
    {
        $inspection = $this->inspect($markdown);
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            return false;
        }

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            if (isset($inspection['code_lines'][$lineNumber])) {
                continue;
            }
            if ($this->directiveMatcher->matches($line, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse directive openings as real CommonMark top-level blocks, then pair
     * them with legacy-compatible raw closing fences. The close scanner uses a
     * normal CommonMark parse to ignore delimiters inside fenced or indented
     * code. Authors can use a longer outer colon fence when prose, raw HTML,
     * or another inline construct needs to contain a standalone shorter fence.
     *
     * @return array{
     *     code_lines: array<int, true>,
     *     literal_code_lines: array<int, true>,
     *     nested_lines: array<int, true>,
     *     top_level_thematic_break_lines: array<int, true>,
     *     references: list<array{label: string, destination: string, title: string}>,
     *     directives: list<array{name: string, start_line: int, end_line: int, body: string, closed: bool, fence_length: int}>
     * }
     */
    public function inspectDirectives(string $markdown, string $family): array
    {
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            return $this->inspect($markdown);
        }
        $this->assertCombinedSourceDirectiveMarkerLimit($lines);

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addBlockStartParser(
            new DirectiveBlockStartParser($family, $this->directiveMatcher),
            200,
        );

        $inspection = $this->inspect($markdown);
        $workingLines = $lines;
        $directives = [];
        $parser = new MarkdownParser($environment);

        while (true) {
            $workingMarkdown = implode("\n", $workingLines);
            $workingInspection = $this->inspect($workingMarkdown);
            $markers = $this->inspectDocument($parser->parse($workingMarkdown))['directives'];
            if ($markers === []) {
                break;
            }
            if ($this->onDirectiveIteration !== null) {
                ($this->onDirectiveIteration)($family);
            }

            $marker = $markers[0];
            $startLine = $marker['start_line'];

            $closingLine = $this->findClosingFence(
                $lines,
                $startLine,
                $marker['fence_length'],
            );
            $endLine = $closingLine ?? count($lines);
            $bodyLines = array_slice(
                $lines,
                $startLine,
                max(0, $endLine - $startLine - ($closingLine === null ? 0 : 1)),
            );
            while ($closingLine !== null && end($bodyLines) === '') {
                array_pop($bodyLines);
            }
            $isTopLevel = ! isset($workingInspection['code_lines'][$startLine])
                && ! isset($workingInspection['nested_lines'][$startLine]);
            if ($isTopLevel && $this->belongsToFamily($marker['name'], $family)) {
                $directives[] = [
                    'name' => $marker['name'],
                    'start_line' => $startLine,
                    'end_line' => $endLine,
                    'body' => implode("\n", $bodyLines),
                    'closed' => $closingLine !== null,
                    'fence_length' => $marker['fence_length'],
                ];
            }

            for ($line = $startLine; $line <= $endLine; $line++) {
                $workingLines[$line - 1] = '';
            }

            if ($closingLine === null) {
                break;
            }
        }
        $inspection['directives'] = $directives;

        return $inspection;
    }

    /** @param list<string> $lines */
    private function assertCombinedSourceDirectiveMarkerLimit(array $lines): void
    {
        $sourceMarkers = 0;
        foreach ($lines as $line) {
            $opening = $this->directiveMatcher->match($line);
            if ($opening === null) {
                continue;
            }

            $sourceMarkers++;
            if ($sourceMarkers > self::MAX_SOURCE_DIRECTIVE_MARKERS) {
                throw new DirectiveLimitExceeded(
                    $opening['family'],
                    'A Markdown page may contain at most '
                    . self::MAX_SOURCE_DIRECTIVE_MARKERS
                    . ' combined Docara and Smart directive-like opening markers.',
                );
            }
        }
    }

    /** @return array<string, mixed> */
    private function inspectDocument(Document $document): array
    {
        $codeLines = [];
        $literalCodeLines = [];
        $nestedLines = [];
        $topLevelThematicBreakLines = [];
        $directives = [];
        $walker = $document->walker();
        while (($event = $walker->next()) !== null) {
            $node = $event->getNode();
            if (! $event->isEntering()) {
                continue;
            }

            if ($node instanceof DirectiveBlock) {
                $start = $node->getStartLine();
                $end = $node->getEndLine();
                if ($start !== null && $end !== null) {
                    $directives[] = [
                        'name' => $node->name(),
                        'start_line' => $start,
                        'end_line' => $end,
                        'body' => $node->body(),
                        'closed' => $node->isClosed(),
                        'fence_length' => $node->fenceLength(),
                    ];
                }
            }

            if ($node instanceof ThematicBreak && $node->parent() instanceof Document) {
                $start = $node->getStartLine();
                if ($start !== null) {
                    $topLevelThematicBreakLines[$start] = true;
                }
            }

            if ($node instanceof AbstractBlock) {
                $start = $node->getStartLine();
                $end = $node->getEndLine();
                if ($start !== null && $end !== null) {
                    if ($node instanceof FencedCode
                        || $node instanceof IndentedCode
                    ) {
                        $this->markLines($codeLines, $start, $end);
                        $this->markLines($literalCodeLines, $start, $end);
                    }
                    if ($node instanceof HtmlBlock) {
                        $this->markLines($codeLines, $start, $end);
                    }
                    if ($this->hasNestedContainer($node)) {
                        $this->markLines($nestedLines, $start, $end);
                    }
                }
            }

        }

        $references = [];
        foreach ($document->getReferenceMap() as $reference) {
            $references[] = [
                'label' => $reference->getLabel(),
                'destination' => $reference->getDestination(),
                'title' => $reference->getTitle(),
            ];
        }

        usort($directives, static fn (array $left, array $right): int => $left['start_line'] <=> $right['start_line']);

        return [
            'code_lines' => $codeLines,
            'literal_code_lines' => $literalCodeLines,
            'nested_lines' => $nestedLines,
            'top_level_thematic_break_lines' => $topLevelThematicBreakLines,
            'references' => $references,
            'directives' => $directives,
        ];
    }

    /** @param array<int, true> $lines */
    private function markLines(array &$lines, int $start, int $end): void
    {
        for ($line = $start; $line <= $end; $line++) {
            $lines[$line] = true;
        }
    }

    private function hasNestedContainer(AbstractBlock $node): bool
    {
        $parent = $node->parent();
        while ($parent !== null) {
            if ($parent instanceof ListItem || $parent instanceof BlockQuote) {
                return true;
            }
            $parent = $parent->parent();
        }

        return false;
    }

    private function belongsToFamily(string $name, string $family): bool
    {
        return $this->directiveMatcher->belongsToFamily($name, $family);
    }

    public function isDirectiveOpeningLine(string $line, ?string $family = null): bool
    {
        return $this->directiveMatcher->matches($line, $family);
    }

    public function isDirectivePlacementLine(string $line, ?string $family = null): bool
    {
        return $this->directiveMatcher->matchesPlacement($line, $family);
    }

    /** @param list<string> $lines */
    private function findClosingFence(array $lines, int $startLine, int $minimumLength): ?int
    {
        $tail = implode("\n", array_slice($lines, $startLine));
        $opaque = $this->inspect($tail)['literal_code_lines'];

        for ($index = $startLine, $count = count($lines); $index < $count; $index++) {
            $relativeLine = $index - $startLine + 1;
            if (isset($opaque[$relativeLine])) {
                continue;
            }
            if (preg_match('/^ {0,3}(:{3,})[ \t]*$/u', $lines[$index], $match) !== 1
                || strlen($match[1]) < $minimumLength
            ) {
                continue;
            }

            return $index + 1;
        }

        return null;
    }
}
