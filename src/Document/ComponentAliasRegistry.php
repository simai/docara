<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ComponentAliasRegistry
{
    public function __construct(private ContentComponentRegistry $components = new ContentComponentRegistry) {}

    public function resolve(string $alias, SourceLocation $location): string
    {
        return $this->components->aliases()[$alias] ?? throw new PortableConfigurationException(
            'DOCUMENT_COMPONENT_ALIAS_UNKNOWN',
            "Unknown component alias [$alias] at [{$location->label()}].",
        );
    }

    /** @return array<string, string> */
    public function aliases(): array
    {
        return $this->components->aliases();
    }

    /** @return array<string, string> */
    public function inlineAliases(): array
    {
        return $this->components->inlineAliases();
    }
}
