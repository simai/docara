<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

final readonly class SmartRegistry
{
    /** @var array<string, SmartComponentDefinition> */
    private array $definitions;

    /** @var array<string, array{canonical:string,reason:string}> */
    private array $aliases;

    /** @param array<string, SmartComponentDefinition> $definitions */
    public function __construct(array $definitions)
    {
        $aliases = [];
        foreach ($definitions as $key => $definition) {
            if ($key !== $definition->key) {
                throw new \LogicException('SMART_REGISTRY_KEY_MISMATCH:' . $key);
            }
            foreach ($definition->aliases as $alias => $reason) {
                if (isset($definitions[$alias]) || isset($aliases[$alias])) {
                    throw new \LogicException('SMART_REGISTRY_DUPLICATE_ALIAS:' . $alias);
                }
                $aliases[$alias] = ['canonical' => $key, 'reason' => $reason];
            }
        }
        $this->definitions = $definitions;
        $this->aliases = $aliases;
    }

    /** @param iterable<SmartContribution> $contributions */
    public static function fromContributions(iterable $contributions): self
    {
        $builder = new SmartRegistryBuilder;
        foreach ($contributions as $contribution) {
            $contribution->contribute($builder);
        }

        return $builder->build();
    }

    public static function bundled(): self
    {
        return self::fromContributions([
            new FrameworkSmartContribution,
            new DocaraSmartContribution,
        ]);
    }

    public function definition(string $key): SmartComponentDefinition
    {
        $canonical = $this->canonicalKey($key);

        return $this->definitions[$canonical]
            ?? throw new \InvalidArgumentException('SMART_REGISTRY_COMPONENT_NOT_FOUND:' . $key);
    }

    public function canonicalKey(string $key): string
    {
        return $this->aliases[$key]['canonical'] ?? $key;
    }

    /** @return array{requested:string,canonical:string,deprecated:bool,reason:?string} */
    public function resolution(string $key): array
    {
        $alias = $this->aliases[$key] ?? null;

        return [
            'requested' => $key,
            'canonical' => $alias['canonical'] ?? $key,
            'deprecated' => $alias !== null,
            'reason' => $alias['reason'] ?? null,
        ];
    }

    /** @return list<string> */
    public function keys(): array
    {
        $keys = array_keys($this->definitions);
        sort($keys, SORT_STRING);

        return $keys;
    }

    /** @return array<string, array{canonical:string,reason:string}> */
    public function aliases(): array
    {
        return $this->aliases;
    }

    /** @return array{path:string,renderer:string} */
    public function template(string $templateId): array
    {
        foreach ($this->definitions as $definition) {
            if (isset($definition->templates[$templateId])) {
                return $definition->templates[$templateId];
            }
        }

        throw new \InvalidArgumentException('SMART_REGISTRY_TEMPLATE_NOT_FOUND:' . $templateId);
    }

    /** @return array{path:string,kind:string,public:string,version:string} */
    public function asset(string $assetKey): array
    {
        foreach ($this->definitions as $definition) {
            if (isset($definition->assets[$assetKey])) {
                return $definition->assets[$assetKey];
            }
        }

        throw new \InvalidArgumentException('SMART_REGISTRY_ASSET_NOT_FOUND:' . $assetKey);
    }

    /** @return array<string, array{path:string,kind:string,public:string,version:string}> */
    public function assets(): array
    {
        $assets = [];
        foreach ($this->definitions as $definition) {
            foreach ($definition->assets as $key => $asset) {
                if (isset($assets[$key])) {
                    throw new \LogicException('SMART_REGISTRY_DUPLICATE_ASSET:' . $key);
                }
                $assets[$key] = $asset;
            }
        }
        ksort($assets, SORT_STRING);

        return $assets;
    }
}
