<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document\Compilation;

use Simai\Docara\Declarative\Document\DocumentNode;
use Simai\Docara\Declarative\Plan\ResolvedBlockFactory;
use Simai\Docara\Declarative\Plan\ResolvedBlockPlan;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class DocumentNodeBlockRegistry
{
    /** @var array<string, DocumentNodeBlockResolver> */
    private array $resolvers;

    /** @param iterable<DocumentNodeBlockResolver> $resolvers */
    public function __construct(iterable $resolvers)
    {
        $indexed = [];
        foreach ($resolvers as $resolver) {
            $type = $resolver->type();
            if ($type === '' || isset($indexed[$type])) {
                throw new \LogicException('DECLARATIVE_DOCUMENT_NODE_RESOLVER_DUPLICATED:' . $type);
            }
            $indexed[$type] = $resolver;
        }
        if ($indexed === []) {
            throw new \LogicException('DECLARATIVE_DOCUMENT_NODE_RESOLVERS_REQUIRED');
        }
        $this->resolvers = $indexed;
    }

    public static function bundled(
        ResolvedBlockFactory $blocks,
        SmartComponentGateway $smarts,
    ): self {
        return new self([
            new MarkdownNodeBlockResolver($blocks),
            new SmartCallNodeBlockResolver($blocks, $smarts),
        ]);
    }

    /** @param array<string, mixed> $section */
    public function resolve(DocumentNode $node, array $section): ResolvedBlockPlan
    {
        $resolver = $this->resolvers[$node->type()] ?? null;
        if ($resolver === null) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DOCUMENT_NODE_UNSUPPORTED',
                "Document node type [{$node->type()}] has no registered resolver.",
            );
        }

        return $resolver->resolve($node, $section);
    }

    /** @return list<string> */
    public function types(): array
    {
        return array_keys($this->resolvers);
    }
}
