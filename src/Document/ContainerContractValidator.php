<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\ComponentCatalog\TypedComponentDefinitionRepository;
use Simai\Docara\Declarative\Smart\SmartComponentGateway;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ContainerContractValidator
{
    public function __construct(
        private TypedComponentDefinitionRepository $definitions,
        private SmartComponentGateway $smarts,
    ) {}

    /** @param list<DocumentNode> $children */
    public function validate(string $parent, array $children, SourceLocation $location): void
    {
        $contract = $this->definitions->containerContract($parent);
        if ($contract === null) {
            throw $this->error('DOCUMENT_CONTAINER_CONTRACT_MISSING', $location, "Container [$parent] has no registry contract.");
        }
        $contractChildren = array_values(array_filter(
            $children,
            static fn (DocumentNode $child): bool => $child instanceof SmartComponentNode
                || $child instanceof ContainerNode
                || $child instanceof ComponentBlockNode
                || ($child instanceof SourceNode && $child->type() === 'typed_directive'),
        ));
        $count = $contractChildren === [] && $children !== [] ? 1 : count($contractChildren);
        $minimum = (int) ($contract['min_children'] ?? 0);
        $maximum = (int) ($contract['max_children'] ?? 0);
        if ($count < $minimum) {
            throw $this->error('DOCUMENT_CONTAINER_CHILD_COUNT_MIN', $location, "Container [$parent] requires at least [$minimum] direct child nodes.");
        }
        if ($maximum < 1 || $count > $maximum) {
            throw $this->error('DOCUMENT_CONTAINER_CHILD_COUNT_MAX', $location, "Container [$parent] accepts at most [$maximum] direct child nodes.");
        }
        if (($contract['order'] ?? null) !== 'declared') {
            throw $this->error('DOCUMENT_CONTAINER_ORDER_INVALID', $location, "Container [$parent] has an unsupported registry order contract.");
        }
        if (($contract['depth_semantics'] ?? null) !== 'relative_subtree_root_level_1') {
            throw $this->error('DOCUMENT_CONTAINER_DEPTH_SEMANTICS_INVALID', $location, "Container [$parent] has an unsupported depth semantics contract.");
        }
        $slots = array_values(array_filter($contract['slots'] ?? [], 'is_string'));
        if ($slots !== [] && $slots !== ['content']) {
            throw $this->error('DOCUMENT_CONTAINER_SLOT_INVALID', $location, "Container [$parent] has an unsupported slot contract.");
        }
        $this->assertRelativeDepth($parent, $children, (int) ($contract['max_depth'] ?? 0), 1);

        foreach ($children as $child) {
            if ($child instanceof SmartComponentNode) {
                $capabilities = $this->definitions->allowedChildCapabilities($parent);
                if (! $this->supportsAnyCapability($child->smart, $capabilities)) {
                    $required = implode(', ', $capabilities);
                    throw $this->error('DOCUMENT_CONTAINER_SMART_CHILD_FORBIDDEN', $child->location(), "Smart child [{$child->smart}] is not admitted by [$parent] capabilities [$required].");
                }

                continue;
            }
            if ($child instanceof ContainerNode) {
                if (! $this->definitions->allowsChild($parent, $child->alias)) {
                    throw $this->error('DOCUMENT_CONTAINER_CHILD_FORBIDDEN', $child->location(), "Container child [{$child->alias}] is not admitted by [$parent].");
                }
                $this->validate($child->alias, $child->children(), $child->location());

                continue;
            }
            if ($child instanceof ComponentBlockNode) {
                if (! $this->definitions->allowsChild($parent, $child->alias)) {
                    throw $this->error('DOCUMENT_CONTAINER_CHILD_FORBIDDEN', $child->location(), "Component child [{$child->alias}] is not admitted by [$parent].");
                }

                continue;
            }
            if ($child instanceof SourceNode) {
                if ($child->type() === 'typed_directive') {
                    $childComponent = (string) ($child->data['component'] ?? '');
                    $definition = $this->definitions->findByName((string) ($child->data['alias'] ?? ''));
                    if ($definition === null
                        || $childComponent === ''
                        || ! $this->definitions->allowsChild($parent, (string) $definition['name'])
                    ) {
                        throw $this->error('DOCUMENT_CONTAINER_CHILD_FORBIDDEN', $child->location(), "Typed child [$childComponent] is not admitted by [$parent].");
                    }
                }

                continue;
            }
            throw $this->error('DOCUMENT_CONTAINER_CHILD_FORBIDDEN', $child->location(), "Child [{$child->type()}] is not admitted by [$parent].");
        }
    }

    /** @param list<DocumentNode> $children */
    private function assertRelativeDepth(string $parent, array $children, int $maxDepth, int $level): void
    {
        foreach ($children as $child) {
            if (! $child instanceof ContainerNode) {
                continue;
            }
            $childLevel = $level + 1;
            if ($maxDepth < 1 || $childLevel > $maxDepth) {
                throw $this->error(
                    'DOCUMENT_CONTAINER_DEPTH_EXCEEDED',
                    $child->location(),
                    "Container [$parent] exceeds max_depth [$maxDepth] at child [{$child->alias}].",
                );
            }
            $this->assertRelativeDepth($parent, $child->children(), $maxDepth, $childLevel);
        }
    }

    /** @param list<string> $capabilities */
    private function supportsAnyCapability(string $smart, array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if ($this->smarts->supportsCapability($smart, $capability)) {
                return true;
            }
        }

        return false;
    }

    private function error(string $code, SourceLocation $location, string $message): PortableConfigurationException
    {
        return new PortableConfigurationException(
            $code,
            $message . ' Source [' . $location->label() . '].',
            diagnosticPath: $location->file,
            diagnosticPointer: '/document/container',
            diagnosticLine: $location->line,
            diagnosticColumn: $location->column,
        );
    }
}
