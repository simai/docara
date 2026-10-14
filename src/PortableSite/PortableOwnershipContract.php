<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Portable\CanonicalJson;

final readonly class PortableOwnershipContract
{
    public const ENGINE_ROOT = '.docara/engine';

    public function __construct(private string $packageRoot) {}

    /** @return array<string, string> relative engine path => bytes */
    public function desiredFiles(string $projectRoot): array
    {
        $runtime = new PortableRuntimeMetadata($this->packageRoot);
        $files = [
            'package-revision.json' => CanonicalJson::encodePretty($runtime->package()),
            'dependency-lock.json' => CanonicalJson::encodePretty($runtime->dependencies()),
            'update-contract.json' => CanonicalJson::encodePretty([
                'schema' => 'docara.update_contract.v1',
                'sequence' => ['verify', 'dry-run', 'apply'],
                'rollback' => 'hash_verified_directory_swap',
                'plan_required' => true,
                'project_owned_overwrite' => false,
            ]),
        ];

        $frameworkPath = rtrim($projectRoot, '/\\') . '/simai-framework.lock.json';
        if (! is_file($frameworkPath) || is_link($frameworkPath)) {
            throw new RuntimeException('Project-owned simai-framework.lock.json is missing or unsafe.');
        }
        $framework = json_decode((string) file_get_contents($frameworkPath), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($framework)) {
            throw new RuntimeException('Project-owned simai-framework.lock.json must contain an object.');
        }
        FrameworkLock::fromArray($framework);
        $files['framework-lock.json'] = CanonicalJson::encodePretty([
            'schema' => 'docara.project_framework_lock_snapshot.v1',
            'source' => 'simai-framework.lock.json',
            'sha256' => hash('sha256', (string) file_get_contents($frameworkPath)),
            'lock' => $framework,
        ]);

        $schemaRoot = $this->packageRoot . '/resources/schemas';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($schemaRoot, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($schemaRoot))), '/');
            $files['schemas/' . $relative] = (string) file_get_contents($file->getPathname());
        }
        ksort($files, SORT_STRING);

        $hashes = array_map(static fn (string $bytes): string => hash('sha256', $bytes), $files);
        $projectFiles = $this->starterProjectFiles();
        $ownership = [
            'schema' => 'docara.project_ownership.v1',
            'version' => 1,
            'engine_root' => self::ENGINE_ROOT,
            'owners' => [
                'engine' => ['.docara/engine/**'],
                'project' => ['content/**', 'assets/**', 'design/**', 'smart/**', 'docara.json', 'redirects.json', 'simai-framework.lock.json', 'snippets/**'],
                'generated' => ['build/**', 'build_*/**', '.docara-preview/**', 'var/cache/**', '.docara/update-plan.json', '.docara/rollbacks/**'],
            ],
            'engine_files' => $hashes,
            'starter_project_files' => $projectFiles,
            'policy' => [
                'project_owned_overwrite' => 'forbidden',
                'unknown_engine_file' => 'fail_closed',
                'dirty_engine_file' => 'fail_closed',
                'symlink' => 'fail_closed',
            ],
        ];
        $files['ownership.json'] = CanonicalJson::encodePretty($ownership);
        ksort($files, SORT_STRING);

        return $files;
    }

    /** @return array<string, array{owner:string,sha256:string}> */
    private function starterProjectFiles(): array
    {
        $stubs = $this->packageRoot . '/stubs/portable';
        if (! is_dir($stubs) || is_link($stubs)) {
            throw new RuntimeException('Portable starter is missing or unsafe.');
        }
        $records = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($stubs, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($stubs))), '/');
            if (str_starts_with($relative, '.docara/')) {
                throw new RuntimeException('Portable starter may not pre-own package state under .docara/.');
            }
            $records[$relative] = ['owner' => 'project', 'sha256' => hash_file('sha256', $file->getPathname())];
        }
        ksort($records, SORT_STRING);

        return $records;
    }
}
