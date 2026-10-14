<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

final readonly class SmartContextAdapterRegistry
{
    /** @var array<string, SmartContextAdapter> */
    private array $adapters;

    /** @param iterable<SmartContextAdapter> $adapters */
    public function __construct(iterable $adapters)
    {
        $indexed = [];
        foreach ($adapters as $adapter) {
            if (isset($indexed[$adapter->id()])) {
                throw new \LogicException('SMART_CONTEXT_ADAPTER_DUPLICATE:' . $adapter->id());
            }
            $indexed[$adapter->id()] = $adapter;
        }
        ksort($indexed, SORT_STRING);
        $this->adapters = $indexed;
    }

    public static function bundled(): self
    {
        return new self([
            new GenericPropsContextAdapter,
            new BrandingContextAdapter,
            new NavigationContextAdapter,
            new OutlineContextAdapter,
            new PreferencesContextAdapter,
        ]);
    }

    public function get(string $id): SmartContextAdapter
    {
        return $this->adapters[$id]
            ?? throw new \InvalidArgumentException('SMART_CONTEXT_ADAPTER_UNKNOWN:' . $id);
    }
}
