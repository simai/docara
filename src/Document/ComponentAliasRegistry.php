<?php

declare(strict_types=1);

namespace Simai\Docara\Document;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ComponentAliasRegistry
{
    /** @param array<string, string> $aliases */
    public function __construct(private array $aliases = [
        'alert' => 'docara.alert',
        'badge' => 'docara.badge',
    ]) {}

    public function resolve(string $alias, SourceLocation $location): string
    {
        return $this->aliases[$alias] ?? throw new PortableConfigurationException(
            'DOCUMENT_COMPONENT_ALIAS_UNKNOWN',
            "Unknown component alias [$alias] at [{$location->label()}].",
        );
    }

    /** @return array<string, string> */
    public function aliases(): array
    {
        return $this->aliases;
    }
}
