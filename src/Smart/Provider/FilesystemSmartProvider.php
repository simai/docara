<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

use Simai\Docara\Portable\JsonDiagnosticLocator;
use Simai\Docara\Smart\Artifact\DocaraPortableSmartAdmissionPolicy;
use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;

class FilesystemSmartProvider implements SmartArtifactProvider
{
    /** @var list<string> */
    private array $ownedNamespaces;

    private string $safeRoot;

    /**
     * @param  list<string>  $namespaces
     */
    public function __construct(
        private readonly string $providerId,
        private readonly int $providerPriority,
        array $namespaces,
        string $root,
        private readonly string $ownerPackage,
        private readonly string $revision,
        private readonly Sf5SmartArtifactV1Contract $contract = new Sf5SmartArtifactV1Contract,
        private readonly DocaraPortableSmartAdmissionPolicy $admission = new DocaraPortableSmartAdmissionPolicy,
    ) {
        $this->ownedNamespaces = $this->normalizeNamespaces($namespaces);
        $this->safeRoot = $this->safeDirectory($root, 'root');
    }

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
        return $this->ownedNamespaces;
    }

    public function descriptors(): iterable
    {
        $directories = glob($this->safeRoot . '/*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_STRING);
        foreach ($directories as $directory) {
            if (is_link($directory)) {
                throw new SmartProviderException('SMART_PROVIDER_SYMLINK_FORBIDDEN', basename($directory));
            }
            $id = basename($directory);
            $this->assertOwned($id);
            $manifestPath = $this->safeFile($directory . '/manifest.json', $id . '/manifest.json');
            $manifest = $this->json($manifestPath, $id . '/manifest.json');
            $this->admission->assertAdmitted($manifest, $id);
            $views = $this->namedJsonArtifacts($directory, $id, 'view', 'smart.view');
            $presets = $this->namedJsonArtifacts($directory, $id, 'preset', 'smart.preset');
            $templates = $this->templates($directory, $id);
            $assets = $this->assets($directory, $id, $manifest);
            $aliases = $this->aliases($manifest, $id);
            $adapterId = $manifest['extensions']['docara']['adapter'] ?? null;
            if ($adapterId !== null && (! is_string($adapterId)
                || preg_match('/^[a-z][a-z0-9_.-]*$/D', $adapterId) !== 1)) {
                throw new SmartProviderException('SMART_PROVIDER_ADAPTER_INVALID', $id);
            }

            yield new SmartArtifactDescriptor(
                $id,
                $this->providerId,
                $this->providerPriority,
                $this->ownerPackage,
                $this->safeRoot,
                ['path' => $this->relative($manifestPath), 'schema' => null],
                $views,
                $presets,
                $templates,
                $aliases,
                $assets,
                $manifest,
                (string) $manifest['render']['strategy'],
                $adapterId,
                $this->contract->effectiveProvenance(
                    $this->providerId,
                    $this->revision,
                    'portable.manifest.direct',
                    'sf5.smart.template.v1',
                    (string) hash_file('sha256', $manifestPath),
                ),
            );
        }
    }

    public function fingerprint(): string
    {
        $records = [$this->providerId, (string) $this->providerPriority, $this->revision];
        foreach ($this->descriptors() as $descriptor) {
            $records[] = $descriptor->id . ':' . $descriptor->provenance['manifest_sha256'];
        }

        return hash('sha256', implode("\n", $records));
    }

    /** @param list<string> $namespaces @return list<string> */
    private function normalizeNamespaces(array $namespaces): array
    {
        $normalized = [];
        foreach ($namespaces as $namespace) {
            if (! is_string($namespace)
                || preg_match('/^[a-z][a-z0-9-]*$/D', $namespace) !== 1
            ) {
                throw new SmartProviderException('SMART_PROVIDER_NAMESPACE_INVALID', (string) $namespace);
            }
            $normalized[] = $namespace;
        }
        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_STRING);
        if ($normalized === []) {
            throw new SmartProviderException('SMART_PROVIDER_NAMESPACE_REQUIRED', $this->providerId);
        }

        return $normalized;
    }

    private function assertOwned(string $id): void
    {
        foreach ($this->ownedNamespaces as $namespace) {
            if (str_starts_with($id, $namespace . '.')) {
                return;
            }
        }
        throw new SmartProviderException('SMART_PROVIDER_NAMESPACE_NOT_OWNED', $this->providerId . ':' . $id);
    }

    /** @return array<string, array{path:string,schema:string,template:string}> */
    private function namedJsonArtifacts(string $directory, string $id, string $kind, string $expectedKind): array
    {
        $records = [];
        $paths = glob($directory . '/' . $kind . '/*.json') ?: [];
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $code = pathinfo($path, PATHINFO_FILENAME);
            $safe = $this->safeFile($path, $id . '/' . $kind . '/' . basename($path));
            $artifact = $this->json($safe, $id . ':' . $kind . ':' . $code);
            if ($expectedKind === 'smart.view') {
                $this->contract->assertView($artifact, $id, $code);
            } else {
                $this->contract->assertPreset($artifact, $id, $code);
            }
            $template = is_string($artifact['template'] ?? null)
                ? $artifact['template']
                : (string) ($artifact['view'] ?? 'default');
            $records[$code] = [
                'path' => $this->relative($safe),
                'schema' => $expectedKind . '.schema.json',
                'template' => 'smart.' . $id . '.' . $template,
            ];
        }

        return $records;
    }

    /** @return array<string, array{path:string,renderer:string}> */
    private function templates(string $directory, string $id): array
    {
        $records = [];
        $paths = glob($directory . '/template/*.{php,blade.php}', GLOB_BRACE) ?: [];
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $safe = $this->safeFile($path, $id . '/template/' . basename($path));
            $file = basename($safe);
            $renderer = str_ends_with($file, '.blade.php') ? 'blade' : 'php';
            $code = preg_replace('/(?:\.blade)?\.php$/', '', $file);
            $records['smart.' . $id . '.' . $code] = [
                'path' => $this->relative($safe),
                'renderer' => $renderer,
            ];
        }
        if ($records === []) {
            throw new SmartProviderException('SMART_PROVIDER_TEMPLATE_REQUIRED', $id);
        }

        return $records;
    }

    /** @param array<string, mixed> $manifest @return array<string, array{path:string,kind:string,public:string,version:string}> */
    private function assets(string $directory, string $id, array $manifest): array
    {
        $records = [];
        foreach (['css' => 'css', 'js' => 'javascript'] as $group => $kind) {
            foreach ($manifest['assets'][$group] ?? [] as $relative) {
                if (! is_string($relative)) {
                    throw new SmartProviderException('SMART_PROVIDER_ASSET_INVALID', $id . ':' . $group);
                }
                $safe = $this->safeFile($directory . '/' . $relative, $id . ':' . $relative);
                $key = $this->providerId . '.' . $id . '.' . $group . '.' . basename($safe);
                $records[$key] = [
                    'path' => $this->relative($safe),
                    'kind' => $kind,
                    'public' => 'smart/' . $id . '/' . ltrim($relative, '/'),
                    'version' => (string) hash_file('sha256', $safe),
                ];
            }
        }

        return $records;
    }

    /** @param array<string, mixed> $manifest @return array<string, string> */
    private function aliases(array $manifest, string $id): array
    {
        $aliases = $manifest['extensions']['docara']['aliases'] ?? [];
        if (! is_array($aliases) || ($aliases !== [] && array_is_list($aliases))) {
            throw new SmartProviderException('SMART_PROVIDER_ALIASES_INVALID', $id);
        }
        foreach ($aliases as $alias => $reason) {
            if (! is_string($alias) || ! is_string($reason) || $reason === '') {
                throw new SmartProviderException('SMART_PROVIDER_ALIASES_INVALID', $id);
            }
            $this->assertOwned($alias);
        }
        ksort($aliases, SORT_STRING);

        return $aliases;
    }

    private function safeDirectory(string $path, string $label): string
    {
        $stat = @lstat($path);
        $real = realpath($path);
        if (! is_array($stat) || $real === false || is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0040000
        ) {
            throw new SmartProviderException('SMART_PROVIDER_ROOT_UNSAFE', $label);
        }

        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    private function safeFile(string $path, string $label): string
    {
        $stat = @lstat($path);
        $real = realpath($path);
        if (! is_array($stat) || $real === false || is_link($path)
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || ! str_starts_with($real, $this->safeRoot . DIRECTORY_SEPARATOR)
        ) {
            throw new SmartProviderException('SMART_PROVIDER_PATH_UNSAFE', $label);
        }

        return $real;
    }

    /** @return array<string, mixed> */
    private function json(string $path, string $label): array
    {
        $contents = (string) file_get_contents($path);
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $location = JsonDiagnosticLocator::locate($contents);
            throw new SmartProviderException(
                'SMART_PROVIDER_JSON_INVALID',
                $label . ':' . $exception->getMessage(),
                basename($this->safeRoot) . '/' . $this->relative($path),
                $location['pointer'],
                $location['line'],
                $location['column'],
            );
        }
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new SmartProviderException('SMART_PROVIDER_JSON_INVALID', $label);
        }

        return $decoded;
    }

    private function relative(string $path): string
    {
        return ltrim(substr($path, strlen($this->safeRoot)), DIRECTORY_SEPARATOR);
    }
}
