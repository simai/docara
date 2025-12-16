<?php

    namespace Simai\Docara\CustomTags;

    use League\CommonMark\Node\Block\AbstractBlock;
    use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
    use League\CommonMark\Parser\Block\BlockContinue;
    use League\CommonMark\Parser\Block\BlockContinueParserInterface;
    use League\CommonMark\Parser\Block\BlockStart;
    use League\CommonMark\Parser\Block\BlockStartParserInterface;
    use League\CommonMark\Parser\Cursor;
    use League\CommonMark\Parser\MarkdownParserStateInterface;

    final class UniversalBlockParser implements BlockStartParserInterface
    {
        public function __construct(private CustomTagRegistry $registry) {}

        public function tryStart(Cursor $cursor, MarkdownParserStateInterface $state): ?BlockStart
        {
            $line = $cursor->getLine();

            foreach ($this->registry->getSpecs() as $spec) {
                $compactMatch = [];
                $compactPattern = '/^\s*!' . preg_quote($spec->type, '/') . '\s*\[\s*(?<inner>[^\]]*?)\s*\]\s*(?:\{\s*(?<attrs>[^\}]+)\s*\})?\s*$/u';
                $isCompact = preg_match($compactPattern, $line, $compactMatch);

                if (! $isCompact && ! preg_match($spec->openRegex, $line, $m)) {
                    continue;
                }

                // If the same line also contains the close marker (inline syntax), let the inline parser handle it.
                if (! $isCompact
                    && $spec->closeRegex !== null
                    && preg_match('/!end' . preg_quote($spec->type, '/') . '\b/u', $line, $mm, PREG_OFFSET_CAPTURE)
                    && $mm[0][1] > 0
                ) {
                    return BlockStart::none();
                }

                $active = $state->getActiveBlockParser();
                if (method_exists($active, 'getBlock')) {
                    $blk = $active->getBlock();
                    if ($blk instanceof CustomTagNode
                        && $blk->getType() === $spec->type
                        && ! $spec->allowNestingSame
                    ) {
                        return BlockStart::none();
                    }
                }

                $cursor->advanceToEnd();

                $attrStr = $isCompact ? ($compactMatch['attrs'] ?? '') : ($m['attrs'] ?? '');
                $userAttrs = $isCompact
                    ? Attrs::parseOpenLine($compactMatch['attrs'] ?? '')
                    : Attrs::parseOpenLine($attrStr);
                $attrs = Attrs::merge($spec->baseAttrs, $userAttrs);
                $node = new CustomTagNode($spec->type, $attrs, [
                    'openMatch' => $isCompact ? $compactMatch : $m,
                    'attrStr' => $attrStr,
                    'compact' => (bool) $isCompact,
                    'innerRaw' => $isCompact ? trim($compactMatch['inner'] ?? '') : null,
                ], $isCompact ? false : ($spec->isContainer ?? true));

                if ($spec->attrsFilter instanceof \Closure) {
                    $node->setAttrs(($spec->attrsFilter)($node->getAttrs(), $node->getMeta()));
                }
                // Allow overriding wrapper via attribute "tag" (and drop it from rendered attrs).
                $attrs = $node->getAttrs();
                if (isset($attrs['tag']) && $attrs['tag'] !== '') {
                    $node->setHtmlTag($attrs['tag']);
                    unset($attrs['tag']);
                    $node->setAttrs($attrs);
                }

                return BlockStart::of(new class($spec, $node) extends AbstractBlockContinueParser
                {
                    public array $buffer = [];
                    public function __construct(
                        private CustomTagSpec $spec,
                        private CustomTagNode $node
                    ) {

                    }

                    public function getBlock(): AbstractBlock
                    {
                        return $this->node;
                    }

                    public function isContainer(): bool
                    {
                        return $this->node->isContainer();
                    }

                    public function canHaveLazyContinuationLines(): bool
                    {
                        return false;
                    }

                    public function canContain(AbstractBlock $childBLock): bool
                    {
                        return true;
                    }

                    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $active): ?BlockContinue
                    {
                        // Compact form: single line, finish immediately.
                        if ($this->node->getMeta()['compact'] ?? false) {
                            return BlockContinue::finished();
                        }

                        $line = $cursor->getLine();

                        if ($this->spec->closeRegex === null) {
                            return BlockContinue::finished();
                        }

                        if (preg_match($this->spec->closeRegex, $line)) {
                            $cursor->advanceToEnd();

                            return BlockContinue::finished();
                        }

                        return BlockContinue::at($cursor);
                    }

                    public function addLine(string $line): void {
                        if(!$this->node->isContainer()) {
                            $this->buffer[] = $line;
                        }
                    }

                    public function closeBlock(): void {
                        if(!$this->node->isContainer()) {
                            $this->node->setMeta(array_merge($this->node->getMeta(), [
                                'raw' => implode("\n", $this->buffer),
                            ]));
                            // If we already know the inner text (compact form), push it as raw if buffer is empty.
                            if (($this->node->getMeta()['compact'] ?? false) && empty($this->buffer)) {
                                $raw = $this->node->getMeta()['innerRaw'] ?? '';
                                $this->node->setMeta(array_merge($this->node->getMeta(), [
                                    'raw' => $raw,
                                ]));
                            }
                        }
                    }
                })->at($cursor);
            }

            return BlockStart::none();
        }
    }
