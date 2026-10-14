<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Smart;

use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ProviderPlanResolverRegistry
{
    /** @var array<string, ProviderSmartPlanResolver> */
    private array $resolvers;

    /** @param iterable<ProviderSmartPlanResolver> $resolvers */
    public function __construct(iterable $resolvers)
    {
        $indexed = [];
        foreach ($resolvers as $resolver) {
            if (isset($indexed[$resolver->providerId()])) {
                throw new \LogicException('SMART_PROVIDER_RESOLVER_DUPLICATE:' . $resolver->providerId());
            }
            $indexed[$resolver->providerId()] = $resolver;
        }
        ksort($indexed, SORT_STRING);
        $this->resolvers = $indexed;
    }

    public function get(string $providerId): ProviderSmartPlanResolver
    {
        return $this->resolvers[$providerId] ?? throw new PortableConfigurationException(
            'DECLARATIVE_SMART_PROVIDER_UNAVAILABLE',
            "Smart provider [$providerId] is registered but unavailable in this gateway.",
        );
    }
}
