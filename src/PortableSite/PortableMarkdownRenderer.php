<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;
use Simai\Docara\Markdown\CommonMarkInspector;
use Simai\Docara\Markdown\DirectiveBlockStartParser;
use Simai\Docara\Markdown\DirectiveLimitExceeded;
use Simai\Docara\Portable\PortableConfigurationException;

final class PortableMarkdownRenderer
{
    private MarkdownConverter $converter;

    private CommonMarkInspector $inspector;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new SmartPunctExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new TableExtension);

        $this->converter = new MarkdownConverter($environment);
        $this->inspector = new CommonMarkInspector;
    }

    public function render(string $markdown): string
    {
        if (preg_match('//u', $markdown) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_BLOCK_INPUT_INVALID',
                'Portable Markdown must be valid UTF-8.',
            );
        }

        $inspection = $this->inspectDirectives($markdown);
        $this->assertDirectivePlacement($markdown, $inspection);
        [$markdown, $blocks, $referenceMarkdown] = $this->extractBlocks($markdown, $inspection['directives']);
        $referenceDefinitions = $this->renderReferenceDefinitions(
            $this->inspector->inspect($referenceMarkdown)['references'],
        );
        if ($referenceDefinitions !== '') {
            $markdown = $referenceDefinitions . "\n\n" . $markdown;
        }
        $html = (string) $this->converter->convert($markdown);

        foreach ($blocks as $block) {
            $blockMarkdown = $block['markdown'];
            if ($referenceDefinitions !== '') {
                $blockMarkdown = $referenceDefinitions . "\n\n" . $blockMarkdown;
            }
            $content = $this->converter->convert($blockMarkdown);
            $rendered = match ($block['type']) {
                'card' => '<section class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1">'
                    . (string) $content . '</section>',
                'steps' => $this->renderSteps($content),
            };
            $wrapper = '<p>' . $block['placeholder'] . '</p>';
            if (substr_count($html, $wrapper) !== 1) {
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_PLACEHOLDER_CARDINALITY_INVALID',
                    "Markdown block placeholder [{$block['placeholder']}] is ambiguous after rendering.",
                );
            }
            $html = str_replace($wrapper, $rendered, $html);
        }

        return $this->decorateNativeMarkdown($html);
    }

    /**
     * Extracts Docara content blocks before CommonMark runs. Smart components
     * remain the responsibility of FrameworkComponentRuntime; these blocks
     * are deliberately semantic Markdown plus Simai Framework utilities.
     *
     * @return array{
     *     0: string,
     *     1: list<array{type: string, markdown: string, placeholder: string}>,
     *     2: string
     * }
     */
    private function extractBlocks(string $markdown, array $directives): array
    {
        $newline = str_contains($markdown, "\r\n") ? "\r\n" : "\n";
        $trailingNewline = str_ends_with($markdown, "\n");
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            throw new PortableConfigurationException(
                'MARKDOWN_BLOCK_INPUT_INVALID',
                'Portable Markdown could not be split into lines.',
            );
        }
        if ($trailingNewline && end($lines) === '') {
            array_pop($lines);
        }

        $output = [];
        $referenceOutput = [];
        $blocks = [];

        $byStartLine = [];
        foreach ($directives as $directive) {
            $byStartLine[$directive['start_line']] = $directive;
        }

        for ($index = 0, $count = count($lines); $index < $count; $index++) {
            $lineNumber = $index + 1;
            $line = $lines[$index];
            if (! isset($byStartLine[$lineNumber])) {
                $output[] = $line;
                $referenceOutput[] = $line;

                continue;
            }

            $directive = $byStartLine[$lineNumber];
            $type = $directive['name'];
            $bodyMarkdown = $directive['body'];
            $startLine = $directive['start_line'];
            if ($directive['closed'] !== true) {
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_UNCLOSED',
                    "Markdown block [$type] at line [$startLine] is not closed.",
                );
            }
            if (trim($bodyMarkdown) === '') {
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_EMPTY',
                    "Markdown block [$type] at line [$startLine] is empty.",
                );
            }
            $bodyInspection = $this->inspectDirectives($bodyMarkdown);
            $frameworkBodyInspection = $this->inspectFrameworkDirectives($bodyMarkdown);
            if ($bodyInspection['directives'] !== []
                || $frameworkBodyInspection['directives'] !== []
                || $this->inspector->containsDirectiveLikeOpening($bodyMarkdown)
            ) {
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_NESTING_UNSUPPORTED',
                    "Markdown block [$type] at line [$startLine] cannot contain another Docara or Smart block.",
                );
            }
            $this->assertDirectivePlacement($bodyMarkdown, $bodyInspection);

            $counter = 0;
            do {
                $placeholder = 'DOCARA_MARKDOWN_BLOCK_'
                    . strtoupper(substr(hash(
                        'sha256',
                        $type . "\0" . $startLine . "\0" . $bodyMarkdown . "\0" . $counter,
                    ), 0, 24));
                $counter++;
            } while (str_contains($markdown, $placeholder)
                || in_array($placeholder, array_column($blocks, 'placeholder'), true));
            $blocks[] = [
                'type' => $type,
                'markdown' => $bodyMarkdown,
                'placeholder' => $placeholder,
            ];
            $output[] = '';
            $output[] = $placeholder;
            $output[] = '';
            $referenceOutput[] = '';
            $bodyLines = preg_split('/\r\n|\n|\r/u', $bodyMarkdown);
            if (! is_array($bodyLines)) {
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_INPUT_INVALID',
                    'Portable Markdown block body could not be split into lines.',
                );
            }
            array_push($referenceOutput, ...$bodyLines);
            $referenceOutput[] = '';
            $index = $directive['end_line'] - 1;
        }

        $result = implode($newline, $output);
        $referenceResult = implode($newline, $referenceOutput);
        if ($trailingNewline) {
            $result .= $newline;
            $referenceResult .= $newline;
        }

        return [$result, $blocks, $referenceResult];
    }

    /** @param array<string, mixed> $inspection */
    private function assertDirectivePlacement(string $markdown, array $inspection): void
    {
        $lines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($lines)) {
            throw new PortableConfigurationException(
                'MARKDOWN_BLOCK_INPUT_INVALID',
                'Portable Markdown could not be split into lines.',
            );
        }
        $recognized = array_fill_keys(array_column($inspection['directives'], 'start_line'), true);
        $ownedBodyLines = [];
        foreach ($inspection['directives'] as $directive) {
            for ($line = $directive['start_line'] + 1; $line < $directive['end_line']; $line++) {
                $ownedBodyLines[$line] = true;
            }
        }
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            if (preg_match('/^( {0,3}):{3,}(?:card|steps)[ \t]*$/u', $line, $match) !== 1
                || isset($recognized[$lineNumber])
                || isset($ownedBodyLines[$lineNumber])
                || isset($inspection['code_lines'][$lineNumber])
            ) {
                continue;
            }
            if (isset($inspection['nested_lines'][$lineNumber])) {
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_INDENTATION_UNSUPPORTED',
                    'Docara Markdown blocks must start at the top level without indentation.',
                );
            }
        }
    }

    /** @param list<array{label: string, destination: string, title: string}> $references */
    private function renderReferenceDefinitions(array $references): string
    {
        $definitions = [];
        foreach ($references as $reference) {
            $label = $reference['label'];
            $destination = str_replace(['\\', '<', '>'], ['\\\\', '\\<', '\\>'], $reference['destination']);
            $definition = '[' . $label . ']: <' . $destination . '>';
            $title = trim(preg_replace('/\s+/u', ' ', $reference['title']) ?? $reference['title']);
            if ($title !== '') {
                $definition .= ' "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $title) . '"';
            }
            $definitions[] = $definition;
        }

        return implode("\n", $definitions);
    }

    private function renderSteps(RenderedContentInterface $rendered): string
    {
        $root = $rendered->getDocument()->firstChild();
        if (! $root instanceof ListBlock
            || $root->getListData()->type !== ListBlock::TYPE_ORDERED
            || $root->next() !== null
        ) {
            throw new PortableConfigurationException(
                'MARKDOWN_STEPS_ORDERED_LIST_REQUIRED',
                'A steps block must contain one Markdown ordered list.',
            );
        }
        $content = (string) $rendered;
        $content = preg_replace(
            '/^<ol\b/',
            '<ol class="flex flex-col gap-2 p-inline-start-3"',
            $content,
            1,
        ) ?? $content;

        return '<section class="bg-surface-0 border border-outline-variant radius-2 p-3">'
            . $content . '</section>';
    }

    private function decorateNativeMarkdown(string $html): string
    {
        $html = str_replace(
            '<table>',
            '<div class="overflow-auto"><table class="table table-border table-stripe">',
            $html,
        );
        $html = str_replace('</table>', '</table></div>', $html);

        return str_replace(
            '<pre>',
            '<pre class="bg-surface-container border border-outline-variant radius-2 p-2 overflow-auto">',
            $html,
        );
    }

    /** @return array<string, mixed> */
    private function inspectDirectives(string $markdown): array
    {
        try {
            return $this->inspector->inspectDirectives($markdown, DirectiveBlockStartParser::PORTABLE);
        } catch (DirectiveLimitExceeded $exception) {
            throw new PortableConfigurationException(
                'MARKDOWN_BLOCK_LIMIT_EXCEEDED',
                $exception->getMessage(),
            );
        }
    }

    /** @return array<string, mixed> */
    private function inspectFrameworkDirectives(string $markdown): array
    {
        try {
            return $this->inspector->inspectDirectives($markdown, DirectiveBlockStartParser::FRAMEWORK);
        } catch (DirectiveLimitExceeded $exception) {
            throw new PortableConfigurationException(
                'MARKDOWN_BLOCK_LIMIT_EXCEEDED',
                $exception->getMessage(),
            );
        }
    }
}
