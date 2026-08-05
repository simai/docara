<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Smart\Artifact\Sf5SmartArtifactV1Contract;

final readonly class FrameworkPortableSmartLock
{
    /** @param array<string, mixed> $data */
    private function __construct(private array $data, private string $root)
    {
        $this->assertValid();
    }

    public static function fromJsonFile(string $path, string $artifactRoot): self
    {
        $bytes = @file_get_contents($path);
        if (! is_string($bytes)) {
            throw new SmartProviderException('SMART_FRAMEWORK_PORTABLE_LOCK_MISSING', $path);
        }
        try {
            $data = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new SmartProviderException('SMART_FRAMEWORK_PORTABLE_LOCK_INVALID', $exception->getMessage());
        }
        if (! is_array($data) || array_is_list($data)) {
            throw new SmartProviderException('SMART_FRAMEWORK_PORTABLE_LOCK_INVALID');
        }

        return new self($data, $artifactRoot);
    }

    /** @return array<string, mixed> */
    public function artifact(string $id): array
    {
        $record = $this->data['artifacts'][$id] ?? null;
        if (! is_array($record)) {
            throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_NOT_LOCKED', $id);
        }

        return $record;
    }

    /** @return array<string, mixed> */
    public function packetFor(string $id): array
    {
        $artifact = $this->artifact($id);
        $packet = $this->data['packets'][$artifact['packet']] ?? null;
        if (! is_array($packet)) {
            throw new SmartProviderException('SMART_FRAMEWORK_PACKET_NOT_LOCKED', $id);
        }

        return $packet;
    }

    public function assertArtifact(string $id): void
    {
        $record = $this->artifact($id);
        $files = $record['files'];
        $ledger = '';
        foreach ($files as $relative => $expected) {
            $path = $this->root . '/' . $id . '/' . $relative;
            $stat = @lstat($path);
            $real = realpath($path);
            if (! is_array($stat) || $real === false || is_link($path)
                || (($stat['mode'] ?? 0) & 0170000) !== 0100000
                || ($stat['nlink'] ?? 1) !== 1
                || ! str_starts_with($real, rtrim((string) realpath($this->root), '/') . '/')
            ) {
                throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_PATH_UNSAFE', $id . ':' . $relative);
            }
            $actual = hash_file('sha256', $real);
            if (! hash_equals($expected, $actual)) {
                throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_HASH_MISMATCH', $id . ':' . $relative);
            }
            $ledger .= $actual . '  ' . $relative . "\n";
        }
        if (! hash_equals((string) $record['artifact_tree_sha256'], hash('sha256', $ledger))) {
            throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_TREE_MISMATCH', $id);
        }
        $manifest = json_decode((string) file_get_contents($this->root . '/' . $id . '/manifest.json'), true);
        if (! is_array($manifest)
            || ! hash_equals((string) $record['slot_contract_sha256'], $this->canonicalHash($manifest['slots'] ?? []))
            || ! hash_equals((string) $record['asset_contract_sha256'], $this->canonicalHash($manifest['assets'] ?? []))
            || ! hash_equals((string) $record['hydration_contract_sha256'], $this->canonicalHash($this->hydrationContract($manifest)))
        ) {
            throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_CONTRACT_MISMATCH', $id);
        }
        foreach ($this->runtimeAssets($id) as $key => $asset) {
            $path = dirname($this->root) . '/' . $asset['path'];
            $stat = @lstat($path);
            $real = realpath($path);
            $frameworkRoot = realpath(dirname($this->root));
            if (! is_array($stat) || $real === false || $frameworkRoot === false || is_link($path)
                || (($stat['mode'] ?? 0) & 0170000) !== 0100000
                || ($stat['nlink'] ?? 1) !== 1
                || ! str_starts_with($real, rtrim($frameworkRoot, '/') . '/')
                || ! hash_equals($asset['sha256'], (string) hash_file('sha256', $real))
            ) {
                throw new SmartProviderException('SMART_FRAMEWORK_RUNTIME_ASSET_MISMATCH', $id . ':' . $key);
            }
        }
    }

    /** @return array<string, array{path:string,kind:string,public:string,sha256:string}> */
    public function runtimeAssets(string $id): array
    {
        /** @var array<string, array{path:string,kind:string,public:string,sha256:string}> $assets */
        $assets = $this->artifact($id)['runtime_assets'];

        return $assets;
    }

    public function fingerprint(): string
    {
        return hash('sha256', CanonicalJson::encode($this->data));
    }

    private function assertValid(): void
    {
        $contract = $this->data['contract'] ?? null;
        if (($this->data['schema'] ?? null) !== 'docara.framework_portable_smart_lock.v1'
            || ! is_array($contract)
            || ($contract['contract_id'] ?? null) !== Sf5SmartArtifactV1Contract::CONTRACT_ID
            || ($contract['schema_version'] ?? null) !== Sf5SmartArtifactV1Contract::SCHEMA_VERSION
            || ($contract['compatibility_id'] ?? null) !== Sf5SmartArtifactV1Contract::COMPATIBILITY_ID
            || ($contract['storage_compatibility_alias'] ?? null) !== Sf5SmartArtifactV1Contract::STORAGE_COMPATIBILITY_ALIAS
            || ($contract['template_abi'] ?? null) !== 'sf5.smart.template.v1'
        ) {
            throw new SmartProviderException('SMART_FRAMEWORK_PORTABLE_LOCK_CONTRACT_INVALID');
        }
        $packets = $this->data['packets'] ?? null;
        $artifacts = $this->data['artifacts'] ?? null;
        if (! is_array($packets) || array_is_list($packets) || $packets === []
            || ! is_array($artifacts) || array_is_list($artifacts) || $artifacts === []
        ) {
            throw new SmartProviderException('SMART_FRAMEWORK_PORTABLE_LOCK_INVALID');
        }
        foreach ($packets as $name => $packet) {
            if (! is_string($name) || ! is_array($packet)
                || ! $this->sha($packet['product_candidate'] ?? null, 40)
                || ! $this->sha($packet['packet_content_sha256'] ?? null)
                || ! $this->sha($packet['packet_file_sha256'] ?? null)
                || ! $this->sha($packet['build_tree_sha256'] ?? null)
            ) {
                throw new SmartProviderException('SMART_FRAMEWORK_PACKET_LOCK_INVALID', (string) $name);
            }
        }
        foreach ($artifacts as $id => $record) {
            if (! is_string($id) || preg_match('/^ui\.[a-z][a-z0-9-]*$/D', $id) !== 1
                || ! is_array($record) || ! isset($packets[$record['packet'] ?? ''])
                || ! $this->sha($record['artifact_tree_sha256'] ?? null)
                || ! $this->sha($record['slot_contract_sha256'] ?? null)
                || ! $this->sha($record['asset_contract_sha256'] ?? null)
                || ! $this->sha($record['hydration_contract_sha256'] ?? null)
                || ! is_string($record['support'] ?? null)
                || ! is_array($record['constraints'] ?? null)
                || ($record['constraints'] !== [] && array_is_list($record['constraints']))
                || ! is_array($record['dependencies'] ?? null) || ! array_is_list($record['dependencies'])
                || ! is_array($record['runtime_assets'] ?? null) || array_is_list($record['runtime_assets'])
                || ! is_array($record['files'] ?? null) || array_is_list($record['files']) || $record['files'] === []
            ) {
                throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_LOCK_INVALID', (string) $id);
            }
            $constraints = $record['constraints'];
            foreach ($constraints['admitted_parents'] ?? [] as $parent) {
                if (! is_string($parent) || preg_match('/^ui\.[a-z][a-z0-9-]*$/D', $parent) !== 1) {
                    throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_CONSTRAINT_INVALID', $id);
                }
            }
            foreach ($constraints['prop_values'] ?? [] as $prop => $values) {
                if (! is_string($prop) || ! is_array($values) || ! array_is_list($values) || $values === []) {
                    throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_CONSTRAINT_INVALID', $id);
                }
            }
            foreach ($record['dependencies'] as $dependency) {
                if (! is_string($dependency) || ! isset($artifacts[$dependency])) {
                    throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_DEPENDENCY_INVALID', $id);
                }
            }
            foreach ($record['runtime_assets'] as $key => $asset) {
                if (! is_string($key) || preg_match('/^[a-z][a-z0-9_.-]*$/D', $key) !== 1
                    || ! is_array($asset)
                    || ! is_string($asset['path'] ?? null) || str_starts_with($asset['path'], '/')
                    || str_contains($asset['path'], '..') || str_contains($asset['path'], '\\')
                    || ! in_array($asset['kind'] ?? null, ['css', 'javascript'], true)
                    || ! is_string($asset['public'] ?? null) || str_starts_with($asset['public'], '/')
                    || str_contains($asset['public'], '..') || str_contains($asset['public'], '\\')
                    || ! $this->sha($asset['sha256'] ?? null)
                ) {
                    throw new SmartProviderException('SMART_FRAMEWORK_RUNTIME_ASSET_LOCK_INVALID', $id . ':' . (string) $key);
                }
            }
            $files = $record['files'];
            ksort($files, SORT_STRING);
            if ($files !== $record['files']) {
                throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_LOCK_ORDER_INVALID', $id);
            }
            foreach ($files as $path => $sha) {
                if (! is_string($path) || str_starts_with($path, '/') || str_contains($path, '..')
                    || str_contains($path, '\\') || ! $this->sha($sha)
                ) {
                    throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_FILE_LOCK_INVALID', $id . ':' . $path);
                }
            }
        }
    }

    private function sha(mixed $value, int $length = 64): bool
    {
        return is_string($value) && preg_match('/^[a-f0-9]{' . $length . '}$/D', $value) === 1;
    }

    /** @param array<string, mixed> $manifest @return array<string, mixed> */
    private function hydrationContract(array $manifest): array
    {
        $render = is_array($manifest['render'] ?? null) ? $manifest['render'] : [];
        $contract = [];
        foreach (['mode', 'strategy', 'hydration', 'domStrategy', 'updateStrategy', 'initialHtml', 'frontendOwnership'] as $key) {
            if (array_key_exists($key, $render)) {
                $contract[$key] = $render[$key];
            }
        }

        return $contract;
    }

    private function canonicalHash(mixed $value): string
    {
        return hash('sha256', CanonicalJson::encode($value));
    }
}
