<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document\Compilation;

use Simai\Docara\Declarative\Document\DocumentNode;
use Simai\Docara\Declarative\Document\MarkdownNode;
use Simai\Docara\Declarative\Plan\ResolvedBlockFactory;
use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class MarkdownNodeBlockResolver implements DocumentNodeBlockResolver
{
    public function __construct(private ResolvedBlockFactory $blocks) {}

    public function type(): string
    {
        return 'markdown';
    }

    /** @param array<string, mixed> $section */
    public function resolve(DocumentNode $node, array $section): ResolvedBlockPlan
    {
        if (! $node instanceof MarkdownNode) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DOCUMENT_NODE_CONTRACT_INVALID',
                'Document node [markdown] does not satisfy its resolver contract.',
            );
        }

        return $this->blocks->create(
            $node->id(),
            'content.markdown',
            'content',
            ['markdown' => $node->markdown, 'source' => $node->span()->toArray()],
            null,
            $section,
        );
    }
}
