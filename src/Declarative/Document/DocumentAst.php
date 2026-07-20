<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Document;

use Simai\Docara\Portable\CanonicalJson;

final readonly class DocumentAst
{
    /**
     * @param  list<DocumentNode>  $nodes
     * @param  list<DocumentHeading>  $headings
     * @param  list<DocumentLink>  $links
     */
    public function __construct(
        public string $source,
        public array $nodes,
        public array $headings,
        public array $links,
    ) {
        if ($source === '' || $nodes === []) {
            throw new \InvalidArgumentException('DOCUMENT_AST_INVALID');
        }
    }

    /** @return list<SmartCallNode> */
    public function smartCalls(): array
    {
        return array_values(array_filter(
            $this->nodes,
            static fn (DocumentNode $node): bool => $node instanceof SmartCallNode,
        ));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'docara.document_ast.v1',
            'source' => $this->source,
            'nodes' => array_map(
                static fn (DocumentNode $node): array => $node->toArray(),
                $this->nodes,
            ),
            'headings' => array_map(
                static fn (DocumentHeading $heading): array => $heading->toArray(),
                $this->headings,
            ),
            'links' => array_map(
                static fn (DocumentLink $link): array => $link->toArray(),
                $this->links,
            ),
        ];
    }

    public function canonicalHash(): string
    {
        return hash('sha256', CanonicalJson::encode($this->toArray()));
    }
}
