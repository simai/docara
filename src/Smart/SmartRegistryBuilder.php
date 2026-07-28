<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final class SmartRegistryBuilder
{
    /** @var array<string, SmartComponentDefinition> */
    private array $definitions = [];

    public function add(SmartComponentDefinition $definition): void
    {
        if (isset($this->definitions[$definition->key])) {
            throw new \LogicException('SMART_REGISTRY_DUPLICATE_COMPONENT:' . $definition->key);
        }
        $this->definitions[$definition->key] = $definition;
    }

    public function build(): SmartRegistry
    {
        return new SmartRegistry($this->definitions);
    }
}
