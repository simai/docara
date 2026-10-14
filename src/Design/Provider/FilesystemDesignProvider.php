<?php

declare(strict_types=1);

namespace Simai\Docara\Design\Provider;

use JsonException;
use Simai\Docara\Design\Artifact\DesignArtifactDescriptor;
use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;

class FilesystemDesignProvider implements DesignArtifactProvider
{
    /** @var list<DesignArtifactDescriptor>|null */
    private ?array $loaded = null;

    /** @param list<string> $namespaces */
    public function __construct(
        private readonly string $providerId,
        private readonly string $providerRevision,
        private readonly string $root,
        private readonly array $namespaces,
        private readonly int $providerPriority,
        private readonly bool $optional = false,
    ) {
        if (preg_match('/^[a-z][a-z0-9_.-]+$/D', $providerId) !== 1
            || $providerRevision === ''
            || $namespaces === []
            || count($namespaces) !== count(array_unique($namespaces))
        ) {
            throw new PortableConfigurationException(
                'DESIGN_PROVIDER_INVALID',
                "Design provider [$providerId] has an invalid identity, revision or namespace set.",
            );
        }
        foreach ($namespaces as $namespace) {
            if (preg_match('/^[a-z][a-z0-9-]*$/D', $namespace) !== 1) {
                throw new PortableConfigurationException(
                    'DESIGN_PROVIDER_NAMESPACE_INVALID',
                    "Design provider [$providerId] declares invalid namespace [$namespace].",
                );
            }
        }
    }

    public function id(): string
    {
        return $this->providerId;
    }

    public function revision(): string
    {
        return $this->providerRevision;
    }

    public function priority(): int
    {
        return $this->providerPriority;
    }

    public function namespaces(): array
    {
        return $this->namespaces;
    }

    public function descriptors(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }
        if (! file_exists($this->root)) {
            if ($this->optional) {
                return $this->loaded = [];
            }
            throw new PortableConfigurationException(
                'DESIGN_PROVIDER_ROOT_MISSING',
                "Design provider [{$this->providerId}] root is missing.",
            );
        }
        if (is_link($this->root) || ! is_dir($this->root) || realpath($this->root) === false) {
            throw new PortableConfigurationException(
                'DESIGN_PROVIDER_ROOT_INVALID',
                "Design provider [{$this->providerId}] root must be a real directory and cannot be a symlink.",
            );
        }

        $root = rtrim((string) realpath($this->root), DIRECTORY_SEPARATOR);
        $descriptors = [];
        foreach (DesignArtifactKind::cases() as $kind) {
            $directory = $root . DIRECTORY_SEPARATOR . $kind->directory();
            if (! file_exists($directory)) {
                continue;
            }
            if (is_link($directory) || ! is_dir($directory)) {
                throw new PortableConfigurationException(
                    'DESIGN_PROVIDER_DIRECTORY_INVALID',
                    "Design provider [{$this->providerId}] directory [{$kind->directory()}] is invalid.",
                );
            }
            $paths = glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: [];
            sort($paths, SORT_STRING);
            foreach ($paths as $path) {
                $descriptors[] = $this->descriptor($root, $kind, $path);
            }
        }

        return $this->loaded = $descriptors;
    }

    public function fingerprint(): string
    {
        return hash('sha256', CanonicalJson::encode(array_map(
            static fn (DesignArtifactDescriptor $descriptor): array => [
                $descriptor->kind->value,
                $descriptor->id,
                $descriptor->relativePath,
                $descriptor->sha256,
            ],
            $this->descriptors(),
        )));
    }

    private function descriptor(string $root, DesignArtifactKind $kind, string $path): DesignArtifactDescriptor
    {
        if (is_link($path) || ! is_file($path)) {
            throw new PortableConfigurationException(
                'DESIGN_ARTIFACT_PATH_INVALID',
                "Design provider [{$this->providerId}] contains an invalid artifact path.",
            );
        }
        $real = realpath($path);
        if ($real === false || ! str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            throw new PortableConfigurationException(
                'DESIGN_ARTIFACT_OUTSIDE_ROOT',
                "Design provider [{$this->providerId}] artifact escapes its root.",
            );
        }
        try {
            $definition = json_decode((string) file_get_contents($real), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PortableConfigurationException(
                'DESIGN_ARTIFACT_JSON_INVALID',
                "Design artifact [{$this->relative($root, $real)}] is not valid JSON.",
                $exception,
            );
        }
        $id = is_array($definition) ? ($definition['key'] ?? null) : null;
        if (! is_string($id) || preg_match('/^[a-z][a-z0-9_.-]+$/D', $id) !== 1) {
            throw new PortableConfigurationException(
                'DESIGN_ARTIFACT_ID_INVALID',
                "Design artifact [{$this->relative($root, $real)}] has an invalid key.",
            );
        }
        $owner = $this->ownerNamespace($kind, $id);
        if (! in_array($owner, $this->namespaces, true)) {
            throw new PortableConfigurationException(
                'DESIGN_ARTIFACT_NAMESPACE_FORBIDDEN',
                "Design provider [{$this->providerId}] cannot own [$id].",
            );
        }

        return new DesignArtifactDescriptor(
            $kind,
            $id,
            $owner,
            $this->providerId,
            $this->providerRevision,
            $this->relative($root, $real),
            (string) hash_file('sha256', $real),
            $definition,
        );
    }

    private function ownerNamespace(DesignArtifactKind $kind, string $id): string
    {
        $segments = explode('.', $id);
        if ($kind === DesignArtifactKind::View) {
            if (count($segments) < 3 || ! in_array($segments[0], ['layout', 'section'], true)) {
                throw new PortableConfigurationException(
                    'DESIGN_VIEW_ID_INVALID',
                    "Design View Tree [$id] must use layout.<namespace>.* or section.<namespace>.*.",
                );
            }

            return $segments[1];
        }

        return $segments[0];
    }

    private function relative(string $root, string $path): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($root) + 1));
    }
}
