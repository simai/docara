<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Keeps the diagram gallery honest: it shows each diagram type of the
 * maintained list once, the list matches the detectors of the bundled
 * Mermaid, and the label rules the gallery documents hold in its sources.
 * scripts/verify-mermaid-gallery.mjs renders the same sources in a browser.
 */
final class MermaidDiagramGalleryTest extends TestCase
{
    /**
     * Diagram types the gallery shows, keyed by the keyword a source starts
     * with, each with the detector text the bundled Mermaid must contain.
     */
    private const GALLERY_TYPES = [
        'flowchart' => '^\s*flowchart/',
        'sequenceDiagram' => '^\s*sequenceDiagram/',
        'stateDiagram-v2' => '^\s*stateDiagram/',
        'journey' => '^\s*journey/',
        'classDiagram' => '^\s*classDiagram/',
        'erDiagram' => '^\s*erDiagram/',
        'requirementDiagram' => '^\s*requirement(Diagram)?/',
        'mindmap' => '^\s*mindmap/',
        'treemap-beta' => '^\s*treemap/',
        'block-beta' => '^\s*block(-beta)?/',
        'packet-beta' => '^\s*packet(-beta)?/',
        'C4Context' => '^\s*C4Context|',
        'architecture-beta' => '^\s*architecture/',
        'gantt' => '^\s*gantt/',
        'timeline' => '^\s*timeline/',
        'gitGraph' => '^\s*gitGraph/',
        'kanban' => '^\s*kanban/',
        'pie' => '^\s*pie/',
        'xychart-beta' => '^\s*xychart(-beta)?/',
        'quadrantChart' => '^\s*quadrantChart/',
        'sankey-beta' => '^\s*sankey(-beta)?/',
        'radar-beta' => '^\s*radar-beta/',
    ];

    /**
     * Detectors in the bundle that the gallery deliberately leaves out:
     * aliases and layouts of shown types, Mermaid's own info diagram, and
     * newer experimental types not checked in the browser yet. A Mermaid
     * upgrade that adds a detector fails until it is shown or listed here.
     */
    private const NOT_SHOWN = [
        'graph', '(flowchart|graph)', 'flowchart-elk', 'info',
        'eventmodeling', 'railroad-beta', 'railroad-abnf-beta', 'railroad-ebnf-beta',
        'railroad-peg-beta', 'treeView-beta', 'venn-beta', 'wardley-beta',
    ];

    #[Test]
    public function gallery_shows_every_listed_type_exactly_once(): void
    {
        $types = array_map(
            static fn (array $diagram): string => $diagram['type'],
            $this->diagrams($this->root() . '/docs/site/content/ru/components/diagram-gallery.md'),
        );

        self::assertSame(array_keys(self::GALLERY_TYPES), $types);
    }

    #[Test]
    public function diagram_page_examples_use_listed_types(): void
    {
        $diagrams = $this->diagrams($this->root() . '/docs/site/content/ru/components/diagram.md');

        self::assertNotEmpty($diagrams);
        foreach ($diagrams as $diagram) {
            self::assertArrayHasKey($diagram['type'], self::GALLERY_TYPES, $diagram['title']);
        }
    }

    #[Test]
    public function listed_types_match_the_bundled_mermaid_detectors(): void
    {
        $bundle = (string) file_get_contents($this->root() . '/resources/portable/vendor/mermaid/mermaid.min.js');
        self::assertMatchesRegularExpression('/version:"11\.16\.0"/', $bundle);
        foreach (self::GALLERY_TYPES as $type => $detector) {
            self::assertStringContainsString($detector, $bundle, "Bundled Mermaid has no detector for $type.");
        }

        preg_match_all('#\^\\\\s\*([A-Za-z(|)?-]+)/#', $bundle, $matches);
        $detected = array_values(array_unique($matches[1]));
        $known = [...self::NOT_SHOWN];
        foreach (self::GALLERY_TYPES as $detector) {
            if (preg_match('#\^\\\\s\*([A-Za-z(|)?-]+)/#', $detector, $match) === 1) {
                $known[] = $match[1];
            }
        }
        sort($detected);
        self::assertSame([], array_values(array_diff($detected, $known)), 'Bundled Mermaid detects types the gallery neither shows nor lists as not shown.');
        self::assertStringNotContainsString('zenuml', strtolower(implode(' ', $detected)));
    }

    #[Test]
    public function gallery_sources_follow_the_documented_label_rules(): void
    {
        foreach ($this->diagrams($this->root() . '/docs/site/content/ru/components/diagram-gallery.md') as $diagram) {
            $source = $diagram['source'];
            if ($diagram['type'] === 'sankey-beta') {
                self::assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', $source, 'sankey-beta does not render Cyrillic labels.');
            }
            if (in_array($diagram['type'], ['architecture-beta', 'xychart-beta'], true)) {
                self::assertDoesNotMatchRegularExpression(
                    '/\p{Cyrillic}/u',
                    (string) preg_replace('/"[^"\n]*"/u', '""', $source),
                    $diagram['type'] . ' needs Cyrillic labels in double quotes.',
                );
            }
            self::assertStringNotContainsString('click ', $source, 'Mermaid runs in strict mode; click handlers do nothing.');
        }
    }

    /** @return list<array{type:string,title:string,source:string}> */
    private function diagrams(string $page): array
    {
        $markdown = (string) file_get_contents($page);
        preg_match_all('/^:::diagram \{engine=mermaid title="([^"]+)"\}\n(.*?)\n:::$/msu', $markdown, $matches, PREG_SET_ORDER);
        $diagrams = [];
        foreach ($matches as $match) {
            $source = trim($match[2]);
            $diagrams[] = [
                'type' => (string) preg_split('/\s+/', $source, 2)[0],
                'title' => $match[1],
                'source' => $source,
            ];
        }

        return $diagrams;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
