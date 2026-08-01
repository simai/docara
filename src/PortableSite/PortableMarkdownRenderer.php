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
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Document\ComponentAliasRegistry;
use Simai\Docara\Document\ComponentBlockNode;
use Simai\Docara\Document\SourceLocation;
use Simai\Docara\Document\SourceNode;
use Simai\Docara\Markdown\AuthoringAttributeParser;
use Simai\Docara\Markdown\CommonMarkInspector;
use Simai\Docara\Markdown\DirectiveBlockStartParser;
use Simai\Docara\Markdown\DirectiveLimitExceeded;
use Simai\Docara\Markdown\DirectiveOpeningMatcher;
use Simai\Docara\Markdown\InlineComponentRenderer;
use Simai\Docara\Portable\PortableConfigurationException;

final class PortableMarkdownRenderer
{
    private MarkdownConverter $converter;

    private CommonMarkInspector $inspector;

    private TypedComponentDefinitionRepository $definitions;

    private PortableColumnRegionParser $columnRegions;

    private InlineComponentRenderer $inlineComponents;

    private AuthoringAttributeParser $attributes;

    private PortableExampleRenderer $examples;

    private SmartComponentGateway $components;

    public function __construct(
        ?PortableMarkdownProfile $profile = null,
        ?TypedComponentDefinitionRepository $definitions = null,
        ?SmartComponentGateway $components = null,
    ) {
        $profile ??= PortableMarkdownProfile::bundled();
        $this->definitions = $definitions ?? TypedComponentDefinitionRepository::bundled();
        $this->converter = new MarkdownConverter($profile->environment());
        $this->inspector = new CommonMarkInspector(
            directiveMatcher: new DirectiveOpeningMatcher($this->definitions->names()),
        );
        $this->columnRegions = new PortableColumnRegionParser($this->inspector);
        $this->components = $components ?? SmartComponentGateway::content();
        $this->inlineComponents = new InlineComponentRenderer(components: $this->components);
        $this->attributes = new AuthoringAttributeParser;
        $this->examples = new PortableExampleRenderer;
    }

    public function componentGateway(): SmartComponentGateway
    {
        return $this->components;
    }

    public function render(string $markdown, ?string $sourceRoot = null, ?string $sourceFile = null): string
    {
        if (preg_match('//u', $markdown) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_BLOCK_INPUT_INVALID',
                'Portable Markdown must be valid UTF-8.',
            );
        }

        $nativeInspection = $this->inspector->inspect($markdown);
        $inline = $this->inlineComponents->extract(
            $markdown,
            $nativeInspection['literal_code_lines'],
            $sourceFile ?? '@markdown',
        );
        $markdown = $inline['markdown'];
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
                TypedRendererId::Card => $this->renderCard(
                    $this->render($blockMarkdown, $sourceRoot, $sourceFile),
                    $block['attributes'],
                ),
                TypedRendererId::Columns => $this->renderColumns(
                    $block['markdown'],
                    $referenceDefinitions,
                ),
                TypedRendererId::Steps => $this->renderSteps($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Cta => $this->renderCta($this->converter->convert($blockMarkdown)),
                TypedRendererId::Features => $this->renderFeatures($this->converter->convert($blockMarkdown)),
                TypedRendererId::Hero => $this->renderHero($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Logos => $this->renderLogos($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Promo => $this->renderPromo($this->converter->convert($blockMarkdown)),
                TypedRendererId::Showcase => $this->renderShowcase($this->converter->convert($blockMarkdown)),
                TypedRendererId::Details => $this->renderDetails($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Download => $this->renderDownload(
                    $this->converter->convert($blockMarkdown),
                    $block['attributes'],
                ),
                TypedRendererId::Embed => $this->renderEmbed($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Example => $this->renderExample($block['markdown'], $block['attributes']),
                TypedRendererId::Figure => $this->renderFigure($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Grid => $this->renderGrid($block['markdown'], $block['attributes']),
                TypedRendererId::Media => $this->renderMedia($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Tree => $this->renderTree($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Alert => $this->renderAlert(
                    $this->converter->convert($blockMarkdown),
                    $block['attributes'],
                    $sourceFile,
                ),
                TypedRendererId::Tabs => $this->renderTabs($this->converter->convert($blockMarkdown)),
                TypedRendererId::Banner => $this->renderBanner($this->converter->convert($blockMarkdown), $block['attributes']),
                TypedRendererId::Diagram => $this->renderDiagram($block['markdown'], $block['attributes']),
                TypedRendererId::Math => $this->renderMath($block['markdown'], $block['attributes']),
                TypedRendererId::Html => $this->renderHtml($block['markdown']),
                TypedRendererId::Code => $this->renderCode($block['attributes'], $sourceRoot, $sourceFile),
                TypedRendererId::Backlinks => $this->renderBacklinks($block['attributes']),
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

        foreach ($inline['replacements'] as $placeholder => $replacement) {
            if (substr_count($html, $placeholder) !== 1) {
                throw new PortableConfigurationException(
                    'MARKDOWN_INLINE_PLACEHOLDER_CARDINALITY_INVALID',
                    "Markdown inline placeholder [$placeholder] is ambiguous after rendering.",
                );
            }
            $html = str_replace($placeholder, $replacement, $html);
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
     *     1: list<array{type: string, renderer: string, markdown: string, placeholder: string, attributes: array<string,string>}>,
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
            $renderer = TypedRendererId::from((string) $definition['renderer']);
            $allowsEmptyBody = in_array($renderer, [TypedRendererId::Code, TypedRendererId::Backlinks], true);
            if (trim($bodyMarkdown) === '' && ! $allowsEmptyBody) {
                throw new PortableConfigurationException(
                    'MARKDOWN_BLOCK_EMPTY',
                    "Markdown block [$type] at line [$startLine] is empty.",
                );
            }
            $bodyInspection = $this->inspectDirectives($bodyMarkdown);
            $frameworkBodyInspection = $this->inspectFrameworkDirectives($bodyMarkdown);
            $nestedPortable = $bodyInspection['directives'];
            $gridNesting = $type === 'grid'
                && $nestedPortable !== []
                && array_values(array_unique(array_column($nestedPortable, 'name'))) === ['card'];
            $cardFigureNesting = $type === 'card'
                && $nestedPortable !== []
                && array_values(array_unique(array_column($nestedPortable, 'name'))) === ['figure'];
            if ((! $gridNesting && ! $cardFigureNesting && $nestedPortable !== [])
                || $frameworkBodyInspection['directives'] !== []
                || (! $gridNesting && ! $cardFigureNesting && $this->inspector->containsDirectiveLikeOpening($bodyMarkdown))
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
                'attributes' => $this->attributes->parse((string) ($directive['attributes'] ?? ''), $type),
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

    /** @param array<string,string> $attributes */
    private function renderSteps(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['view', 'current'], 'steps');
        $view = $this->attributeOneOf($attributes['view'] ?? 'timeline', ['list', 'timeline'], 'steps', 'view');
        $current = max(1, (int) ($attributes['current'] ?? '1'));
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
        if ($view === 'timeline') {
            $index = 0;
            $content = preg_replace_callback('/<li>/u', static function () use (&$index, $current): string {
                $index++;
                $state = $index < $current ? 'complete' : ($index === $current ? 'current' : 'pending');
                $marker = $index < $current ? '&#10003;' : (string) $index;

                return '<li data-step-state="' . $state . '" class="relative list-none p-inline-start-4 p-block-end-2">'
                    . '<span aria-hidden="true" class="absolute inset-inline-start-0 top-0 sf-badge sf-badge--main sf-badge--primary sf-badge--size-1 inline-flex items-center content-main-center">'
                    . $marker . '</span>';
            }, $content) ?? $content;
            $content = preg_replace('/^<ol\b/', '<ol class="m-0 p-0"', $content, 1) ?? $content;
        } else {
            $content = preg_replace('/^<ol\b/', '<ol class="flex flex-col gap-1 p-inline-start-3"', $content, 1) ?? $content;
        }

        return '<section data-docara-block="steps" data-view="' . $view
            . '" class="bg-surface-0 border border-outline-variant radius-2 p-3 m-bottom-1">'
            . $content . '</section>';
    }

    /** @param array<string,string> $attributes */
    private function renderDetails(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['open', 'view'], 'details');
        $open = $this->attributeBoolean($attributes['open'] ?? 'false', 'details', 'open');
        $view = $this->attributeOneOf($attributes['view'] ?? 'surface', ['surface', 'lines'], 'details', 'view');
        $nodes = iterator_to_array($rendered->getDocument()->children());
        $heading = $nodes[0] ?? null;
        if (! $heading instanceof Heading || ! in_array($heading->getLevel(), [2, 3, 4], true)) {
            throw new PortableConfigurationException(
                'MARKDOWN_DETAILS_HEADING_REQUIRED',
                'A details block must start with a level-two, level-three or level-four heading.',
            );
        }
        $title = $this->inlineVisibleText($heading);
        if (! $this->containsVisibleText($title) || count($nodes) < 2) {
            throw new PortableConfigurationException(
                'MARKDOWN_DETAILS_CONTENT_REQUIRED',
                'A details block requires a visible title and content.',
            );
        }
        $content = trim((string) $rendered);
        $content = preg_replace('/^<h[2-4][^>]*>.*?<\/h[2-4]>\s*/su', '', $content, 1) ?? $content;

        $classes = $view === 'lines'
            ? 'border-block-end border-outline-variant p-block-1 m-bottom-1'
            : 'bg-surface-0 border border-outline-variant radius-2 p-2 m-bottom-1';

        return '<details data-docara-block="details" data-view="' . $view . '" class="' . $classes . '"'
            . ($open ? ' open' : '') . '><summary class="flex items-center gap-1 cursor-pointer font-bold">'
            . $this->escapeHtml($title) . '</summary><div class="p-block-start-1">' . $content . '</div></details>';
    }

    /** @param array<string,string> $attributes */
    private function renderDownload(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['name', 'format', 'size', 'checksum', 'action'], 'download');
        $action = $this->attributeOneOf($attributes['action'] ?? 'download', ['download', 'open'], 'download', 'action');
        $content = trim((string) $rendered);
        if (preg_match('/^<p><a(?<attributes>[^>]*)>(?<label>.+)<\/a><\/p>$/su', $content, $match) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_DOWNLOAD_LINK_REQUIRED',
                'A download block must contain exactly one Markdown link.',
            );
        }

        $name = trim($attributes['name'] ?? '');
        $meta = array_values(array_filter([
            trim($attributes['format'] ?? ''),
            trim($attributes['size'] ?? ''),
            trim($attributes['checksum'] ?? ''),
        ], static fn (string $value): bool => $value !== ''));
        $icon = $action === 'open' ? 'open_in_new' : 'download';
        $download = $action === 'download' ? ' download' : '';

        if ($name === '' && $meta === []) {
            return '<a data-docara-block="download" data-action="' . $action
                . '" class="sf-button sf-button--outline sf-button--on-surface sf-button--size-1 inline-flex items-center gap-1 decoration-none m-bottom-1"'
                . $match['attributes'] . $download . '><i class="sf-icon sf-icon-loaded" aria-hidden="true">' . $icon . '</i>'
                . '<span class="sf-button-text-container">' . $match['label'] . '</span></a>';
        }

        return '<section data-docara-block="download" data-action="' . $action
            . '" class="bg-surface-0 border border-outline-variant radius-2 p-2 flex flex-col md:flex-row md:items-center content-main-between gap-2 m-bottom-1">'
            . '<div class="min-w-0 flex items-start gap-1"><i class="sf-icon sf-icon-loaded color-primary" aria-hidden="true">description</i>'
            . '<div class="min-w-0 flex flex-col gap-1/3">'
            . ($name !== '' ? '<strong>' . $this->escapeHtml($name) . '</strong>' : '')
            . ($meta !== [] ? '<span class="color-on-surface-variant">' . $this->escapeHtml(implode(' · ', $meta)) . '</span>' : '')
            . '</div></div><a class="sf-button sf-button--outline sf-button--on-surface sf-button--size-1 inline-flex items-center gap-1 decoration-none"'
            . $match['attributes'] . $download . '><i class="sf-icon sf-icon-loaded" aria-hidden="true">' . $icon . '</i>'
            . '<span class="sf-button-text-container">' . $match['label'] . '</span></a></section>';
    }

    /** @param array<string,string> $attributes */
    private function renderExample(string $markdown, array $attributes): string
    {
        $this->assertAttributes($attributes, ['label'], 'example');
        $label = trim($attributes['label'] ?? 'Example');
        if ($label === '') {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_LABEL_INVALID',
                'An example label must contain visible text.',
            );
        }

        $sources = $this->exampleSources($markdown);
        $hasMarkdown = isset($sources['Markdown']);
        if ($hasMarkdown && count($sources) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_SOURCE_COMBINATION_INVALID',
                'A Markdown example cannot be combined with HTML, CSS or JavaScript sources.',
            );
        }
        if (! $hasMarkdown && ! isset($sources['HTML'])) {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_HTML_REQUIRED',
                'An HTML/CSS/JavaScript example must include an HTML source.',
            );
        }

        $preview = $hasMarkdown
            ? $this->render($sources['Markdown'])
            : $this->renderExampleDocument($sources);
        $renderedSources = [];
        foreach ($sources as $language => $source) {
            $renderedSources[$language] = $this->render(
                $this->sourceFence($source) . strtolower($language) . "\n"
                . $source . "\n" . $this->sourceFence($source) . "\n",
            );
        }

        return $this->examples->render(
            id: 'markdown-example-' . substr(hash('sha256', $markdown), 0, 12),
            preview: $preview,
            sources: $renderedSources,
            exampleLabel: $label,
            copyLabel: 'Copy code',
            copiedLabel: 'Code copied',
        );
    }

    private function sourceFence(string $source): string
    {
        preg_match_all('/`+/', $source, $backticks);
        preg_match_all('/~+/', $source, $tildes);
        $backtickLength = max(3, 1 + max(array_map('strlen', $backticks[0] ?: [''])));
        $tildeLength = max(3, 1 + max(array_map('strlen', $tildes[0] ?: [''])));

        return $tildeLength <= $backtickLength
            ? str_repeat('~', $tildeLength)
            : str_repeat('`', $backtickLength);
    }

    /** @return array<string,string> */
    private function exampleSources(string $markdown): array
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $markdown));
        if (preg_match_all(
            '/(?:\A|\n)(?<fence>`{3,}|~{3,})(?<language>markdown|html|css|javascript|js)\h*\n(?<source>.*?)\n\k<fence>(?=\n|\z)/su',
            $normalized,
            $matches,
            PREG_SET_ORDER,
        ) < 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_FENCED_SOURCE_REQUIRED',
                'An example must contain fenced Markdown or HTML/CSS/JavaScript source.',
            );
        }

        $remainder = preg_replace(
            '/(?:\A|\n)(?<fence>`{3,}|~{3,})(?:markdown|html|css|javascript|js)\h*\n.*?\n\k<fence>(?=\n|\z)/su',
            '',
            $normalized,
        );
        if (trim((string) $remainder) !== '') {
            throw new PortableConfigurationException(
                'MARKDOWN_EXAMPLE_SOURCE_ONLY',
                'An example block may contain only fenced source blocks.',
            );
        }

        $sources = [];
        foreach ($matches as $match) {
            $language = match (strtolower($match['language'])) {
                'html' => 'HTML',
                'css' => 'CSS',
                'js', 'javascript' => 'JavaScript',
                default => 'Markdown',
            };
            if (isset($sources[$language])) {
                throw new PortableConfigurationException(
                    'MARKDOWN_EXAMPLE_SOURCE_DUPLICATE',
                    "An example contains more than one [$language] source.",
                );
            }
            $sources[$language] = rtrim($match['source']);
        }

        return $sources;
    }

    /** @param array<string,string> $sources */
    private function renderExampleDocument(array $sources): string
    {
        $document = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . (isset($sources['CSS']) ? '<style>' . $sources['CSS'] . '</style>' : '')
            . '</head><body>' . $sources['HTML']
            . (isset($sources['JavaScript']) ? '<script>' . str_replace('</script', '<\\/script', $sources['JavaScript']) . '</script>' : '')
            . '</body></html>';

        return '<iframe title="Example" class="w-full border-0 min-h-i5 bg-surface-0" sandbox="allow-scripts" srcdoc="'
            . $this->escapeHtml($document) . '"></iframe>';
    }

    /** @param array<string,string> $attributes */
    private function renderEmbed(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['provider', 'id', 'ratio', 'title', 'consent'], 'embed');
        $ratioInput = str_replace('x', '/', strtolower($attributes['ratio'] ?? '16/9'));
        $ratio = $this->attributeOneOf($ratioInput, ['1/1', '4/3', '16/9', '21/9'], 'embed', 'ratio');
        $provider = $this->attributeOneOf($attributes['provider'] ?? 'generic', ['generic', 'video', 'map', 'external'], 'embed', 'provider');
        $consent = $this->attributeOneOf($attributes['consent'] ?? 'required', ['required', 'none'], 'embed', 'consent');
        $id = trim($attributes['id'] ?? '');
        if ($id !== '' && preg_match('/\A[A-Za-z0-9._-]{1,80}\z/D', $id) !== 1) {
            throw new PortableConfigurationException('MARKDOWN_EMBED_ID_INVALID', 'An embed id contains unsupported characters.');
        }
        $title = trim($attributes['title'] ?? 'Embedded content');
        $content = trim((string) $rendered);
        if (preg_match('/^<p><a href="(?<url>[^"]+)"[^>]*>.*<\/a><\/p>$/su', $content, $match) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_EMBED_LINK_REQUIRED',
                'An embed block must contain exactly one Markdown link.',
            );
        }
        $this->assertSafeUrl($match['url'], 'MARKDOWN_EMBED_URL_UNSAFE');

        $url = $this->escapeHtml($match['url']);
        $common = ' title="' . $this->escapeHtml($title)
            . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-presentation" allowfullscreen';
        $content = $consent === 'none'
            ? '<iframe src="' . $url . '"' . $common . '></iframe>'
            : '<div class="w-full h-full flex flex-col items-center content-main-center gap-1 p-2 text-center" data-docara-embed-consent>'
                . '<p class="m-0">External content is loaded only after confirmation.</p>'
                . '<button type="button" class="sf-button sf-button--main sf-button--primary sf-button--size-1" data-docara-embed-load>Load content</button>'
                . '</div><template data-docara-embed-template><iframe data-src="' . $url . '"' . $common . '></iframe></template>';

        return '<div data-docara-block="embed" data-provider="' . $provider . '" data-consent="' . $consent
            . '"' . ($id !== '' ? ' id="' . $this->escapeHtml($id) . '"' : '')
            . ' class="ratio-' . str_replace('/', '-', $ratio)
            . ' overflow-hidden bg-surface-container radius-2 m-bottom-1">' . $content . '</div>';
    }

    /** @param array<string,string> $attributes */
    private function renderCode(array $attributes, ?string $sourceRoot, ?string $sourceFile): string
    {
        $this->assertAttributes($attributes, ['src', 'lang', 'lines', 'title'], 'code');
        $src = trim($attributes['src'] ?? '');
        if ($src === '' || $sourceRoot === null || $sourceFile === null) {
            throw new PortableConfigurationException(
                'MARKDOWN_CODE_SOURCE_REQUIRED',
                'An external code block requires src and an authored page source context.',
            );
        }
        if (str_contains($src, "\0") || str_starts_with($src, '/') || preg_match('/\A[A-Za-z]:[\\\\\/]/D', $src) === 1) {
            throw new PortableConfigurationException('MARKDOWN_CODE_SOURCE_UNSAFE', 'An external code source path must be relative.');
        }
        $root = realpath($sourceRoot);
        $base = realpath(dirname($sourceFile));
        $path = $base === false ? false : realpath($base . DIRECTORY_SEPARATOR . $src);
        if ($root === false || $base === false || $path === false || is_link($path) || ! is_file($path)
            || ($path !== $root && ! str_starts_with($path, $root . DIRECTORY_SEPARATOR))) {
            throw new PortableConfigurationException('MARKDOWN_CODE_SOURCE_UNSAFE', "External code source [$src] is missing or outside the project root.");
        }
        $source = file_get_contents($path);
        if (! is_string($source) || strlen($source) > 1048576 || preg_match('//u', $source) !== 1) {
            throw new PortableConfigurationException('MARKDOWN_CODE_SOURCE_INVALID', "External code source [$src] is invalid or too large.");
        }
        $lineRange = trim($attributes['lines'] ?? '');
        if ($lineRange !== '') {
            if (preg_match('/\A(?<start>[1-9][0-9]*)(?:-(?<end>[1-9][0-9]*))?\z/D', $lineRange, $match) !== 1) {
                throw new PortableConfigurationException('MARKDOWN_CODE_LINES_INVALID', 'External code lines must use N or N-M.');
            }
            $lines = preg_split('/\r\n|\n|\r/u', $source) ?: [];
            $start = (int) $match['start'];
            $end = isset($match['end']) && $match['end'] !== '' ? (int) $match['end'] : $start;
            if ($end < $start || $start > count($lines)) {
                throw new PortableConfigurationException('MARKDOWN_CODE_LINES_INVALID', 'External code line range is outside the source file.');
            }
            $source = implode("\n", array_slice($lines, $start - 1, $end - $start + 1));
        }
        $lang = strtolower(trim($attributes['lang'] ?? pathinfo($path, PATHINFO_EXTENSION) ?: 'text'));
        if (preg_match('/\A[a-z0-9_+-]{1,32}\z/D', $lang) !== 1) {
            throw new PortableConfigurationException('MARKDOWN_CODE_LANGUAGE_INVALID', 'External code language is invalid.');
        }
        $title = trim($attributes['title'] ?? basename($path));
        $markdown = '```' . $lang . "\n" . rtrim($source) . "\n```\n";
        $rendered = $this->render($markdown);

        if ($title === '') {
            return $rendered;
        }

        return preg_replace_callback(
            '/<div\b(?<attributes>[^>]*)\bdata-docara-code-block\b/u',
            fn (array $match): string => '<div' . (string) ($match['attributes'] ?? '')
                . 'data-docara-code-block data-docara-code-title="' . $this->escapeHtml($title) . '"',
            $rendered,
            1,
        ) ?? $rendered;
    }

    /** @param array<string,string> $attributes */
    private function renderBacklinks(array $attributes): string
    {
        $this->assertAttributes($attributes, ['limit'], 'backlinks');
        $limit = (int) ($attributes['limit'] ?? '5');
        if ($limit < 1 || $limit > 50) {
            throw new PortableConfigurationException('MARKDOWN_BACKLINKS_LIMIT_INVALID', 'Backlinks limit must be between 1 and 50.');
        }

        return '<nav data-docara-block="backlinks" data-docara-backlinks data-docara-backlinks-limit="' . $limit
            . '" class="m-bottom-1" aria-label="Backlinks"></nav>';
    }

    /** @param array<string,string> $attributes */
    private function renderFigure(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['ratio', 'fit'], 'figure');
        $ratio = $this->attributeOneOf($attributes['ratio'] ?? 'auto', ['auto', '1/1', '4/3', '3/2', '16/9', '21/9'], 'figure', 'ratio');
        $fit = $this->attributeOneOf($attributes['fit'] ?? 'cover', ['cover', 'contain'], 'figure', 'fit');
        $content = trim((string) $rendered);
        if (preg_match('/^<p><img(?<image>[^>]*)\s*\/><\/p>(?:\s*<p>(?<caption>.*?)<\/p>)?$/su', $content, $match) !== 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_FIGURE_IMAGE_REQUIRED',
                'A figure block must contain one Markdown image and an optional caption paragraph.',
            );
        }
        $ratioClass = $ratio === 'auto' ? '' : ' ratio-' . str_replace('/', '-', $ratio);

        $imageClasses = 'w-full' . ($ratio === 'auto' ? '' : ' h-full') . ' object-' . $fit;

        return '<figure data-docara-block="figure" data-fit="' . $fit . '" class="m-0 m-bottom-1 flex flex-col gap-1"><div class="overflow-hidden radius-2'
            . $ratioClass . '"><img' . $match['image'] . ' class="' . $imageClasses . '" loading="lazy" decoding="async"></div>'
            . (isset($match['caption']) && trim($match['caption']) !== ''
                ? '<figcaption class="color-on-surface-variant">' . $match['caption'] . '</figcaption>'
                : '') . '</figure>';
    }

    /** @param array<string,string> $attributes */
    private function renderGrid(string $markdown, array $attributes): string
    {
        $this->assertAttributes($attributes, ['columns', 'gap'], 'grid');
        $columns = $this->attributeOneOf($attributes['columns'] ?? '3', ['1', '2', '3', '4'], 'grid', 'columns');
        $gap = $this->attributeOneOf($attributes['gap'] ?? '2', ['0', '1', '2', '3', '4'], 'grid', 'gap');
        $inspection = $this->inspectDirectives($markdown);
        $remainingLines = preg_split('/\r\n|\n|\r/u', $markdown);
        if (! is_array($remainingLines)) {
            throw new PortableConfigurationException('MARKDOWN_BLOCK_INPUT_INVALID', 'Grid body could not be split into lines.');
        }
        foreach ($inspection['directives'] as $directive) {
            for ($line = $directive['start_line']; $line <= $directive['end_line']; $line++) {
                $remainingLines[$line - 1] = '';
            }
        }
        if ($inspection['directives'] === []
            || array_values(array_unique(array_column($inspection['directives'], 'name'))) !== ['card']
            || trim(implode("\n", $remainingLines)) !== ''
        ) {
            throw new PortableConfigurationException(
                'MARKDOWN_GRID_CARD_REQUIRED',
                'A grid block may contain only one or more card blocks.',
            );
        }

        $html = $this->render($markdown);
        $cardRoot = '<section data-docara-block="card"';
        $count = substr_count($html, $cardRoot);
        if ($count < 1) {
            throw new PortableConfigurationException(
                'MARKDOWN_GRID_CARD_REQUIRED',
                'A grid block may contain only one or more card blocks.',
            );
        }

        $html = preg_replace_callback(
            '/<section data-docara-block="card"(?<attributes>[^>]*)>/u',
            static function (array $match): string {
                $attributes = str_replace(' m-bottom-1"', ' m-bottom-0"', (string) $match['attributes']);

                return '<section data-docara-block="card" data-docara-block-nested' . $attributes . '>';
            },
            $html,
        ) ?? $html;

        // Grid owns the spacing between composed cards. A figure keeps its
        // normal block margin elsewhere, but must not add a second gap inside
        // a card that already uses the Framework gap scale.
        $html = str_replace(
            'data-docara-block="figure" data-fit=',
            'data-docara-block="figure" data-docara-block-nested data-fit=',
            $html,
        );
        $html = preg_replace(
            '/(<figure\b[^>]*class="[^"]*)\bm-bottom-1\b/u',
            '$1m-bottom-0',
            $html,
        ) ?? $html;

        return '<section data-docara-block="grid" class="grid grid-col-1 md:grid-col-2 lg:grid-col-'
            . $columns . ' gap-' . $gap . ' m-bottom-1">' . $html . '</section>';
    }

    /** @param array<string,string> $attributes */
    private function renderMedia(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['side', 'ratio'], 'media');
        $side = $this->attributeOneOf($attributes['side'] ?? 'right', ['left', 'right'], 'media', 'side');
        $ratio = $this->attributeOneOf($attributes['ratio'] ?? '16/9', ['1/1', '4/3', '3/2', '16/9'], 'media', 'ratio');
        $content = trim((string) $rendered);
        $image = '';
        $content = preg_replace_callback(
            '/<p><img(?<attributes>[^>]*)\s*\/><\/p>/u',
            static function (array $match) use (&$image, $ratio): string {
                $image = '<div class="ratio-' . str_replace('/', '-', $ratio) . ' overflow-hidden radius-2">'
                    . '<img' . rtrim((string) $match['attributes']) . ' loading="lazy" decoding="async"></div>';

                return '';
            },
            $content,
            1,
        ) ?? $content;
        if ($image === '' || trim($content) === '') {
            throw new PortableConfigurationException(
                'MARKDOWN_MEDIA_CONTENT_REQUIRED',
                'A media block requires one image and visible Markdown content.',
            );
        }
        $text = '<div class="min-w-0 flex flex-col gap-1">' . $content . '</div>';

        return '<section data-docara-block="media" data-side="' . $side
            . '" class="grid grid-col-1 lg:grid-col-2 gap-3 items-center m-bottom-1">'
            . ($side === 'left' ? $image . $text : $text . $image) . '</section>';
    }

    /** @param array<string,string> $attributes */
    private function renderTree(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['interactive'], 'tree');
        $interactive = $this->attributeBoolean($attributes['interactive'] ?? 'true', 'tree', 'interactive');
        $root = $rendered->getDocument()->firstChild();
        if (! $root instanceof ListBlock
            || $root->getListData()->type !== ListBlock::TYPE_BULLET
            || $root->next() !== null
        ) {
            throw new PortableConfigurationException(
                'MARKDOWN_TREE_LIST_REQUIRED',
                'A tree block must contain one nested unordered Markdown list.',
            );
        }
        $content = trim((string) $rendered);
        $content = preg_replace('/<ul>/u', '<ul class="list-none m-0 p-inline-start-2 flex flex-col gap-1/3">', $content) ?? $content;
        $content = preg_replace('/<li>/u', '<li class="min-w-0">', $content) ?? $content;

        return '<div data-docara-block="tree" data-interactive="' . ($interactive ? 'true' : 'false')
            . '" class="bg-surface-0 border border-outline-variant radius-2 p-2 m-bottom-1">'
            . $content . '</div>';
    }

    /** @param array<string,string> $attributes */
    private function renderAlert(
        RenderedContentInterface $rendered,
        array $attributes,
        ?string $sourceFile,
    ): string {
        $this->assertAttributes($attributes, ['type', 'variant'], 'alert');
        $type = $this->attributeOneOf(
            $attributes['type'] ?? 'info',
            ['clear', 'info', 'success', 'warning', 'danger'],
            'alert',
            'type',
        );
        $variant = $this->attributeOneOf(
            $attributes['variant'] ?? 'default',
            ['default', 'flat', 'outlined'],
            'alert',
            'variant',
        );
        $nodes = iterator_to_array($rendered->getDocument()->children());
        $heading = $nodes[0] ?? null;
        if (! $heading instanceof Heading || ! in_array($heading->getLevel(), [2, 3, 4, 5], true)) {
            throw new PortableConfigurationException(
                'MARKDOWN_ALERT_HEADING_REQUIRED',
                'An alert block must start with a level-two through level-five heading.',
            );
        }
        $title = $this->inlineVisibleText($heading);
        if (! $this->containsVisibleText($title) || count($nodes) < 2) {
            throw new PortableConfigurationException(
                'MARKDOWN_ALERT_CONTENT_REQUIRED',
                'An alert block requires a visible title and supporting content.',
            );
        }
        $content = trim((string) $rendered);
        $content = preg_replace('/^<h[2-5][^>]*>.*?<\/h[2-5]>\s*/su', '', $content, 1) ?? $content;
        $location = new SourceLocation($sourceFile ?? '@markdown', 1, 1, 1);
        $alias = 'alert';

        return $this->components->renderComponentBlock(
            new ComponentBlockNode(
                $alias,
                (new ComponentAliasRegistry)->resolve($alias, $location),
                ['type' => $type, 'variant' => $variant],
                ':::alert',
                $title . "\n\n" . strip_tags($content),
                $location,
                [
                    new SourceNode('heading', $title, $location, ['level' => $heading->getLevel(), 'text' => $title]),
                    new SourceNode('paragraph', strip_tags($content), $location, ['text' => strip_tags($content)]),
                ],
            ),
            '<h' . $heading->getLevel() . '>' . $this->escapeHtml($title) . '</h' . $heading->getLevel() . '>' . $content,
        )->html;
    }

    private function renderTabs(RenderedContentInterface $rendered): string
    {
        $html = trim((string) $rendered);
        if (preg_match_all('/<h3[^>]*>(?<label>.*?)<\/h3>\s*(?<content>.*?)(?=<h3\b|\z)/su', $html, $matches, PREG_SET_ORDER) < 2) {
            throw new PortableConfigurationException(
                'MARKDOWN_TABS_ITEMS_REQUIRED',
                'A tabs block requires at least two level-three headings with content.',
            );
        }
        $id = 'docara-tabs-' . substr(hash('sha256', $html), 0, 12);
        $tabs = '';
        $panels = '';
        foreach ($matches as $index => $match) {
            $label = trim(strip_tags((string) $match['label']));
            $content = trim((string) $match['content']);
            if ($label === '' || $content === '') {
                throw new PortableConfigurationException('MARKDOWN_TABS_ITEM_INVALID', 'Every tab requires a label and visible content.');
            }
            $selected = $index === 0;
            $tabId = $id . '-tab-' . $index;
            $panelId = $id . '-panel-' . $index;
            $tabs .= '<button type="button" role="tab" id="' . $tabId . '" aria-controls="' . $panelId
                . '" aria-selected="' . ($selected ? 'true' : 'false') . '"' . ($selected ? '' : ' tabindex="-1"')
                . ' class="sf-tab p-inline-1 p-block-1/3 color-on-surface-variant">' . $this->escapeHtml($label) . '</button>';
            $panels .= '<div role="tabpanel" id="' . $panelId . '" aria-labelledby="' . $tabId . '"'
                . ($selected ? '' : ' hidden') . ' class="p-2">' . $content . '</div>';
        }

        return '<section data-docara-block="tabs" data-docara-tabs class="border border-outline-variant radius-2 overflow-hidden m-bottom-1">'
            . '<div role="tablist" class="flex border-bottom border-outline-variant bg-surface-0">' . $tabs . '</div>'
            . $panels . '</section>';
    }

    /** @param array<string,string> $attributes */
    private function renderBanner(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['type'], 'banner');
        $type = $this->attributeOneOf($attributes['type'] ?? 'info', ['info', 'success', 'warning', 'danger'], 'banner', 'type');
        $content = trim((string) $rendered);
        if ($content === '') {
            throw new PortableConfigurationException('MARKDOWN_BANNER_CONTENT_REQUIRED', 'A banner requires visible Markdown content.');
        }
        $icon = match ($type) {
            'success' => 'check_circle', 'warning' => 'warning', 'danger' => 'error', default => 'info',
        };

        return '<aside data-docara-block="banner" role="status" class="sf-alert sf-alert--' . $type
            . ' sf-alert--flat flex items-start m-bottom-1"><sf-icon icon="' . $icon . '" aria-hidden="true"></sf-icon>'
            . '<div class="sf-alert-wrap flex-1"><div class="sf-alert-content">' . $content . '</div></div></aside>';
    }

    /** @param array<string,string> $attributes */
    private function renderDiagram(string $markdown, array $attributes): string
    {
        $this->assertAttributes($attributes, ['engine', 'title'], 'diagram');
        $engine = $this->attributeOneOf($attributes['engine'] ?? 'mermaid', ['mermaid'], 'diagram', 'engine');
        $title = trim($attributes['title'] ?? 'Diagram');
        $source = trim($markdown);
        if ($source === '') {
            throw new PortableConfigurationException('MARKDOWN_DIAGRAM_SOURCE_REQUIRED', 'A diagram requires a declarative source.');
        }

        return '<figure data-docara-block="diagram" data-engine="' . $engine . '" class="border border-outline-variant radius-2 overflow-hidden m-0 m-bottom-1">'
            . '<figcaption class="bg-surface-container p-inline-2 p-block-1/3 font-bold">' . $this->escapeHtml($title) . '</figcaption>'
            . '<div class="mermaid p-2 overflow-auto" data-docara-diagram-source role="img" aria-label="'
            . $this->escapeHtml($title) . '">'
            . $this->escapeHtml($source) . '</div></figure>';
    }

    /** @param array<string,string> $attributes */
    private function renderMath(string $markdown, array $attributes): string
    {
        $this->assertAttributes($attributes, ['display', 'label'], 'math');
        $display = $this->attributeOneOf($attributes['display'] ?? 'block', ['inline', 'block'], 'math', 'display');
        $label = trim($attributes['label'] ?? 'Mathematical formula');
        $source = trim($markdown);
        if ($source === '') {
            throw new PortableConfigurationException('MARKDOWN_MATH_SOURCE_REQUIRED', 'A math block requires TeX source.');
        }
        $tag = $display === 'inline' ? 'span' : 'div';

        return '<' . $tag . ' data-docara-block="math" data-display="' . $display . '" data-docara-math-source role="math" aria-label="'
            . $this->escapeHtml($label) . '" class="font-mono ' . ($display === 'block' ? 'p-2 bg-surface-container radius-2 m-bottom-1 overflow-auto' : '')
            . '">' . $this->escapeHtml($source) . '</' . $tag . '>';
    }

    private function renderHtml(string $markdown): string
    {
        $source = trim($markdown);
        if (preg_match('/^```html\h*\n(?<source>.*?)\n```$/su', $source, $match) !== 1) {
            throw new PortableConfigurationException('MARKDOWN_HTML_FENCE_REQUIRED', 'An html block requires exactly one fenced HTML source.');
        }
        $document = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body>'
            . $match['source'] . '</body></html>';

        return '<iframe data-docara-block="html" title="HTML example" class="w-full min-h-i5 border border-outline-variant radius-2 bg-surface-0 m-bottom-1" sandbox srcdoc="'
            . $this->escapeHtml($document) . '"></iframe>';
    }

    /** @param array<string,string> $attributes @param list<string> $allowed */
    private function assertAttributes(array $attributes, array $allowed, string $component): void
    {
        $unknown = array_values(array_diff(array_keys($attributes), $allowed));
        if ($unknown !== []) {
            throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_UNKNOWN',
                "Markdown component [$component] does not support attribute [{$unknown[0]}].",
            );
        }
    }

    /** @param list<string> $allowed */
    private function attributeOneOf(string $value, array $allowed, string $component, string $attribute): string
    {
        if (! in_array($value, $allowed, true)) {
            throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
                "Markdown component [$component] has invalid [$attribute] value [$value].",
            );
        }

        return $value;
    }

    private function attributeBoolean(string $value, string $component, string $attribute): bool
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => throw new PortableConfigurationException(
                'MARKDOWN_COMPONENT_ATTRIBUTE_VALUE_INVALID',
                "Markdown component [$component] has invalid [$attribute] value [$value].",
            ),
        };
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /** @param array<string,string> $attributes */
    private function renderCard(string $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['variant'], 'card');
        $variant = $this->attributeOneOf(
            $attributes['variant'] ?? 'default',
            ['default', 'plain'],
            'card',
            'variant',
        );

        return $this->cardOpening($variant)
            . (string) $rendered . '</section>';
    }

    private function cardOpening(string $variant): string
    {
        $classes = $variant === 'plain'
            ? 'flex flex-col gap-1 m-bottom-1'
            : 'bg-surface-0 border border-outline-variant radius-2 p-3 flex flex-col gap-1 m-bottom-1';

        return '<section data-docara-block="card" data-docara-card-variant="' . $variant
            . '" class="' . $classes . '">';
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

        return '<a data-docara-block="cta" class="docara-cta-link sf-button sf-button--default sf-button--primary sf-button--size-1 radius-default inline-flex items-center content-main-center decoration-none w-full sm:w-auto sm:self-start m-bottom-1"'
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
            '<ul data-docara-block="features" class="' . $gridClasses . ' list-none m-0 m-bottom-1 p-0">',
            $content,
            1,
        ) ?? $content;
        $content = preg_replace(
            '/<img(?<attributes>[^>]*)\s*\/?>/u',
            '<img data-docara-media="feature-icon" loading="lazy" decoding="async"$1>',
            $content,
        ) ?? $content;

        return preg_replace(
            '/<li>/',
            '<li class="bg-surface-0 border border-outline-variant radius-2 p-3 flex min-w-0 max-w-none flex-col gap-1">',
            $content,
        ) ?? $content;
    }

    /** @param array<string,string> $attributes */
    private function renderHero(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['variant'], 'hero');
        $variant = $this->attributeOneOf($attributes['variant'] ?? 'split', ['split', 'centered', 'compact'], 'hero', 'variant');
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
                static function (array $match) use (&$image): string {
                    $attributes = rtrim((string) $match['attributes']);
                    $decorative = preg_match('/\balt=""/u', $attributes) === 1
                        ? ' aria-hidden="true"'
                        : '';
                    $image = '<div class="min-w-0 flex items-center content-main-center">'
                        . '<img data-docara-media="hero" loading="eager" fetchpriority="high" decoding="async"'
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

        $columns = $variant === 'split' && $image !== '' ? 'grid-col-1 lg:grid-col-2' : 'grid-col-1';
        $alignment = $variant === 'centered' ? ' text-center items-center' : '';
        $spacing = $variant === 'compact' ? ' p-3' : ' p-4';

        return '<section data-docara-block="hero" data-variant="' . $variant
            . '" data-docara-width="full" class="bg-surface-container overflow-hidden m-bottom-1">'
            . '<div data-docara-container class="container m-inline-auto grid ' . $columns . ' gap-4 items-center' . $spacing . '">'
            . '<div class="min-w-0 flex flex-col gap-2' . $alignment . '">' . $content . '</div>'
            . $image . '</div></section>';
    }

    /** @param array<string,string> $attributes */
    private function renderLogos(RenderedContentInterface $rendered, array $attributes): string
    {
        $this->assertAttributes($attributes, ['tone'], 'logos');
        $tone = $this->attributeOneOf($attributes['tone'] ?? 'normal', ['normal', 'muted'], 'logos', 'tone');
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
            '<ul data-docara-block="logos" data-tone="' . $tone
                . '" class="grid grid-col-2 md:grid-col-3 lg:grid-col-6 gap-2 list-none m-0 m-bottom-1 p-0">',
            $content,
            1,
        ) ?? $content;
        $content = preg_replace(
            '/<img(?<attributes>[^>]*)\s*\/?>/u',
            '<img data-docara-media="logo" loading="lazy" decoding="async"$1>',
            $content,
        ) ?? $content;

        return preg_replace(
            '/<li>/u',
            '<li class="min-w-0 flex items-center content-main-center' . ($tone === 'muted' ? ' color-on-surface-variant' : '') . '">',
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
                static function (array $match) use (&$image, $block): string {
                    $attributes = rtrim((string) $match['attributes']);
                    $decorative = preg_match('/\balt=""/u', $attributes) === 1
                        ? ' aria-hidden="true"'
                        : '';
                    $image = '<div class="min-w-0 flex items-center content-main-center">'
                        . '<img data-docara-media="' . $block . '" loading="lazy" decoding="async"'
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
            . $surfaceClass . ' overflow-hidden m-bottom-1"><div data-docara-container class="container m-inline-auto grid '
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
                '<img data-docara-media="card" loading="lazy" decoding="async"$1>',
                $html,
            ) ?? $html;
            $content[] = '<div class="min-w-0">' . $html . '</div>';
        }

        return '<section data-docara-block="columns" data-docara-columns="' . $count
            . '" class="' . $classes . ' m-bottom-1">' . implode('', $content) . '</section>';
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
        $html = preg_replace_callback(
            '/<blockquote>\s*<p>(?<content>.*?)\s*\{(?<config>(?=[^{}]*(?:author|source)\s*=)[^{}]+)\}<\/p>\s*<\/blockquote>/su',
            function (array $matches): string {
                $config = str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], (string) $matches['config']);
                $attributes = $this->attributes->parse($config, 'quote');
                $this->assertAttributes($attributes, ['author', 'source', 'url'], 'quote');
                $author = trim($attributes['author'] ?? '');
                $source = trim($attributes['source'] ?? '');
                $url = trim($attributes['url'] ?? '');
                if ($author === '' && $source === '') {
                    throw new PortableConfigurationException(
                        'MARKDOWN_QUOTE_ATTRIBUTION_REQUIRED',
                        'An attributed quote requires an author or source.',
                    );
                }
                if ($url !== '') {
                    $this->assertSafeUrl($url, 'MARKDOWN_LINK_URL_UNSAFE');
                }
                $citation = array_values(array_filter([$author, $source], static fn (string $value): bool => $value !== ''));
                $citationHtml = $this->escapeHtml(implode(', ', $citation));
                if ($url !== '') {
                    $citationHtml = '<a href="' . $this->escapeHtml($url)
                        . '" rel="noopener noreferrer">' . $citationHtml . '</a>';
                }

                return '<figure data-docara-native-quote class="m-0 m-bottom-1">'
                    . '<blockquote>' . trim((string) $matches['content'])
                    . '<footer class="color-on-surface-variant p-block-start-1">— <cite>'
                    . $citationHtml . '</cite></footer></blockquote></figure>';
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<p><img(?<image>[^>]*)\s*\/>\{(?<config>[^{}]+)\}<\/p>/su',
            function (array $matches): string {
                $config = str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], (string) $matches['config']);
                $attributes = $this->attributes->parse($config, 'image');
                $this->assertAttributes($attributes, ['ratio', 'fit'], 'image');
                $ratio = str_replace('x', '/', $attributes['ratio'] ?? 'auto');
                $ratio = $this->attributeOneOf($ratio, ['auto', '1/1', '4/3', '3/2', '16/9', '21/9'], 'image', 'ratio');
                $fit = $this->attributeOneOf($attributes['fit'] ?? 'cover', ['cover', 'contain'], 'image', 'fit');
                $ratioClass = $ratio === 'auto' ? '' : ' ratio-' . str_replace('/', '-', $ratio);
                $imageClasses = 'w-full' . ($ratio === 'auto' ? '' : ' h-full') . ' object-' . $fit;

                return '<figure data-docara-native-image data-fit="' . $fit
                    . '" class="m-0 m-bottom-1 overflow-hidden radius-2' . $ratioClass . '">'
                    . '<img' . rtrim((string) $matches['image']) . ' class="' . $imageClasses
                    . '" loading="lazy" decoding="async"></figure>';
            },
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/<table>(?<content>.*?)<\/table>/su',
            static fn (array $matches): string => '<div data-docara-table-scroll class="overflow-auto m-bottom-1">'
                . '<table class="table table-border table-stripe">'
                . (string) $matches['content'] . '</table></div>',
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/<pre><code(?P<attributes>[^>]*)>(?P<content>.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $attributes = (string) ($matches['attributes'] ?? '');

                return '<div data-docara-code-block class="source init docara-code-block min-w-0 overflow-hidden bg-surface-container border border-outline-variant radius-2 m-bottom-1">'
                    . '<pre class="docara-code-scroll overflow-auto m-0 p-2"><code'
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
