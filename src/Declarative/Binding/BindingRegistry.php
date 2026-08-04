<?php

declare(strict_types=1);

namespace Simai\Docara\Declarative\Binding;

use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class BindingRegistry
{
    /** @var array<string, BindingDescriptor> */
    private array $bindings = [];

    /** @var array<string, string> */
    private array $namespaceOwners = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @param iterable<BindingProvider> $providers */
    public function __construct(
        iterable $providers,
        private readonly SchemaRepository $schemas = new SchemaRepository,
    ) {
        $ordered = is_array($providers) ? $providers : iterator_to_array($providers);
        usort($ordered, static fn (BindingProvider $left, BindingProvider $right): int => [$right->priority(), $left->id()] <=> [$left->priority(), $right->id()]);
        $providerIds = [];
        foreach ($ordered as $provider) {
            if (isset($providerIds[$provider->id()])) {
                throw new PortableConfigurationException('BINDING_PROVIDER_DUPLICATE', "Binding provider [{$provider->id()}] is registered more than once.");
            }
            $providerIds[$provider->id()] = true;
            foreach ($provider->namespaces() as $namespace) {
                if (preg_match('/^[a-z][a-z0-9-]*$/D', $namespace) !== 1) {
                    throw new PortableConfigurationException('BINDING_NAMESPACE_INVALID', "Binding provider [{$provider->id()}] declares invalid namespace [$namespace].");
                }
                $owner = $this->namespaceOwners[$namespace] ?? null;
                if ($owner !== null && $owner !== $provider->id()) {
                    throw new PortableConfigurationException('BINDING_NAMESPACE_COLLISION', "Binding namespace [$namespace] is claimed by [$owner] and [{$provider->id()}].");
                }
                $this->namespaceOwners[$namespace] = $provider->id();
            }
            foreach ($provider->descriptors() as $descriptor) {
                $this->register($provider, $descriptor);
            }
        }
        ksort($this->bindings, SORT_STRING);
        ksort($this->namespaceOwners, SORT_STRING);
    }

    public static function bundled(): self
    {
        return new self([new BuiltinBindingProvider]);
    }

    public function get(string $id): BindingDescriptor
    {
        $id = $this->aliases[$id] ?? $id;
        $descriptor = $this->bindings[$id] ?? null;
        if (! $descriptor instanceof BindingDescriptor) {
            throw new PortableConfigurationException('DECLARATIVE_REGION_BINDING_FORBIDDEN', "Unknown declarative region binding [$id].");
        }

        return $descriptor;
    }

    /** @return list<BindingDescriptor> */
    public function all(): array
    {
        return array_values($this->bindings);
    }

    /** @return array<string, mixed> */
    public function resolve(string $id, BindingInvocation $invocation, PageCompositionContext $context): array
    {
        $descriptor = $this->get($id);
        if ($descriptor->smart !== $invocation->smart) {
            throw new PortableConfigurationException('BINDING_SMART_MISMATCH', "Binding [$id] cannot supply Smart [{$invocation->smart}].");
        }
        $view = $invocation->view === '' ? 'default' : $invocation->view;
        if (! in_array($view, $descriptor->presentations, true)) {
            throw new PortableConfigurationException('BINDING_PRESENTATION_FORBIDDEN', "Binding [$id] does not admit presentation [$view].");
        }
        $collisions = array_values(array_intersect(array_keys($invocation->staticProps), $descriptor->ownedProps));
        if ($collisions !== []) {
            sort($collisions, SORT_STRING);
            throw new PortableConfigurationException('BINDING_OWNED_PROP_COLLISION', "Binding [$id] owns prop [{$collisions[0]}]; project/static configuration cannot replace it.");
        }
        $resolved = $descriptor->resolver->resolve($invocation, $context);
        if (array_diff(array_keys($resolved), $descriptor->ownedProps) !== []) {
            throw new PortableConfigurationException('BINDING_RESOLVER_OUTPUT_FORBIDDEN', "Binding [$id] returned an undeclared prop.");
        }
        $props = [...$invocation->staticProps, ...$resolved];
        $this->schemas->assertValid($props, $descriptor->outputSchema);

        return $props;
    }

    public function fingerprint(): string
    {
        return hash('sha256', CanonicalJson::encode(array_map(
            static fn (BindingDescriptor $descriptor): array => $descriptor->provenance(),
            $this->all(),
        )));
    }

    private function register(BindingProvider $provider, BindingDescriptor $descriptor): void
    {
        $segments = explode('.', $descriptor->id, 2);
        $owner = $segments[0] ?? '';
        if (count($segments) !== 2 || $owner !== $descriptor->ownerNamespace || ! in_array($owner, $provider->namespaces(), true)) {
            throw new PortableConfigurationException('BINDING_NAMESPACE_FORBIDDEN', "Binding provider [{$provider->id()}] cannot own [{$descriptor->id}].");
        }
        if ($descriptor->provider !== $provider->id() || $descriptor->providerRevision !== $provider->revision()) {
            throw new PortableConfigurationException('BINDING_PROVENANCE_INVALID', "Binding [{$descriptor->id}] has invalid provider provenance.");
        }
        if (isset($this->bindings[$descriptor->id])) {
            throw new PortableConfigurationException('BINDING_DUPLICATE', "Binding [{$descriptor->id}] is owned by two providers.");
        }
        if ($descriptor->capabilities === [] || $descriptor->presentations === [] || $descriptor->ownedProps === []) {
            throw new PortableConfigurationException('BINDING_DESCRIPTOR_INVALID', "Binding [{$descriptor->id}] has an incomplete contract.");
        }
        $this->schemas->get($descriptor->outputSchema);
        $this->bindings[$descriptor->id] = $descriptor;
        foreach ($descriptor->storageCompatibilityAliases as $alias) {
            if (preg_match('/^[a-z][a-z0-9_]*$/D', $alias) !== 1 || isset($this->aliases[$alias]) || isset($this->bindings[$alias])) {
                throw new PortableConfigurationException('BINDING_ALIAS_COLLISION', "Binding [{$descriptor->id}] declares invalid or duplicate storage alias [$alias].");
            }
            $this->aliases[$alias] = $descriptor->id;
        }
    }
}
