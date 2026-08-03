<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Design\Artifact\DesignArtifactKind;

final readonly class ValidationService
{
    public function validate(string $root, string $kind, ?string $id = null): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $checks = [];
        if ($kind === 'project') {
            $checks[] = $this->pass('PROJECT_REGISTRIES_VALID', count($runtime->smarts->keys()) + count($runtime->designs->all()));
        } elseif ($kind === 'smart') {
            $ids = $id === null ? $runtime->smarts->keys() : [$id];
            foreach ($ids as $smartId) {
                $definition = $runtime->smarts->definition($smartId);
                $manifest = $definition->portableManifest;
                foreach ([
                    'manifest' => $definition->manifest !== [],
                    'props' => is_array($manifest['props'] ?? null),
                    'views' => $definition->views !== [],
                    'presets' => is_array($definition->presets),
                    'templates' => $definition->templates !== [],
                    'assets' => is_array($definition->assets),
                    'hydration' => is_string($manifest['render']['hydration'] ?? null),
                    'fixtures' => $this->declares($manifest, 'fixtures'),
                    'states' => $this->declares($manifest, 'states'),
                    'accessibility' => $this->declares($manifest, 'accessibility'),
                    'ai_guidance' => array_key_exists('ai', $manifest) && is_array($manifest['ai']),
                    'namespace' => str_contains($smartId, '.'),
                    'provenance' => $definition->provenance !== [],
                ] as $surface => $passed) {
                    $checks[] = ['code' => 'SMART_' . strtoupper($surface) . '_VALID', 'subject' => $smartId, 'status' => $passed ? 'pass' : 'not_declared'];
                }
            }
        } elseif (in_array($kind, ['layout', 'view', 'section', 'block'], true)) {
            $artifactKind = DesignArtifactKind::from($kind);
            $artifacts = $id === null ? $runtime->designs->all($artifactKind) : [$runtime->designs->get($artifactKind, $id)];
            foreach ($artifacts as $descriptor) {
                $checks[] = ['code' => 'DESIGN_ARTIFACT_VALID', 'subject' => $descriptor->id, 'status' => 'pass', 'sha256' => $descriptor->sha256];
            }
        } else {
            throw new \InvalidArgumentException('SDK_VALIDATION_KIND_UNKNOWN:' . $kind);
        }

        return OperationResult::success('validate', $id ?? $kind, [
            'checks' => $checks,
            'passed' => count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'pass')),
            'not_declared' => count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'not_declared')),
        ], $runtime->provenance());
    }

    /** @return array{code:string,status:string,count:int} */
    private function pass(string $code, int $count): array
    {
        return ['code' => $code, 'status' => 'pass', 'count' => $count];
    }

    /** @param array<string, mixed> $manifest */
    private function declares(array $manifest, string $field): bool
    {
        return (array_key_exists($field, $manifest) && is_array($manifest[$field]))
            || (is_array($manifest['ai'] ?? null) && array_key_exists($field, $manifest['ai']) && is_array($manifest['ai'][$field]));
    }
}
