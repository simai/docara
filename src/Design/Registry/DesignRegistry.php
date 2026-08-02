<?php

declare(strict_types=1);

namespace Simai\Docara\Design\Registry;

use Simai\Docara\Design\Artifact\DesignArtifactDescriptor;
use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Design\Provider\BuiltinDesignProvider;
use Simai\Docara\Design\Provider\DesignArtifactProvider;
use Simai\Docara\Design\Provider\ProjectDesignProvider;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class DesignRegistry
{
    /** @var array<string, DesignArtifactDescriptor> */
    private array $artifacts = [];

    /** @var array<string, string> */
    private array $namespaceOwners = [];

    /** @param iterable<DesignArtifactProvider> $providers */
    public function __construct(
        iterable $providers,
        private readonly SchemaRepository $schemas = new SchemaRepository,
    ) {
        $ordered = is_array($providers) ? $providers : iterator_to_array($providers);
        usort($ordered, static fn (DesignArtifactProvider $left, DesignArtifactProvider $right): int => [$right->priority(), $left->id()] <=> [$left->priority(), $right->id()]);
        $providerIds = [];
        foreach ($ordered as $provider) {
            if (isset($providerIds[$provider->id()])) {
                throw new PortableConfigurationException(
                    'DESIGN_PROVIDER_DUPLICATE',
                    "Design provider [{$provider->id()}] is registered more than once.",
                );
            }
            $providerIds[$provider->id()] = true;
            foreach ($provider->namespaces() as $namespace) {
                $owner = $this->namespaceOwners[$namespace] ?? null;
                if ($owner !== null && $owner !== $provider->id()) {
                    throw new PortableConfigurationException(
                        'DESIGN_NAMESPACE_COLLISION',
                        "Design namespace [$namespace] is claimed by [$owner] and [{$provider->id()}].",
                    );
                }
                $this->namespaceOwners[$namespace] = $provider->id();
            }
            foreach ($provider->descriptors() as $descriptor) {
                $this->schemas->assertValid($descriptor->definition, $descriptor->kind->schema());
                $key = $this->key($descriptor->kind, $descriptor->id);
                if (isset($this->artifacts[$key])) {
                    $owner = $this->artifacts[$key]->provider;
                    throw new PortableConfigurationException(
                        'DESIGN_ARTIFACT_DUPLICATE',
                        "Design artifact [{$descriptor->kind->value}:{$descriptor->id}] is owned by [$owner] and [{$descriptor->provider}].",
                    );
                }
                $this->artifacts[$key] = $descriptor;
            }
        }
        ksort($this->artifacts, SORT_STRING);
        ksort($this->namespaceOwners, SORT_STRING);
    }

    public static function bundled(?string $projectRoot = null, ?string $projectNamespace = null): self
    {
        $providers = [new BuiltinDesignProvider];
        if ($projectRoot !== null && $projectNamespace !== null) {
            $providers[] = new ProjectDesignProvider(
                $projectRoot,
                $projectNamespace,
                'project-design-' . substr(hash('sha256', $projectRoot . "\0" . $projectNamespace), 0, 16),
            );
        }

        return new self($providers);
    }

    public function get(DesignArtifactKind $kind, string $id): DesignArtifactDescriptor
    {
        $descriptor = $this->artifacts[$this->key($kind, $id)] ?? null;
        if (! $descriptor instanceof DesignArtifactDescriptor) {
            throw new PortableConfigurationException(
                'DECLARATIVE_DEFINITION_NOT_ALLOWED',
                "Definition [{$kind->value}:$id] is not registered.",
            );
        }

        return $descriptor;
    }

    public function defaultLayout(): DesignArtifactDescriptor
    {
        $defaults = array_values(array_filter(
            $this->all(DesignArtifactKind::Layout),
            static fn (DesignArtifactDescriptor $descriptor): bool => ($descriptor->definition['default'] ?? false) === true,
        ));
        if (count($defaults) !== 1) {
            throw new PortableConfigurationException(
                'DESIGN_DEFAULT_LAYOUT_INVALID',
                'Exactly one registered layout must declare itself as the default.',
            );
        }

        return $defaults[0];
    }

    /** @return list<DesignArtifactDescriptor> */
    public function all(?DesignArtifactKind $kind = null): array
    {
        return array_values(array_filter(
            $this->artifacts,
            static fn (DesignArtifactDescriptor $descriptor): bool => $kind === null || $descriptor->kind === $kind,
        ));
    }

    /** @return array<string, string> */
    public function namespaceOwners(): array
    {
        return $this->namespaceOwners;
    }

    public function fingerprint(): string
    {
        return hash('sha256', CanonicalJson::encode(array_map(
            static fn (DesignArtifactDescriptor $descriptor): array => [
                $descriptor->kind->value,
                $descriptor->id,
                $descriptor->provider,
                $descriptor->providerRevision,
                $descriptor->sha256,
            ],
            $this->all(),
        )));
    }

    private function key(DesignArtifactKind $kind, string $id): string
    {
        return $kind->value . ':' . $id;
    }
}
