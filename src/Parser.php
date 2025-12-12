<?php

    namespace Simai\Docara;

    use League\CommonMark\Environment\Environment;
    use League\CommonMark\Event\DocumentParsedEvent;
    use League\CommonMark\Exception\CommonMarkException;
    use League\CommonMark\Extension\Attributes\AttributesExtension;
    use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
    use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
    use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
    use League\CommonMark\Extension\Table\Table;
    use League\CommonMark\Extension\Table\TableCell;
    use League\CommonMark\Extension\Table\TableRow;
    use League\CommonMark\Extension\Table\TableSection;
    use League\CommonMark\MarkdownConverter;
    use Mni\FrontYAML\Parser as FrontYamlParser;
    use Simai\Docara\CustomTags\CustomTagRegistry;
    use Simai\Docara\CustomTags\CustomTagsExtension;
    use Simai\Docara\Parsers\FrontMatterParser as BaseFrontMatterParser;

    class Parser extends BaseFrontMatterParser
    {
        private MarkdownConverter $md;

        private function extractClassFromCell(TableCell $cell): ?string
        {
            $attrs = $cell->data->get('attributes') ?? [];
            if (! empty($attrs['class'])) {
                return trim($attrs['class']);
            }

            $first = $cell->firstChild();
            if ($first) {
                $attrs = $first->data->get('attributes') ?? [];
                if (! empty($attrs['class'])) {
                    return trim($attrs['class']);
                }
            }

            for ($n = $cell->firstChild(); $n; $n = $n->next()) {
                $walker = $n->walker();
                while ($e = $walker->next()) {
                    if (! $e->isEntering()) {
                        continue;
                    }
                    $node = $e->getNode();
                    $attrs = $node->data->get('attributes') ?? [];
                    if (! empty($attrs['class'])) {
                        return trim($attrs['class']);
                    }
                }
            }

            return null;
        }

        public function __construct(FrontYamlParser $frontYaml, CustomTagRegistry $registry)
        {
            parent::__construct($frontYaml);
            $config = [
                'html_input' => 'allow',
                'allow_unsafe_links' => true,
                'disallowed_raw_html' => [
                    'disallowed_tags' => [
                        'title',
                        'textarea',
                        'style',
                        'xmp',
                        'noembed',
                        'noframes',
                        'script',
                        'plaintext',
                    ],
                ],
            ];
            $env = new Environment($config);
            $env->addExtension(new CustomTagsExtension($registry));
            $env->addExtension(new CommonMarkCoreExtension);
            $env->addExtension(new FrontMatterExtension);
            $env->addExtension(new AttributesExtension);
            $env->addExtension(new GithubFlavoredMarkdownExtension);

            $env->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event) {
                $doc = $event->getDocument();
                $walker = $doc->walker();

                while ($e = $walker->next()) {
                    if (! $e->isEntering()) {
                        continue;
                    }
                    $table = $e->getNode();
                    if (! ($table instanceof Table)) {
                        continue;
                    }

                    $thead = null;
                    for ($sec = $table->firstChild(); $sec; $sec = $sec->next()) {
                        if ($sec instanceof TableSection && $sec->getType() === TableSection::TYPE_HEAD) {
                            $thead = $sec;
                            break;
                        }
                    }
                    if (! $thead) {
                        continue;
                    }

                    $headerRow = $thead->firstChild() instanceof TableRow ? $thead->firstChild() : null;
                    if (! $headerRow) {
                        continue;
                    }

                    $colClasses = [];
                    $i = 0;
                    for ($cell = $headerRow->firstChild(); $cell; $cell = $cell->next()) {
                        if (! ($cell instanceof TableCell)) {
                            continue;
                        }
                        $i++;
                        $cls = $this->extractClassFromCell($cell);
                        if ($cls) {
                            $colClasses[$i] = $cls;
                        }
                    }
                    if (! $colClasses) {
                        continue;

                    }

                    for ($sec = $table->firstChild(); $sec; $sec = $sec->next()) {
                        if (! ($sec instanceof TableSection)) {
                            continue;
                        }
                        for ($tr = $sec->firstChild(); $tr; $tr = $tr->next()) {
                            if (! ($tr instanceof TableRow)) {
                                continue;
                            }
                            $col = 0;
                            for ($td = $tr->firstChild(); $td; $td = $td->next()) {
                                if (! ($td instanceof TableCell)) {
                                    continue;
                                }
                                $col++;
                                if (! isset($colClasses[$col])) {
                                    continue;
                                }
                                $attrs = $td->data->get('attributes') ?? [];
                                $attrs['class'] = trim(($attrs['class'] ?? '') . ' ' . $colClasses[$col]);
                                $td->data->set('attributes', $attrs);
                            }
                        }
                    }
                }
            }, -100);
            $this->md = new MarkdownConverter($env);
        }

        /**
         * @throws CommonMarkException
         */
        public function parseMarkdownWithoutFrontMatter($content): string
        {
            return (string) $this->md->convert($content);
        }
    }
