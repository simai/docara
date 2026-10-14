<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Provider;

/**
 * One namespace owner for exact portable Framework packets plus the bounded
 * Alert/Button storage compatibility adapter. Selection is artifact-format
 * driven; no component identity is dispatched here.
 */
final class FrameworkLockSmartProvider implements SmartArtifactProvider
{
    private FilesystemSmartProvider $portable;

    private ?LegacyBundledSmartProvider $legacy;

    private ?FrameworkPortableSmartLock $lock;

    public function __construct(
        string $root,
        string $ownerPackage,
        private readonly string $revision,
        ?string $lockPath = null,
        ?string $legacyResourceRoot = null,
    ) {
        if ($revision === '' || in_array(strtolower($revision), ['main', 'master', 'latest', 'head'], true)) {
            throw new SmartProviderException('SMART_FRAMEWORK_REVISION_IMMUTABLE_REQUIRED', $revision);
        }
        $this->portable = new FilesystemSmartProvider(
            'framework.lock',
            400,
            ['ui'],
            $root,
            $ownerPackage,
            $revision,
        );
        $this->lock = $lockPath === null
            ? null
            : FrameworkPortableSmartLock::fromJsonFile($lockPath, $root);
        $this->legacy = $legacyResourceRoot === null
            ? null
            : new LegacyBundledSmartProvider(
                'framework.lock',
                400,
                'ui',
                $legacyResourceRoot,
                'larena/ui',
                $revision,
            );
    }

    public function id(): string
    {
        return 'framework.lock';
    }

    public function priority(): int
    {
        return 400;
    }

    public function namespaces(): array
    {
        return ['ui'];
    }

    public function descriptors(): iterable
    {
        $seen = [];
        foreach ($this->portable->descriptors() as $descriptor) {
            $seen[$descriptor->id] = true;
            if ($this->lock === null) {
                yield $descriptor;

                continue;
            }
            $this->lock->assertArtifact($descriptor->id);
            $artifact = $this->lock->artifact($descriptor->id);
            $packet = $this->lock->packetFor($descriptor->id);
            $runtimeAssets = [];
            foreach ($this->lock->runtimeAssets($descriptor->id) as $key => $asset) {
                $runtimeAssets[$key] = [
                    'path' => $asset['path'],
                    'kind' => $asset['kind'],
                    'public' => $asset['public'],
                    'version' => $asset['sha256'],
                    'root' => dirname($descriptor->root),
                    'head' => $asset['head'],
                ];
            }
            yield new SmartArtifactDescriptor(
                $descriptor->id,
                $descriptor->providerId,
                $descriptor->priority,
                $descriptor->ownerPackage,
                $descriptor->root,
                $descriptor->manifest,
                $descriptor->views,
                $descriptor->presets,
                $descriptor->templates,
                $descriptor->aliases,
                array_replace($descriptor->assets, $runtimeAssets),
                $descriptor->portableManifest,
                $descriptor->strategy,
                $descriptor->adapterId,
                array_replace($descriptor->provenance, [
                    'provider_revision' => (string) $packet['product_candidate'],
                    'owner_packet_content_sha256' => (string) $packet['packet_content_sha256'],
                    'owner_packet_file_sha256' => (string) $packet['packet_file_sha256'],
                    'owner_build_tree_sha256' => (string) $packet['build_tree_sha256'],
                    'artifact_tree_sha256' => (string) $artifact['artifact_tree_sha256'],
                    'support_status' => (string) $artifact['support'],
                    'dependencies' => $artifact['dependencies'],
                    'consumer_constraints' => $artifact['constraints'],
                    'nonclaims' => $artifact['nonclaims'] ?? [],
                ]),
            );
        }
        if ($this->legacy !== null) {
            foreach ($this->legacy->descriptors() as $descriptor) {
                if (isset($seen[$descriptor->id])) {
                    throw new SmartProviderException('SMART_FRAMEWORK_ARTIFACT_DUPLICATE', $descriptor->id);
                }
                $seen[$descriptor->id] = true;
                yield $descriptor;
            }
        }
    }

    public function fingerprint(): string
    {
        $records = [$this->id(), $this->revision, $this->lock?->fingerprint() ?? 'unlocked'];
        foreach ($this->descriptors() as $descriptor) {
            $records[] = $descriptor->id . ':' . $descriptor->provenance['manifest_sha256'];
        }

        return hash('sha256', implode("\n", $records));
    }
}
