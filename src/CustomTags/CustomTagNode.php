<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Block\Document;

final class CustomTagNode extends AbstractBlock
{

    public function __construct(
        private string $type,
        private array $attrs = [],
        private array $meta = [],
        private bool $isContainer = true
    ) {
        parent::__construct();
    }

    public function getDocument(): ?Document
    {
        $cur = $this;
        while ($cur->parent() !== null) {
            $cur = $cur->parent();
        }

        return $cur instanceof Document ? $cur : null;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isContainer(): bool
    {
        return $this->isContainer;
    }

    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function setAttrs(array $attrs): void
    {
        $this->attrs = $attrs;
    }

    public function addClass(string $class): void
    {
        $cur = $this->attrs['class'] ?? '';
        $list = array_filter(array_unique(array_merge(
            $cur ? preg_split('/\s+/', $cur) : [],
            preg_split('/\s+/', trim($class))
        )));
        if ($list) {
            $this->attrs['class'] = implode(' ', $list);
        }
    }
    public function setMeta($meta): array
    {
        return $this->meta = $meta;
    }
    public function getMeta(): array
    {
        return $this->meta;
    }
}
