<?php

namespace Simai\Docara\CustomTags;

use League\CommonMark\Node\Inline\AbstractInline;

final class CustomTagInline extends AbstractInline
{
    public function __construct(
        private string $type,
        private array $attrs = [],
        private array $meta = [],
        private ?string $htmlTag = null,
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

    public function getHtmlTag(): ?string
    {
        return $this->htmlTag;
    }

    public function setHtmlTag(?string $htmlTag): void
    {
        $this->htmlTag = $htmlTag ? trim($htmlTag) : null;
    }
}
