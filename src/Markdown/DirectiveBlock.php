<?php

declare(strict_types=1);

namespace Simai\Docara\Markdown;

use League\CommonMark\Node\Block\AbstractBlock;

final class DirectiveBlock extends AbstractBlock
{
    private string $body = '';

    private bool $closed = false;

    public function __construct(
        private readonly string $name,
        private readonly int $fenceLength,
    ) {
        parent::__construct();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function fenceLength(): int
    {
        return $this->fenceLength;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function markClosed(): void
    {
        $this->closed = true;
    }
}
