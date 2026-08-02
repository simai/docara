<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;

final readonly class PortableProjectUpdater
{
    private string $packageRoot;

    public function __construct(private Filesystem $files, ?string $packageRoot = null)
    {
        $this->packageRoot = $packageRoot ?? dirname(__DIR__, 2);
    }

    /** @return array<string, mixed> */
    public function installEngineState(string $root): array
    {
        $root = $this->safeProjectRoot($root);
        $engine = $root . '/' . PortableOwnershipContract::ENGINE_ROOT;
        if ($this->files->exists($engine)) {
            throw new RuntimeException('Package-owned .docara/engine already exists; use the update command.');
        }
        $this->writeDirectory($engine, $this->desiredFiles($root));

        return $this->verify($root);
    }

    /** @return array<string, mixed> */
    public function verify(string $root): array
    {
        $root = $this->safeProjectRoot($root);
        $engine = $root . '/' . PortableOwnershipContract::ENGINE_ROOT;
        $current = $this->readAndVerifyEngine($engine);
        $desired = $this->desiredFiles($root);

        return [
            'schema' => 'docara.update_verification.v1',
            'status' => $this->directoryHash($current) === $this->directoryHash($desired) ? 'current' : 'update_available',
            'current_sha256' => $this->directoryHash($current),
            'desired_sha256' => $this->directoryHash($desired),
            'engine_files' => count($current),
            'project_owned_preserved' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function dryRun(string $root, bool $adopt = false): array
    {
        $root = $this->safeProjectRoot($root);
        $engine = $root . '/' . PortableOwnershipContract::ENGINE_ROOT;
        $exists = $this->files->isDirectory($engine) && ! is_link($engine);
        if (! $exists && ! $adopt) {
            throw new RuntimeException('Ownership state is missing. Re-run dry-run with --adopt to plan an explicit one-time adoption.');
        }
        $current = $exists ? $this->readAndVerifyEngine($engine) : [];
        $desired = $this->desiredFiles($root);
        $operations = $this->operations($current, $desired);
        $plan = [
            'schema' => 'docara.update_plan.v1',
            'action' => $exists ? 'update' : 'adopt',
            'current_sha256' => $this->directoryHash($current),
            'desired_sha256' => $this->directoryHash($desired),
            'project_markers_sha256' => $this->projectMarkersHash($root),
            'operations' => $operations,
            'project_owned_preserved' => true,
        ];
        $plan['plan_sha256'] = hash('sha256', CanonicalJson::encode($plan));
        $this->atomicPut($root . '/.docara/update-plan.json', CanonicalJson::encodePretty($plan));

        return $plan;
    }

    /** @return array<string, mixed> */
    public function apply(string $root): array
    {
        $root = $this->safeProjectRoot($root);
        $planPath = $root . '/.docara/update-plan.json';
        $plan = $this->jsonFile($planPath, 'Update plan is missing or invalid. Run update --dry-run first.');
        $planHash = (string) ($plan['plan_sha256'] ?? '');
        $unsigned = $plan;
        unset($unsigned['plan_sha256']);
        if ($planHash === '' || ! hash_equals($planHash, hash('sha256', CanonicalJson::encode($unsigned)))) {
            throw new RuntimeException('Update plan hash is invalid. Run update --dry-run again.');
        }

        $engine = $root . '/' . PortableOwnershipContract::ENGINE_ROOT;
        $current = $this->files->isDirectory($engine) && ! is_link($engine)
            ? $this->readAndVerifyEngine($engine)
            : [];
        $desired = $this->desiredFiles($root);
        if (! hash_equals((string) ($plan['current_sha256'] ?? ''), $this->directoryHash($current))
            || ! hash_equals((string) ($plan['desired_sha256'] ?? ''), $this->directoryHash($desired))
            || ! hash_equals((string) ($plan['project_markers_sha256'] ?? ''), $this->projectMarkersHash($root))) {
            throw new RuntimeException('Update inputs changed after dry-run. No files were changed; run update --dry-run again.');
        }

        $candidate = $root . '/.docara/update-candidate';
        $this->removeGeneratedDirectory($candidate, 'update candidate');
        $this->writeDirectory($candidate, $desired);
        $candidateFiles = $this->readAndVerifyEngine($candidate);
        if (! hash_equals($this->directoryHash($desired), $this->directoryHash($candidateFiles))) {
            throw new RuntimeException('Staged update candidate failed its content hash check.');
        }

        $id = gmdate('YmdHis') . '-' . substr($planHash, 0, 12);
        $rollback = $root . '/.docara/rollbacks/' . $id;
        if ($this->files->exists($rollback) || is_link($rollback)) {
            throw new RuntimeException("Rollback id collision [{$id}]. Re-run dry-run before applying.");
        }
        $this->files->ensureDirectoryExists($rollback);
        $frameworkBytes = (string) file_get_contents($root . '/simai-framework.lock.json');
        $manifest = [
            'schema' => 'docara.update_rollback.v1',
            'id' => $id,
            'action' => (string) ($plan['action'] ?? 'update'),
            'plan_sha256' => $planHash,
            'before_sha256' => $this->directoryHash($current),
            'after_sha256' => $this->directoryHash($desired),
            'framework_lock_sha256' => hash('sha256', $frameworkBytes),
            'status' => 'prepared',
        ];
        $this->atomicPut($rollback . '/manifest.json', CanonicalJson::encodePretty($manifest));
        $this->atomicPut($rollback . '/simai-framework.lock.json', $frameworkBytes);

        $hadCurrent = $this->files->isDirectory($engine) && ! is_link($engine);
        if ($hadCurrent && ! @rename($engine, $rollback . '/engine')) {
            $this->removeGeneratedDirectory($candidate, 'update candidate');
            throw new RuntimeException('Current package-owned state could not be moved into the rollback package.');
        }
        if (! @rename($candidate, $engine)) {
            if ($hadCurrent && ! @rename($rollback . '/engine', $engine)) {
                throw new RuntimeException('Update promotion failed and the previous package-owned state could not be restored.');
            }
            throw new RuntimeException('Update promotion failed; the previous package-owned state was restored.');
        }

        $manifest['status'] = 'applied';
        $this->atomicPut($rollback . '/manifest.json', CanonicalJson::encodePretty($manifest));
        $this->files->delete($planPath);

        return [
            'schema' => 'docara.update_result.v1',
            'status' => 'applied',
            'rollback_id' => $id,
            'operations' => $plan['operations'] ?? [],
            'engine_sha256' => $manifest['after_sha256'],
        ];
    }

    /** @return array<string, mixed> */
    public function rollback(string $root, string $id): array
    {
        $root = $this->safeProjectRoot($root);
        $rollbacks = $root . '/.docara/rollbacks';
        if ($id === 'latest') {
            $entries = array_values(array_filter($this->files->directories($rollbacks), static fn (string $path): bool => ! is_link($path)));
            rsort($entries, SORT_STRING);
            $id = isset($entries[0]) ? basename($entries[0]) : '';
        }
        if ($id === '' || preg_match('/^[0-9]{14}-[a-f0-9]{12}$/', $id) !== 1) {
            throw new RuntimeException('Rollback id is invalid or no rollback package is available.');
        }
        $rollback = $rollbacks . '/' . $id;
        if (is_link($rollback) || ! $this->files->isDirectory($rollback)) {
            throw new RuntimeException("Rollback package [{$id}] is missing or unsafe.");
        }
        $manifest = $this->jsonFile($rollback . '/manifest.json', "Rollback manifest [{$id}] is invalid.");
        if (($manifest['schema'] ?? null) !== 'docara.update_rollback.v1' || ($manifest['status'] ?? null) !== 'applied') {
            throw new RuntimeException("Rollback package [{$id}] is not in an applied state.");
        }
        $framework = (string) @file_get_contents($rollback . '/simai-framework.lock.json');
        if ($framework === '' || ! hash_equals((string) ($manifest['framework_lock_sha256'] ?? ''), hash('sha256', $framework))) {
            throw new RuntimeException("Rollback package [{$id}] has a corrupt Framework lock snapshot.");
        }

        $engine = $root . '/' . PortableOwnershipContract::ENGINE_ROOT;
        $current = $this->readAndVerifyEngine($engine);
        if (! hash_equals((string) ($manifest['after_sha256'] ?? ''), $this->directoryHash($current))) {
            throw new RuntimeException('Current package-owned state changed after apply; rollback is refused.');
        }
        $previousPath = $rollback . '/engine';
        $action = (string) ($manifest['action'] ?? 'update');
        $previous = [];
        if ($action !== 'adopt') {
            $previous = $this->readAndVerifyEngine($previousPath);
            if (! hash_equals((string) ($manifest['before_sha256'] ?? ''), $this->directoryHash($previous))) {
                throw new RuntimeException("Rollback package [{$id}] has corrupt previous package-owned files.");
            }
        } elseif ($this->files->exists($previousPath)) {
            throw new RuntimeException("Adoption rollback package [{$id}] contains unexpected previous files.");
        }

        $replaced = $rollback . '/replaced-engine';
        $this->removeGeneratedDirectory($replaced, 'replaced engine');
        if (! @rename($engine, $replaced)) {
            throw new RuntimeException('Current package-owned state could not be staged for rollback.');
        }
        if ($action !== 'adopt' && ! @rename($previousPath, $engine)) {
            if (! @rename($replaced, $engine)) {
                throw new RuntimeException('Rollback failed and the applied package-owned state could not be restored.');
            }
            throw new RuntimeException('Rollback failed; the applied package-owned state was restored.');
        }

        $manifest['status'] = 'rolled_back';
        $this->atomicPut($rollback . '/manifest.json', CanonicalJson::encodePretty($manifest));

        return [
            'schema' => 'docara.update_result.v1',
            'status' => 'rolled_back',
            'rollback_id' => $id,
            'engine_sha256' => $action === 'adopt' ? $this->directoryHash([]) : $this->directoryHash($previous),
        ];
    }

    /** @return array<string, string> */
    private function desiredFiles(string $root): array
    {
        return (new PortableOwnershipContract($this->packageRoot))->desiredFiles($root);
    }

    /** @return array<string, string> */
    private function readAndVerifyEngine(string $engine): array
    {
        if (is_link($engine) || ! $this->files->isDirectory($engine)) {
            throw new RuntimeException('Package-owned .docara/engine is missing or unsafe.');
        }
        $files = $this->directoryFiles($engine);
        $ownership = isset($files['ownership.json'])
            ? json_decode($files['ownership.json'], true, 512, JSON_THROW_ON_ERROR)
            : null;
        if (! is_array($ownership) || ($ownership['schema'] ?? null) !== 'docara.project_ownership.v1') {
            throw new RuntimeException('Package-owned ownership manifest is missing or invalid.');
        }
        $expected = is_array($ownership['engine_files'] ?? null) ? $ownership['engine_files'] : [];
        $actualPaths = array_keys($files);
        $allowedPaths = array_merge(array_keys($expected), ['ownership.json']);
        sort($actualPaths, SORT_STRING);
        sort($allowedPaths, SORT_STRING);
        if ($actualPaths !== $allowedPaths) {
            throw new RuntimeException('Unknown or missing package-owned file detected under .docara/engine.');
        }
        foreach ($expected as $relative => $sha256) {
            if (! is_string($relative) || ! is_string($sha256)
                || ! isset($files[$relative])
                || ! hash_equals($sha256, hash('sha256', $files[$relative]))) {
                throw new RuntimeException("Dirty package-owned file detected [.docara/engine/{$relative}].");
            }
        }

        return $files;
    }

    /** @return array<string, string> */
    private function directoryFiles(string $directory): array
    {
        if (is_link($directory) || ! $this->files->isDirectory($directory)) {
            throw new RuntimeException('Package-owned directory is missing or unsafe.');
        }
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new RuntimeException('Symbolic links are forbidden in package-owned state.');
            }
            if (! $file->isFile()) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($directory))), '/');
            $files[$relative] = (string) file_get_contents($file->getPathname());
        }
        ksort($files, SORT_STRING);

        return $files;
    }

    /** @param array<string, string> $files */
    private function writeDirectory(string $directory, array $files): void
    {
        if ($this->files->exists($directory) || is_link($directory)) {
            throw new RuntimeException("Refusing to overwrite existing directory [{$directory}].");
        }
        $this->files->ensureDirectoryExists($directory);
        foreach ($files as $relative => $bytes) {
            $path = $directory . '/' . $relative;
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $bytes);
        }
    }

    /** @param array<string, string> $current @param array<string, string> $desired @return list<array{action:string,path:string,before_sha256:?string,after_sha256:?string}> */
    private function operations(array $current, array $desired): array
    {
        $paths = array_values(array_unique(array_merge(array_keys($current), array_keys($desired))));
        sort($paths, SORT_STRING);
        $operations = [];
        foreach ($paths as $path) {
            $before = isset($current[$path]) ? hash('sha256', $current[$path]) : null;
            $after = isset($desired[$path]) ? hash('sha256', $desired[$path]) : null;
            if ($before === $after) {
                continue;
            }
            $operations[] = [
                'action' => $before === null ? 'add' : ($after === null ? 'delete' : 'update'),
                'path' => PortableOwnershipContract::ENGINE_ROOT . '/' . $path,
                'before_sha256' => $before,
                'after_sha256' => $after,
            ];
        }

        return $operations;
    }

    /** @param array<string, string> $files */
    private function directoryHash(array $files): string
    {
        $hashes = array_map(static fn (string $bytes): string => hash('sha256', $bytes), $files);
        ksort($hashes, SORT_STRING);

        return hash('sha256', CanonicalJson::encode($hashes));
    }

    private function projectMarkersHash(string $root): string
    {
        $records = [];
        foreach (['docara.json', 'redirects.json', 'simai-framework.lock.json'] as $relative) {
            $path = $root . '/' . $relative;
            if (! is_file($path) || is_link($path)) {
                throw new RuntimeException("Project-owned marker [{$relative}] is missing or unsafe.");
            }
            $records[$relative] = hash_file('sha256', $path);
        }

        return hash('sha256', CanonicalJson::encode($records));
    }

    private function safeProjectRoot(string $root): string
    {
        $root = rtrim($root, '/\\');
        if ($root === '' || is_link($root) || ($real = realpath($root)) === false || ! is_dir($real)) {
            throw new RuntimeException('Project root is missing or unsafe.');
        }
        foreach (['.docara', '.docara/engine', '.docara/rollbacks'] as $relative) {
            $path = $real . '/' . $relative;
            if (is_link($path)) {
                throw new RuntimeException("Symbolic link [{$relative}] is forbidden in update state.");
            }
        }

        return str_replace('\\', '/', $real);
    }

    /** @return array<string, mixed> */
    private function jsonFile(string $path, string $message): array
    {
        try {
            $value = json_decode((string) $this->files->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new RuntimeException($message, 0, $exception);
        }
        if (! is_array($value)) {
            throw new RuntimeException($message);
        }

        return $value;
    }

    private function atomicPut(string $path, string $bytes): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $temporary = $path . '.tmp';
        if (is_link($temporary)) {
            throw new RuntimeException('Refusing to replace a symbolic-link temporary update file.');
        }
        $this->files->put($temporary, $bytes);
        if (! @rename($temporary, $path)) {
            $this->files->delete($temporary);
            throw new RuntimeException("Could not atomically write [{$path}].");
        }
    }

    private function removeGeneratedDirectory(string $path, string $label): void
    {
        if (is_link($path)) {
            throw new RuntimeException("Refusing to remove symbolic-link {$label}.");
        }
        if ($this->files->isDirectory($path) && ! $this->files->deleteDirectory($path)) {
            throw new RuntimeException("Could not clean stale {$label}.");
        }
    }
}
