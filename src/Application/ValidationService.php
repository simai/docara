<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Simai\Docara\Design\Artifact\DesignArtifactKind;
use Simai\Docara\Documentation\DocumentationSourceRepository;
use Simai\Docara\Documentation\DocumentationStatusService;

final readonly class ValidationService
{
    public function validate(string $root, string $kind, ?string $id = null): OperationResult
    {
        $runtime = ProjectRuntime::load($root);
        $checks = [];
        if ($kind === 'project') {
            $checks[] = $this->pass('PROJECT_REGISTRIES_VALID', count($runtime->smarts->keys()) + count($runtime->designs->all()));
            array_push($checks, ...(new PageInspectionService)->validateAll($runtime->root));
            if (($runtime->site['documentation_tracking']['enabled'] ?? false) === true) {
                $report = (new DocumentationStatusService)->report($runtime->root);
                $checks[] = ['code' => 'DOCUMENTATION_SOURCES_VALID', 'status' => 'pass', 'count' => count((new DocumentationSourceRepository)->all($runtime->root))];
                foreach ($report['items'] as $item) {
                    if (! in_array($item['status'], ['current', 'excluded'], true)) {
                        $checks[] = ['code' => 'DOCUMENTATION_' . strtoupper($item['status']), 'subject' => $item['source'] . ':' . $item['key'], 'status' => 'review_required'];
                    }
                }
            }
        } elseif ($kind === 'page') {
            if ($id === null) {
                throw new \InvalidArgumentException('SDK_ARGUMENT_REQUIRED:id');
            }
            $inspection = (new PageInspectionService)->inspect($runtime->root, $id);
            $checks[] = ['code' => 'PAGE_MARKDOWN_VALID', 'subject' => $id, 'status' => 'pass'];
            foreach ($inspection['diagnostics'] as $diagnostic) {
                $checks[] = $diagnostic + ['subject' => $id];
            }
        } elseif ($kind === 'source') {
            if ($id === null) {
                throw new \InvalidArgumentException('SDK_ARGUMENT_REQUIRED:id');
            }
            $parts = explode(':', $id, 2);
            $repository = new DocumentationSourceRepository;
            $source = $repository->source($runtime->root, $parts[0]);
            if (isset($parts[1])) {
                $repository->entity($runtime->root, $parts[0], $parts[1]);
            }
            $checks[] = ['code' => 'DOCUMENTATION_SOURCE_VALID', 'subject' => $id, 'status' => 'pass', 'revision' => $source['revision']];
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

        $data = [
            'checks' => $checks,
            'passed' => count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'pass')),
            'not_declared' => count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'not_declared')),
            'review_required' => count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'review_required')),
            'errors' => count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'error')),
        ];
        if ($data['errors'] > 0) {
            return new OperationResult('validate', 'error', 2, $id ?? $kind, $data, [], $runtime->provenance());
        }

        return OperationResult::success('validate', $id ?? $kind, $data, $runtime->provenance());
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
