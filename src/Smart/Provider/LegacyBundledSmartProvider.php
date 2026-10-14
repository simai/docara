<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

use Simai\Docara\Smart\Artifact\LegacySmartManifestV1Adapter;
use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;

/**
 * Bounded adapter for the six repository-owned pre-portable artifacts.
 * New project/package artifacts use FilesystemSmartProvider directly.
 */
final class LegacyBundledSmartProvider implements SmartArtifactProvider
{
    public function __construct(
        private readonly string $providerId,
        private readonly int $providerPriority,
        private readonly string $namespace,
        private readonly string $resourceRoot,
        private readonly string $ownerPackage,
        private readonly string $revision,
        private readonly LegacySmartManifestV1Adapter $adapter = new LegacySmartManifestV1Adapter,
        private readonly Sf5SmartArtifactV1Contract $contract = new Sf5SmartArtifactV1Contract,
    ) {}

    public function id(): string
    {
        return $this->providerId;
    }

    public function priority(): int
    {
        return $this->providerPriority;
    }

    public function namespaces(): array
    {
        return [$this->namespace];
    }

    public function descriptors(): iterable
    {
        $root = $this->safeDirectory($this->resourceRoot);
        $directories = glob($root . '/smart/' . $this->namespace . '.*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_STRING);
        foreach ($directories as $directory) {
            if (is_link($directory)) {
                throw new SmartProviderException('SMART_PROVIDER_SYMLINK_FORBIDDEN', basename($directory));
            }
            $id = basename($directory);
            $manifestPath = $this->manifestPath($root, $directory, $id);
            $legacy = $this->json($manifestPath, $id);
            $portable = $this->adapter->adapt($legacy, $id);
            $views = $this->views($root, $directory, $id);
            $templates = $this->templates($root, $directory, $id);
            $aliases = $legacy['extensions']['docara']['aliases'] ?? [];
            if (! is_array($aliases)) {
                throw new SmartProviderException('SMART_PROVIDER_ALIASES_INVALID', $id);
            }
            $assets = $this->assets($root, $legacy, $id);

            yield new SmartArtifactDescriptor(
                $id,
                $this->providerId,
                $this->providerPriority,
                $this->ownerPackage,
                $root,
                ['path' => $this->relative($root, $manifestPath), 'schema' => null],
                $views,
                [],
                $templates,
                $aliases,
                $assets,
                $portable,
                (string) $portable['render']['strategy'],
                is_string($legacy['provenance']['input_adapter'] ?? null)
                    ? $legacy['provenance']['input_adapter']
                    : 'smart.props',
                $this->contract->effectiveProvenance(
                    $this->providerId,
                    $this->revision,
                    'docara.legacy-smart-manifest.v1',
                    'docara.legacy.object-view.v1',
                    (string) hash_file('sha256', $manifestPath),
                ),
            );
        }
    }

    public function fingerprint(): string
    {
        $records = [$this->providerId, $this->revision];
        foreach ($this->descriptors() as $descriptor) {
            $records[] = $descriptor->id . ':' . $descriptor->provenance['manifest_sha256'];
        }

        return hash('sha256', implode("\n", $records));
    }

    private function manifestPath(string $root, string $directory, string $id): string
    {
        $path = $this->namespace === 'ui'
            ? $root . '/framework/manifests/' . str_replace('.', '-', $id) . '.json'
            : $directory . '/manifest.json';

        return $this->safeFile($root, $path, $id . ':manifest');
    }

    /** @return array<string, array{path:string,schema:string,template:string}> */
    private function views(string $root, string $directory, string $id): array
    {
        $records = [];
        $paths = glob($directory . '/views/*.json') ?: [];
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $safe = $this->safeFile($root, $path, $id . ':view');
            $view = $this->json($safe, $id . ':view');
            $code = (string) ($view['view'] ?? pathinfo($safe, PATHINFO_FILENAME));
            $records[$code] = [
                'path' => $this->relative($root, $safe),
                'schema' => 'declarative-smart-view.schema.json',
                'template' => (string) $view['template'],
            ];
        }

        return $records;
    }

    /** @return array<string, array{path:string,renderer:string}> */
    private function templates(string $root, string $directory, string $id): array
    {
        $records = [];
        $paths = glob($directory . '/templates/*.{php,blade.php}', GLOB_BRACE) ?: [];
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $safe = $this->safeFile($root, $path, $id . ':template');
            $file = basename($safe);
            $code = preg_replace('/(?:\.blade)?\.php$/', '', $file);
            $records['smart.' . $id . '.' . $code] = [
                'path' => $this->relative($root, $safe),
                'renderer' => str_ends_with($file, '.blade.php') ? 'blade' : 'php',
            ];
        }

        return $records;
    }

    /** @param array<string, mixed> $legacy @return array<string, array{path:string,kind:string,public:string,version:string}> */
    private function assets(string $root, array $legacy, string $id): array
    {
        $records = [];
        foreach ($legacy['assets'] ?? [] as $asset) {
            $key = is_array($asset) ? ($asset['key'] ?? null) : null;
            if (! is_string($key) || ! str_starts_with($key, 'docara.smart.')) {
                continue;
            }
            $extension = str_ends_with($key, '.js') ? 'js' : 'css';
            $name = substr($key, strlen('docara.smart.'));
            $name = substr($name, 0, -strlen('.' . $extension)) . '.' . $extension;
            $path = $this->safeFile($root, $root . '/smart/assets/' . $name, $id . ':asset:' . $key);
            $records[$key] = [
                'path' => $this->relative($root, $path),
                'kind' => $extension === 'js' ? 'javascript' : 'css',
                'public' => 'smart/' . $name,
                'version' => (string) hash_file('sha256', $path),
            ];
        }

        return $records;
    }

    private function safeDirectory(string $path): string
    {
        $real = realpath($path);
        if ($real === false || is_link($path) || ! is_dir($real)) {
            throw new SmartProviderException('SMART_PROVIDER_ROOT_UNSAFE', $path);
        }

        return $real;
    }

    private function safeFile(string $root, string $path, string $label): string
    {
        $real = realpath($path);
        $stat = @lstat($path);
        if ($real === false || ! is_array($stat) || is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || ! str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            throw new SmartProviderException('SMART_PROVIDER_PATH_UNSAFE', $label);
        }

        return $real;
    }

    /** @return array<string, mixed> */
    private function json(string $path, string $label): array
    {
        try {
            $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new SmartProviderException('SMART_PROVIDER_JSON_INVALID', $label . ':' . $exception->getMessage());
        }
        if (! is_array($value) || array_is_list($value)) {
            throw new SmartProviderException('SMART_PROVIDER_JSON_INVALID', $label);
        }

        return $value;
    }

    private function relative(string $root, string $path): string
    {
        return ltrim(substr($path, strlen($root)), DIRECTORY_SEPARATOR);
    }
}
