<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Node\Inline\AbstractInline;

final class CustomTagInline extends AbstractInline
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

    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function setAttrs(array $attrs): void
    {
        $this->attrs = $attrs;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
