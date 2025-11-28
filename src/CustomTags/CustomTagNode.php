<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Node\Block\AbstractBlock;

final class CustomTagNode extends AbstractBlock
{
    public function __construct(
        private string $type,
        private array $attrs = [],
        private array $meta = [],
    ) {
        parent::__construct();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isContainer(): bool
    {
        return true;
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

    public function getMeta(): array
    {
        return $this->meta;
    }
}
