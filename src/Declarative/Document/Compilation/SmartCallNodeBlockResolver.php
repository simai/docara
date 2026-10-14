<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document\Compilation;

use Simai\Docara\Declarative\Document\DocumentNode;
use Simai\Docara\Declarative\Document\SmartCallNode;
use Simai\Docara\Declarative\Plan\ResolvedBlockFactory;
use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class SmartCallNodeBlockResolver implements DocumentNodeBlockResolver
{
    public function __construct(
        private ResolvedBlockFactory $blocks,
        private SmartComponentGateway $smarts,
    ) {}

    public function type(): string
    {
        return 'smart';
    }

    /** @param array<string, mixed> $section */
    public function resolve(DocumentNode $node, array $section): ResolvedBlockPlan
    {
        if (! $node instanceof SmartCallNode) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DOCUMENT_NODE_CONTRACT_INVALID',
                'Document node [smart] does not satisfy its resolver contract.',
            );
        }

        return $this->blocks->create(
            $node->id(),
            'content.smart',
            'content',
            ['source' => $node->span()->toArray()],
            $this->smarts->resolve($node),
            $section,
        );
    }
}
