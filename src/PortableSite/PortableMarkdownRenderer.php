<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\Strikethrough\Strikethrough;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
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

    private PortableColumnRegionParser $columnRegions;

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
        $this->columnRegions = new PortableColumnRegionParser($this->inspector);
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
            $renderer = TypedRendererId::from($block['renderer']);
            $rendered = match ($renderer) {
                TypedRendererId::Card => $this->renderCard($this->converter->convert($blockMarkdown)),
                TypedRendererId::Columns => $this->renderColumns(
                    $block['markdown'],
                    $referenceDefinitions,
                ),
                TypedRendererId::Steps => $this->renderSteps($this->converter->convert($blockMarkdown)),
                TypedRendererId::Cta => $this->renderCta($this->converter->convert($blockMarkdown)),
                TypedRendererId::Features => $this->renderFeatures($this->converter->convert($blockMarkdown)),
                TypedRendererId::Hero => $this->renderHero($this->converter->convert($blockMarkdown)),
                TypedRendererId::Logos => $this->renderLogos($this->converter->convert($blockMarkdown)),
                TypedRendererId::Promo => $this->renderPromo($this->converter->convert($blockMarkdown)),
                TypedRendererId::Showcase => $this->renderShowcase($this->converter->convert($blockMarkdown)),
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
     * are deliberately semantic Markdown plus SIMAI Framework utilities.
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

        return '<a data-docara-block="cta" class="docara-cta-link sf-button sf-button--default sf-button--primary sf-button--size-1 radius-default inline-flex items-center content-main-center decoration-none w-full sm:w-auto sm:self-start"'
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
                    && ! $node instanceof Image
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
            $imageCount = 0;
            $firstInline = $paragraph->firstChild();
            $itemWalker = $paragraph->walker();
            while (($event = $itemWalker->next()) !== null) {
                if (! $event->isEntering() || ! $event->getNode() instanceof Image) {
                    continue;
                }
                $image = $event->getNode();
                $this->assertSafeUrl($image->getUrl(), 'MARKDOWN_FEATURES_IMAGE_UNSAFE');
                $imageCount++;
            }
            if ($imageCount > 1 || ($imageCount === 1 && ! $firstInline instanceof Image)) {
                throw new PortableConfigurationException(
                    'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID',
                    'A features item may start with at most one image.',
                );
            }
            if ($imageCount === 1 && $firstInline instanceof Image && $firstInline->next() === null) {
                throw new PortableConfigurationException(
                    'MARKDOWN_FEATURES_ITEM_CONTENT_INVALID',
                    'A features image must be followed by visible item text.',
                );
            }
            if (! $this->containsVisibleText($text)) {
                throw new PortableConfigurationException(
                    'MARKDOWN_FEATURES_ITEM_TEXT_REQUIRED',
                    'Every features block item must contain visible text.',
                );
            }
        }

        $itemCount = count($items);
        $gridClasses = $itemCount === 4
            ? 'grid grid-col-1 md:grid-col-2 lg:grid-col-4 gap-2'
            : 'grid grid-col-1 lg:grid-col-3 gap-2';
        $content = trim((string) $rendered);
        $content = preg_replace(
            '/^<ul>/',
            '<ul data-docara-block="features" class="' . $gridClasses . ' list-none m-0 p-0">',
            $content,
            1,
        ) ?? $content;
        $content = preg_replace(
            '/<img(?<attributes>[^>]*)\s*\/?>/u',
            '<img data-docara-media="feature-icon" class="'
                . $this->mediaUtilityClasses('feature-icon')
                . '" loading="lazy" decoding="async"$1>',
            $content,
        ) ?? $content;

        return preg_replace(
            '/<li>/',
            '<li class="bg-surface-0 border border-outline-variant radius-2 p-3 flex min-w-0 max-w-none flex-col gap-1">',
            $content,
        ) ?? $content;
    }

    private function renderHero(RenderedContentInterface $rendered): string
    {
        $nodes = iterator_to_array($rendered->getDocument()->children());
        $heading = $nodes[0] ?? null;
        if (! $heading instanceof Heading || $heading->getLevel() !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_HERO_H1_REQUIRED',
                'A hero block must start with one level-one Markdown heading.',
            );
        }

        $headingText = $this->inlineVisibleText($heading);
        if (! $this->containsVisibleText($headingText)) {
            throw new PortableConfigurationException(
                'MARKDOWN_HERO_H1_REQUIRED',
                'A hero block heading must contain visible text.',
            );
        }

        $descriptionCount = 0;
        $actionCount = 0;
        $imageCount = 0;
        $phase = 'description';
        foreach (array_slice($nodes, 1) as $index => $node) {
            if (! $node instanceof Paragraph) {
                throw new PortableConfigurationException(
                    'MARKDOWN_HERO_STRUCTURE_INVALID',
                    'A hero block may contain only a heading and bounded Markdown paragraphs.',
                );
            }

            $first = $node->firstChild();
            if ($first instanceof Image && $first->next() === null) {
                if ($imageCount > 0 || $index !== count($nodes) - 2) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_HERO_STRUCTURE_INVALID',
                        'A hero block may end with at most one image.',
                    );
                }
                $this->assertSafeUrl($first->getUrl(), 'MARKDOWN_HERO_IMAGE_UNSAFE');
                $imageCount++;
                $phase = 'image';

                continue;
            }

            if ($first instanceof Link && $first->next() === null) {
                if ($phase === 'image' || $actionCount > 1) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_HERO_STRUCTURE_INVALID',
                        'A hero action must appear after the description and before the optional image.',
                    );
                }
                $this->assertSafeUrl($first->getUrl(), 'MARKDOWN_HERO_LINK_UNSAFE');
                if (! $this->containsVisibleText($this->inlineVisibleText($first))) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_HERO_LINK_REQUIRED',
                        'A hero action must have an accessible text label.',
                    );
                }
                $actionCount++;
                $phase = 'action';

                continue;
            }

            if ($phase !== 'description' || ! $this->paragraphHasBoundedInlineContent($node)) {
                throw new PortableConfigurationException(
                    'MARKDOWN_HERO_STRUCTURE_INVALID',
                    'Hero description paragraphs must precede the action and use bounded inline Markdown.',
                );
            }
            $descriptionCount++;
        }

        if ($descriptionCount < 1 || $descriptionCount > 2) {
            throw new PortableConfigurationException(
                'MARKDOWN_HERO_DESCRIPTION_REQUIRED',
                'A hero block must contain one or two description paragraphs.',
            );
        }

        $content = trim((string) $rendered);
        $content = preg_replace_callback(
            '/^<h1(?<attributes>[^>]*)>/u',
            static fn (array $match): string => '<h1' . (string) $match['attributes'] . ' class="m-0">',
            $content,
            1,
        ) ?? $content;

        $image = '';
        if ($imageCount === 1) {
            $content = preg_replace_callback(
                '/<p><img(?<attributes>[^>]*)\s*\/?><\/p>/u',
                function (array $match) use (&$image): string {
                    $attributes = rtrim((string) $match['attributes']);
                    $decorative = preg_match('/\balt=""/u', $attributes) === 1
                        ? ' aria-hidden="true"'
                        : '';
                    $image = '<div class="min-w-0 flex items-center content-main-center">'
                        . '<img data-docara-media="hero" class="'
                        . $this->mediaUtilityClasses('hero')
                        . '" loading="eager" fetchpriority="high" decoding="async"'
                        . $decorative . $attributes . '>'
                        . '</div>';

                    return '';
                },
                $content,
                1,
            ) ?? $content;
        }

        if ($actionCount > 0) {
            $actionIndex = 0;
            $content = preg_replace_callback(
                '/<p><a(?<attributes>[^>]*)>(?<label>.*?)<\/a><\/p>/su',
                static function (array $match) use (&$actionIndex): string {
                    $type = $actionIndex === 0 ? 'primary' : 'on-surface sf-button--outline';
                    $actionIndex++;

                    return '<a data-docara-hero-action class="sf-button sf-button--default sf-button--'
                        . $type
                        . ' sf-button--size-1 box-border h-c8 lg:h-d0 radius-default inline-flex items-center content-main-center decoration-none w-full sm:w-auto sm:self-start"'
                        . (string) $match['attributes'] . '><span class="sf-button-text-container">'
                        . (string) $match['label'] . '</span></a>';
                },
                $content,
                2,
            ) ?? $content;
            $content = preg_replace(
                '/(?<actions>(?:<a data-docara-hero-action\b.*?<\/a>\s*){1,2})/su',
                '<div data-docara-hero-actions class="flex flex-wrap items-center gap-1">$1</div>',
                $content,
                1,
            ) ?? $content;
        }

        $columns = $image === '' ? 'grid-col-1' : 'grid-col-1 lg:grid-col-2';

        return '<section data-docara-block="hero" data-docara-width="full" class="bg-surface-container overflow-hidden">'
            . '<div data-docara-container class="container m-inline-auto grid ' . $columns . ' gap-4 items-center p-4">'
            . '<div class="min-w-0 flex flex-col gap-2">' . $content . '</div>'
            . $image . '</div></section>';
    }

    private function renderLogos(RenderedContentInterface $rendered): string
    {
        $root = $rendered->getDocument()->firstChild();
        if (! $root instanceof ListBlock
            || $root->getListData()->type !== ListBlock::TYPE_BULLET
            || $root->next() !== null
        ) {
            throw new PortableConfigurationException(
                'MARKDOWN_LOGOS_UNORDERED_LIST_REQUIRED',
                'A logos block must contain one flat unordered Markdown list.',
            );
        }

        $items = iterator_to_array($root->children());
        if (count($items) < 2 || count($items) > 12) {
            throw new PortableConfigurationException(
                'MARKDOWN_LOGOS_ITEM_COUNT_INVALID',
                'A logos block must contain between two and twelve items.',
            );
        }
        foreach ($items as $item) {
            if (! $item instanceof ListItem) {
                throw new PortableConfigurationException(
                    'MARKDOWN_LOGOS_ITEM_CONTENT_INVALID',
                    'Every logos item must contain one Markdown paragraph.',
                );
            }
            $paragraph = $item->firstChild();
            if (! $paragraph instanceof Paragraph
                || $paragraph->next() !== null
                || ! $this->paragraphHasBoundedInlineContent($paragraph, allowImage: true)
            ) {
                throw new PortableConfigurationException(
                    'MARKDOWN_LOGOS_ITEM_CONTENT_INVALID',
                    'Every logos item must contain one bounded text, link or image paragraph.',
                );
            }
        }

        $content = trim((string) $rendered);
        $content = preg_replace(
            '/^<ul>/u',
            '<ul data-docara-block="logos" class="grid grid-col-2 md:grid-col-3 lg:grid-col-6 gap-2 list-none m-0 p-0">',
            $content,
            1,
        ) ?? $content;
        $content = preg_replace(
            '/<img(?<attributes>[^>]*)\s*\/?>/u',
            '<img data-docara-media="logo" class="'
                . $this->mediaUtilityClasses('logo')
                . '" loading="lazy" decoding="async"$1>',
            $content,
        ) ?? $content;

        return preg_replace(
            '/<li>/u',
            '<li class="min-w-0 flex items-center content-main-center color-on-surface-variant">',
            $content,
        ) ?? $content;
    }

    private function renderShowcase(RenderedContentInterface $rendered): string
    {
        return $this->renderMediaSection(
            rendered: $rendered,
            block: 'showcase',
            headingLevel: 2,
            imageRequired: true,
            actionRequired: false,
            surfaceClass: 'bg-surface-0',
        );
    }

    private function renderPromo(RenderedContentInterface $rendered): string
    {
        return $this->renderMediaSection(
            rendered: $rendered,
            block: 'promo',
            headingLevel: 2,
            imageRequired: false,
            actionRequired: true,
            surfaceClass: 'bg-surface-container',
        );
    }

    private function renderMediaSection(
        RenderedContentInterface $rendered,
        string $block,
        int $headingLevel,
        bool $imageRequired,
        bool $actionRequired,
        string $surfaceClass,
    ): string {
        $nodes = iterator_to_array($rendered->getDocument()->children());
        $heading = $nodes[0] ?? null;
        if (! $heading instanceof Heading || $heading->getLevel() !== $headingLevel) {
            throw new PortableConfigurationException(
                'MARKDOWN_' . strtoupper($block) . '_HEADING_REQUIRED',
                "A $block block must start with one level-$headingLevel Markdown heading.",
            );
        }

        $descriptionCount = 0;
        $actionCount = 0;
        $imageCount = 0;
        $phase = 'description';
        foreach (array_slice($nodes, 1) as $index => $node) {
            if (! $node instanceof Paragraph) {
                throw new PortableConfigurationException(
                    'MARKDOWN_' . strtoupper($block) . '_STRUCTURE_INVALID',
                    "A $block block may contain only bounded Markdown paragraphs.",
                );
            }
            $first = $node->firstChild();
            if ($first instanceof Image && $first->next() === null) {
                if ($imageCount > 0 || $index !== count($nodes) - 2) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_' . strtoupper($block) . '_STRUCTURE_INVALID',
                        "A $block block may end with at most one image.",
                    );
                }
                $this->assertSafeUrl(
                    $first->getUrl(),
                    'MARKDOWN_' . strtoupper($block) . '_IMAGE_UNSAFE',
                );
                if ($block === 'showcase'
                    && ! $this->containsVisibleText($this->inlineVisibleText($first))
                ) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_SHOWCASE_IMAGE_ALT_REQUIRED',
                        'A showcase image must have meaningful alternative text.',
                    );
                }
                $imageCount++;
                $phase = 'image';

                continue;
            }
            if ($first instanceof Link && $first->next() === null) {
                if ($phase === 'image' || $actionCount > 0) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_' . strtoupper($block) . '_STRUCTURE_INVALID',
                        "A $block action must follow the description and precede the optional image.",
                    );
                }
                $this->assertSafeUrl(
                    $first->getUrl(),
                    'MARKDOWN_' . strtoupper($block) . '_LINK_UNSAFE',
                );
                if (! $this->containsVisibleText($this->inlineVisibleText($first))) {
                    throw new PortableConfigurationException(
                        'MARKDOWN_' . strtoupper($block) . '_LINK_REQUIRED',
                        "A $block action must have an accessible text label.",
                    );
                }
                $actionCount++;
                $phase = 'action';

                continue;
            }
            if ($phase !== 'description' || ! $this->paragraphHasBoundedInlineContent($node)) {
                throw new PortableConfigurationException(
                    'MARKDOWN_' . strtoupper($block) . '_STRUCTURE_INVALID',
                    ucfirst($block) . ' description paragraphs must precede the action and media.',
                );
            }
            $descriptionCount++;
        }

        if ($descriptionCount < 1 || $descriptionCount > 2) {
            throw new PortableConfigurationException(
                'MARKDOWN_' . strtoupper($block) . '_DESCRIPTION_REQUIRED',
                "A $block block must contain one or two description paragraphs.",
            );
        }
        if ($imageRequired && $imageCount !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_' . strtoupper($block) . '_IMAGE_REQUIRED',
                "A $block block must contain one image.",
            );
        }
        if ($actionRequired && $actionCount !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_' . strtoupper($block) . '_LINK_REQUIRED',
                "A $block block must contain one action link.",
            );
        }

        $content = trim((string) $rendered);
        $content = preg_replace_callback(
            '/^<h' . $headingLevel . '(?<attributes>[^>]*)>/u',
            static fn (array $match): string => '<h' . $headingLevel
                . (string) $match['attributes'] . ' class="m-0">',
            $content,
            1,
        ) ?? $content;

        $image = '';
        if ($imageCount === 1) {
            $content = preg_replace_callback(
                '/<p><img(?<attributes>[^>]*)\s*\/?><\/p>/u',
                function (array $match) use (&$image, $block): string {
                    $attributes = rtrim((string) $match['attributes']);
                    $decorative = preg_match('/\balt=""/u', $attributes) === 1
                        ? ' aria-hidden="true"'
                        : '';
                    $image = '<div class="min-w-0 flex items-center content-main-center">'
                        . '<img data-docara-media="' . $block . '" class="'
                        . $this->mediaUtilityClasses($block)
                        . '" loading="lazy" decoding="async"'
                        . $decorative . $attributes . '>'
                        . '</div>';

                    return '';
                },
                $content,
                1,
            ) ?? $content;
        }
        if ($actionCount === 1) {
            $content = preg_replace_callback(
                '/<p><a(?<attributes>[^>]*)>(?<label>.*?)<\/a><\/p>/su',
                static fn (array $match): string => '<a data-docara-' . $block
                    . '-action class="sf-button sf-button--default sf-button--primary sf-button--size-1 radius-default inline-flex items-center content-main-center decoration-none w-full sm:w-auto sm:self-start"'
                    . (string) $match['attributes'] . '><span class="sf-button-text-container">'
                    . (string) $match['label'] . '</span></a>',
                $content,
                1,
            ) ?? $content;
        }

        $columns = $image === '' ? 'grid-col-1' : 'grid-col-1 lg:grid-col-2';

        return '<section data-docara-block="' . $block . '" data-docara-width="full" class="'
            . $surfaceClass . ' overflow-hidden"><div data-docara-container class="container m-inline-auto grid '
            . $columns . ' gap-4 items-center p-4"><div class="min-w-0 flex flex-col gap-2">'
            . $content . '</div>' . $image . '</div></section>';
    }

    private function renderColumns(string $markdown, string $referenceDefinitions): string
    {
        $regions = $this->columnRegions->parse($markdown);
        $count = count($regions);
        $classes = match ($count) {
            2 => 'grid grid-col-1 md:grid-col-2 gap-2',
            3 => 'grid grid-col-1 md:grid-col-2 lg:grid-col-3 gap-2',
            4 => 'grid grid-col-1 md:grid-col-2 lg:grid-col-4 gap-2',
        };
        $content = [];
        foreach ($regions as $region) {
            if ($referenceDefinitions !== '') {
                $region = $referenceDefinitions . "\n\n" . $region;
            }
            $html = trim((string) $this->converter->convert($region));
            if ($html === '') {
                throw new PortableConfigurationException(
                    'MARKDOWN_COLUMNS_REGION_EMPTY',
                    'Every columns region must render visible Markdown content.',
                );
            }
            $html = preg_replace(
                '/<img(?<attributes>[^>]*)\s*\/?>/u',
                '<img data-docara-media="card" class="'
                    . $this->mediaUtilityClasses('card')
                    . '" loading="lazy" decoding="async"$1>',
                $html,
            ) ?? $html;
            $content[] = '<div class="min-w-0">' . $html . '</div>';
        }

        return '<section data-docara-block="columns" data-docara-columns="' . $count
            . '" class="' . $classes . '">' . implode('', $content) . '</section>';
    }

    private function mediaUtilityClasses(string $kind): string
    {
        return match ($kind) {
            'feature-icon' => 'block w-e0 h-e0 object-contain',
            'card' => 'block w-full h-auto aspect-4x3 object-cover radius-2',
            'logo' => 'block w-full h-auto max-w-f0 max-h-d2 object-contain',
            'hero', 'promo', 'showcase' => 'block w-full h-auto aspect-16x9 object-contain',
            default => 'block w-full h-auto',
        };
    }

    private function containsVisibleText(string $text): bool
    {
        return preg_match('/[\p{L}\p{N}\p{P}\p{S}]/u', $text) === 1;
    }

    private function assertSafeUrl(string $url, string $errorCode): void
    {
        if (preg_match(RegexHelper::REGEX_UNSAFE_PROTOCOL, $url) === 1) {
            throw new PortableConfigurationException(
                $errorCode,
                'A Docara landing block cannot use an unsafe URL protocol.',
            );
        }
    }

    private function inlineVisibleText(Node $root): string
    {
        $text = '';
        $walker = $root->walker();
        while (($event = $walker->next()) !== null) {
            if (! $event->isEntering()) {
                continue;
            }
            $node = $event->getNode();
            if ($node instanceof Text || $node instanceof Code) {
                $text .= $node->getLiteral();
            }
        }

        return $text;
    }

    private function paragraphHasBoundedInlineContent(Paragraph $paragraph, bool $allowImage = false): bool
    {
        $visible = '';
        $walker = $paragraph->walker();
        while (($event = $walker->next()) !== null) {
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
                && (! $allowImage || ! $node instanceof Image)
            ) {
                return false;
            }
            if ($node instanceof Link || $node instanceof Image) {
                $this->assertSafeUrl(
                    $node->getUrl(),
                    $node instanceof Image ? 'MARKDOWN_IMAGE_URL_UNSAFE' : 'MARKDOWN_LINK_URL_UNSAFE',
                );
            }
            if ($node instanceof Text || $node instanceof Code) {
                $visible .= $node->getLiteral();
            }
        }

        return $this->containsVisibleText($visible);
    }

    private function decorateNativeMarkdown(string $html): string
    {
        $html = str_replace(
            '<table>',
            '<div class="overflow-auto"><table class="table table-border table-stripe">',
            $html,
        );
        $html = str_replace('</table>', '</table></div>', $html);

        return preg_replace_callback(
            '/<pre><code(?P<attributes>[^>]*)>(?P<content>.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $attributes = (string) ($matches['attributes'] ?? '');

                return '<div data-docara-code-block class="source init sf-code-surface docara-code-block">'
                    . '<pre class="sf-code-surface__scroll docara-code-scroll"><code'
                    . $attributes . '>' . (string) ($matches['content'] ?? '') . '</code></pre>'
                    . '</div>';
            },
            $html,
        ) ?? $html;
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
