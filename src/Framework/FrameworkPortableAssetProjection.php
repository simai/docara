<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Smart\SmartRegistry;

final readonly class FrameworkPortableAssetProjection
{
    public function __construct(private SmartRegistry $registry) {}

    /** @param list<string> $requiredKeys @return array<string, mixed> */
    public function forKeys(array $requiredKeys): array
    {
        $required = array_values(array_unique(array_filter(
            $requiredKeys,
            static fn (mixed $key): bool => is_string($key) && str_starts_with($key, 'framework.portable.'),
        )));
        sort($required, SORT_STRING);
        $available = $this->registry->assets();
        $files = [];
        foreach ($required as $key) {
            $asset = $available[$key] ?? null;
            if (! is_array($asset)
                || ! is_string($asset['public'] ?? null)
                || ! str_starts_with($asset['public'], 'framework/')
                || ! is_string($asset['version'] ?? null)
                || preg_match('/^[a-f0-9]{64}$/D', $asset['version']) !== 1
            ) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_PORTABLE_ASSET_PROJECTION_INVALID',
                    "Portable Framework asset [$key] is not an admitted exact-hash asset.",
                );
            }
            $relative = substr($asset['public'], strlen('framework/'));
            if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '..') || str_contains($relative, '\\')) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_PORTABLE_ASSET_PROJECTION_PATH_INVALID',
                    "Portable Framework asset [$key] has an unsafe public path.",
                );
            }
            if (isset($files[$relative])) {
                throw new PortableConfigurationException(
                    'FRAMEWORK_PORTABLE_ASSET_PROJECTION_COLLISION',
                    "Portable Framework assets collide at [$relative].",
                );
            }
            $files[$relative] = ['key' => $key, 'sha256' => $asset['version']];
        }
        ksort($files, SORT_STRING);

        return [
            'schema' => 'docara.framework_portable_asset_projection.v1',
            'files' => $files,
            'sha256' => hash('sha256', CanonicalJson::encode($files)),
        ];
    }
}
