<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

final readonly class DocumentIr
{
    /** @param list<DocumentNode> $nodes */
    public function __construct(public string $source, public array $nodes)
    {
        if ($source === '' || $nodes === []) {
            throw new \InvalidArgumentException('DOCUMENT_IR_EMPTY');
        }
    }

    /** @return list<DocumentNode> */
    public function allNodes(): array
    {
        $all = [];
        $visit = function (DocumentNode $node) use (&$visit, &$all): void {
            $all[] = $node;
            foreach ($node->children() as $child) {
                $visit($child);
            }
        };
        foreach ($this->nodes as $node) {
            $visit($node);
        }

        return $all;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'docara.document_ir.v1',
            'source' => $this->source,
            'nodes' => array_map(static fn (DocumentNode $node): array => $node->toArray(), $this->nodes),
        ];
    }
}
