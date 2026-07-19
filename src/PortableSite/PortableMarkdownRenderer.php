<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Output\RenderedContentInterface;
use League\CommonMark\Util\RegexHelper;
use Simai\Docara\ComponentCatalog\TypedComponentDefinitionRepository;
use Simai\Docara\ComponentCatalog\TypedRendererId;
use Simai\Docara\Markdown\CommonMarkInspector;
use Simai\Docara\Markdown\DirectiveBlockStartParser;
use Simai\Docara\Markdown\DirectiveLimitExceeded;
use Simai\Docara\Markdown\DirectiveOpeningMatcher;
use Simai\Docara\Portable\PortableConfigurationException;

final class PortableMarkdownRenderer
{
    private MarkdownConverter $converter;

    private CommonMarkInspector $inspector;

    private TypedComponentDefinitionRepository $definitions;

    public function __construct(
        ?PortableMarkdownProfile $profile = null,
        ?TypedComponentDefinitionRepository $definitions = null,
    ) {
        $profile ??= PortableMarkdownProfile::bundled();
        $this->definitions = $definitions ?? TypedComponentDefinitionRepository::bundled();
        $this->converter = new MarkdownConverter($profile->environment());
        $this->inspector = new CommonMarkInspector(
            directiveMatcher: new DirectiveOpeningMatcher($this->definitions->names()),
        );
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
            $rendered = match (TypedRendererId::from($block['renderer'])) {
                TypedRendererId::Card => $this->renderCard($content),
                TypedRendererId::Steps => $this->renderSteps($content),
                TypedRendererId::Cta => $this->renderCta($content),
                TypedRendererId::Features => $this->renderFeatures($content),
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
     *     1: list<array{type: string, renderer: string, markdown: string, placeholder: string}>,
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
            $definition = $this->definitions->byName($type);
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
                'renderer' => (string) $definition['renderer'],
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
            if (! $this->inspector->isDirectivePlacementLine($line, DirectiveBlockStartParser::PORTABLE)
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

    private function renderCard(RenderedContentInterface $rendered): string
    {
        return '<section class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1">'
            . (string) $rendered . '</section>';
    }

    private function renderCta(RenderedContentInterface $rendered): string
    {
        $paragraph = $rendered->getDocument()->firstChild();
        $link = $paragraph?->firstChild();
        if (! $paragraph instanceof Paragraph
            || $paragraph->next() !== null
            || ! $link instanceof Link
            || $link->next() !== null
        ) {
            throw new PortableConfigurationException(
                'MARKDOWN_CTA_LINK_REQUIRED',
                'A CTA block must contain exactly one Markdown link.',
            );
        }
        if (preg_match(RegexHelper::REGEX_UNSAFE_PROTOCOL, $link->getUrl()) === 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_CTA_LINK_UNSAFE',
                'A CTA block cannot use an unsafe link protocol.',
            );
        }

        $label = '';
        $walker = $link->walker();
        while (($event = $walker->next()) !== null) {
            if (! $event->isEntering()) {
                continue;
            }
            $node = $event->getNode();
            if (! $node instanceof Link
                && ! $node instanceof Text
                && ! $node instanceof Emphasis
                && ! $node instanceof Strong
                && ! $node instanceof Newline
                && ! $node instanceof Strikethrough
            ) {
                throw new PortableConfigurationException(
                    'MARKDOWN_CTA_LINK_REQUIRED',
                    'A CTA block link may contain only bounded textual Markdown.',
                );
            }
            if ($node instanceof Text) {
                $label .= $node->getLiteral();
            }
        }
        if (! $this->containsVisibleText($label)) {
            throw new PortableConfigurationException(
                'MARKDOWN_CTA_LINK_REQUIRED',
                'A CTA block link must have an accessible text label.',
            );
        }

        $content = trim((string) $rendered);
        if (preg_match('/^<p><a(?<attributes>[^>]*)>(?<label>.*)<\/a><\/p>$/su', $content, $match) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_CTA_LINK_REQUIRED',
                'A CTA block could not be represented as one native link.',
            );
        }

        return '<a data-docara-block="cta" class="docara-cta-link sf-button sf-button--default sf-button--primary sf-button--size-1 bg-primary color-on-primary p-1/2 line-none radius-default inline-flex items-center content-main-center decoration-none w-full sm:w-auto sm:self-start"'
            . $match['attributes'] . '><span class="sf-button-text-container">'
            . $match['label'] . '</span></a>';
    }

    private function renderFeatures(RenderedContentInterface $rendered): string
    {
        $root = $rendered->getDocument()->firstChild();
        $listCount = 0;
        $walker = $rendered->getDocument()->walker();
        while (($event = $walker->next()) !== null) {
            if ($event->isEntering() && $event->getNode() instanceof ListBlock) {
                $listCount++;
            }
        }
        if (! $root instanceof ListBlock
            || $root->getListData()->type !== ListBlock::TYPE_BULLET
            || $root->next() !== null
            || $listCount !== 1
        ) {
            throw new PortableConfigurationException(
                'MARKDOWN_FEATURES_UNORDERED_LIST_REQUIRED',
                'A features block must contain one flat unordered Markdown list.',
            );
        }

        $items = iterator_to_array($root->children());
        if (count($items) < 2 || count($items) > 6) {
            throw new PortableConfigurationException(
                'MARKDOWN_FEATURES_ITEM_COUNT_INVALID',
                'A features block must contain between two and six list items.',
            );
        }

        foreach ($items as $item) {
            if (! $item instanceof ListItem) {
                throw new PortableConfigurationException(
                    'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID',
                    'Every features block item must contain one plain Markdown paragraph.',
                );
            }
            $paragraph = $item->firstChild();
            if (! $paragraph instanceof Paragraph || $paragraph->next() !== null) {
                throw new PortableConfigurationException(
                    'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID',
                    'Every features block item must contain one plain Markdown paragraph.',
                );
            }

            $text = '';
            $itemWalker = $paragraph->walker();
            while (($event = $itemWalker->next()) !== null) {
                if (! $event->isEntering()) {
                    continue;
                }
                $node = $event->getNode();
                if (! $node instanceof Paragraph
                    && ! $node instanceof Text
                    && ! $node instanceof Code
                    && ! $node instanceof Emphasis
                    && ! $node instanceof Strong
                    && ! $node instanceof Link
                    && ! $node instanceof Newline
                    && ! $node instanceof Strikethrough
                ) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID',
                        'A features block item contains unsupported Markdown content.',
                    );
                }
                if ($node instanceof Text || $node instanceof Code) {
                    $text .= $node->getLiteral();
                }
            }
            if (! $this->containsVisibleText($text)) {
                throw new PortableConfigurationException(
                    'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED',
                    'Every features block item must contain visible text.',
                );
            }
        }

        $content = trim((string) $rendered);
        $content = preg_replace(
            '/^<ul>/',
            '<ul data-docara-block="features" class="docara-feature-grid grid grid-col-1 lg:grid-col-3 gap-2 list-none m-0 p-0">',
            $content,
            1,
        ) ?? $content;

        return preg_replace(
            '/<li>/',
            '<li class="bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1">',
            $content,
        ) ?? $content;
    }

    private function containsVisibleText(string $text): bool
    {
        return preg_match('/[\p{L}\p{N}\p{P}\p{S}]/u', $text) === 1;
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
                $this->directiveLimitErrorCode($exception),
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
                $this->directiveLimitErrorCode($exception),
                $exception->getMessage(),
            );
        }
    }

    private function directiveLimitErrorCode(DirectiveLimitExceeded $exception): string
    {
        return $exception->family === DirectiveBlockStartParser::FRAMEWORK
            ? 'FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED'
            : 'MARKDOWN_BLOCK_LIMIT_EXCEEDED';
    }
}
