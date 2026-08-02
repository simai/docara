<?php

declare(strict_types=1);

namespace Simai\Docara\Smart;

use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;
use Simai\Docara\Smart\Provider\LegacyBundledSmartProvider;
use Simai\Docara\Smart\Provider\ProjectSmartProvider;
use Simai\Docara\Smart\Provider\SmartArtifactProvider;
use Simai\Docara\Smart\Provider\SmartRegistryCompiler;

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

    public static function bundled(): self
    {
        return (new SmartRegistryCompiler)->compile(self::bundledProviders());
    }

    public static function withProject(string $namespace, string $root, string $revision): self
    {
        return (new SmartRegistryCompiler)->compile([
            new ProjectSmartProvider($namespace, $root, 'project/' . $namespace, $revision),
            ...self::bundledProviders(),
        ]);
    }

    /** @return list<SmartArtifactProvider> */
    private static function bundledProviders(): array
    {
        $root = dirname(__DIR__, 2) . '/resources';

        return [
            new LegacyBundledSmartProvider(
                'docara.package', 300, 'docara', $root, 'simai/docara', 'repository-tree',
            ),
            new LegacyBundledSmartProvider(
                'framework.lock', 400, 'ui', $root, 'larena/ui', Sf5SmartArtifactV1Contract::SOURCE_REVISION,
            ),
        ];
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

    /** @return array{path:string,renderer:string,root?:string} */
    public function template(string $templateId): array
    {
        foreach ($this->definitions as $definition) {
            if (isset($definition->templates[$templateId])) {
                return $definition->templates[$templateId] + ['root' => $definition->root];
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

    /** @return array<string, array{path:string,kind:string,public:string,version:string,root?:string}> */
    public function assets(): array
    {
        $assets = [];
        foreach ($this->definitions as $definition) {
            foreach ($definition->assets as $key => $asset) {
                if (isset($assets[$key])) {
                    throw new \LogicException('SMART_REGISTRY_DUPLICATE_ASSET:' . $key);
                }
                $assets[$key] = $asset + ['root' => $definition->root];
            }
        }
        ksort($assets, SORT_STRING);

        return $assets;
    }
}
