<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

use Simai\Docara\Smart\SmartRegistry;
use Simai\Docara\Smart\SmartRegistryBuilder;

final class SmartRegistryCompiler
{
    /** @param iterable<SmartArtifactProvider> $providers */
    public function compile(iterable $providers): SmartRegistry
    {
        $ordered = is_array($providers) ? array_values($providers) : iterator_to_array($providers, false);
        usort($ordered, static fn (SmartArtifactProvider $left, SmartArtifactProvider $right): int => [
            $left->priority(),
            $left->id(),
        ] <=> [
            $right->priority(),
            $right->id(),
        ]);
        $providerIds = [];
        $namespaceOwners = [];
        $builder = new SmartRegistryBuilder;
        foreach ($ordered as $provider) {
            if (isset($providerIds[$provider->id()])) {
                throw new SmartProviderException('SMART_PROVIDER_DUPLICATE', $provider->id());
            }
            $providerIds[$provider->id()] = true;
            foreach ($provider->namespaces() as $namespace) {
                if (isset($namespaceOwners[$namespace])) {
                    throw new SmartProviderException(
                        'SMART_PROVIDER_NAMESPACE_COLLISION',
                        $namespace . ':' . $namespaceOwners[$namespace] . ':' . $provider->id(),
                    );
                }
                $namespaceOwners[$namespace] = $provider->id();
            }
            foreach ($provider->descriptors() as $descriptor) {
                if ($descriptor->providerId !== $provider->id()
                    || $descriptor->priority !== $provider->priority()
                ) {
                    throw new SmartProviderException('SMART_PROVIDER_DESCRIPTOR_MISMATCH', $descriptor->id);
                }
                $builder->add($descriptor->definition());
            }
        }

        return $builder->build();
    }
}
