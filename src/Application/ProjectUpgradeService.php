<?php

declare(strict_types=1);

namespace Simai\Docara\Application;

use Closure;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\File\Filesystem;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\SchemaRepository;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class ProjectUpgradeService
{
    private const PLAN_SCHEMA = 'docara.upgrade_plan.v1';

    private const JOURNAL_SCHEMA = 'docara.upgrade_journal.v1';

    private readonly ?Closure $candidatePreparer;

    private readonly ?Closure $candidateVerifier;

    public function __construct(
        private readonly Filesystem $files = new Filesystem,
        private readonly string $packageRoot = __DIR__ . '/../..',
        ?callable $candidatePreparer = null,
        ?callable $candidateVerifier = null,
        private readonly ?string $composerBinary = null,
    ) {
        $this->candidatePreparer = $candidatePreparer === null ? null : Closure::fromCallable($candidatePreparer);
        $this->candidateVerifier = $candidateVerifier === null ? null : Closure::fromCallable($candidateVerifier);
    }

    public function check(string $root, ?string $target = null): OperationResult
    {
        $plan = $this->prepare($root, $target);

        return OperationResult::success('upgrade.check', $plan['target_version'] ?? $plan['current_version'], $plan, $this->provenance($plan));
    }

    public function plan(string $root, ?string $target = null): OperationResult
    {
        $plan = $this->prepare($root, $target);

        return OperationResult::success('upgrade.plan', $plan['plan_sha256'] ?? $plan['current_version'], $plan, $this->provenance($plan));
    }

    public function upgrade(string $root, ?string $target = null): OperationResult
    {
        $plan = $this->prepare($root, $target);
        if (($plan['status'] ?? null) === 'current') {
            return OperationResult::success('upgrade', (string) $plan['current_version'], $plan, $this->provenance($plan));
        }

        return $this->apply($root, (string) $plan['plan_sha256']);
    }

    public function apply(string $root, string $planId): OperationResult
    {
        $root = $this->projectRoot($root);
        $plan = $this->findPlan($root, $planId);
        $this->assertPlan($plan, $planId);
        $this->assertNoActiveUpgrade($root, (string) $plan['upgrade_id']);
        $this->assertPlanInputsCurrent($root, $plan);
        $upgradeRoot = $root . '/.docara/upgrades/' . $plan['upgrade_id'];
        $candidate = $upgradeRoot . '/candidate';
        $rollback = $upgradeRoot . '/rollback';
        $this->assertSafeDirectory($candidate, 'upgrade candidate');
        foreach (['composer.lock', 'vendor/bin/docara'] as $relative) {
            if (! is_file($candidate . '/' . $relative) || is_link($candidate . '/' . $relative)) {
                throw new RuntimeException('UPGRADE_CANDIDATE_INCOMPLETE:Candidate dependency runtime is incomplete.');
            }
        }
        if (! is_dir($upgradeRoot . '/verified-build') || is_link($upgradeRoot . '/verified-build')) {
            throw new RuntimeException('UPGRADE_CANDIDATE_INCOMPLETE:Verified candidate build is missing.');
        }
        if ($this->treeHash($upgradeRoot . '/verified-build') !== $plan['verified_build_sha256']) {
            throw new RuntimeException('UPGRADE_PLAN_STALE:Verified candidate build changed after planning.');
        }

        $this->makeDirectory($rollback);
        $this->copyRegularFile($root . '/composer.lock', $rollback . '/composer.lock');
        $this->copyTree($root . '/.docara/engine', $rollback . '/engine');
        if (is_dir($root . '/build_production') && ! is_link($root . '/build_production')) {
            $this->copyTree($root . '/build_production', $rollback . '/build_production');
        }
        $journal = [
            'schema' => self::JOURNAL_SCHEMA,
            'upgrade_id' => $plan['upgrade_id'],
            'plan_sha256' => $planId,
            'current_version' => $plan['current_version'],
            'target_version' => $plan['target_version'],
            'phase' => 'prepared',
            'completed_phases' => ['candidate_verified', 'rollback_prepared'],
            'rollback_available' => true,
        ];
        $this->writeJournal($upgradeRoot, $journal);

        try {
            $journal = $this->advance($upgradeRoot, $journal, 'dependencies_promoted');
            if (! @rename($root . '/vendor', $rollback . '/vendor')) {
                throw new RuntimeException('UPGRADE_PROMOTION_FAILED:Current vendor could not be staged for rollback.');
            }
            if (! @rename($candidate . '/vendor', $root . '/vendor')) {
                @rename($rollback . '/vendor', $root . '/vendor');
                throw new RuntimeException('UPGRADE_PROMOTION_FAILED:Candidate vendor could not be promoted.');
            }
            $this->atomicCopy($candidate . '/composer.lock', $root . '/composer.lock');

            $journal = $this->advance($upgradeRoot, $journal, 'engine_synchronized');
            $this->runDocara($root . '/vendor/bin/docara', $root, ['update', '--dry-run', '--json']);
            $this->runDocara($root . '/vendor/bin/docara', $root, ['update', '--apply', '--json']);

            $journal = $this->advance($upgradeRoot, $journal, 'build_promoted');
            $replacedBuild = $upgradeRoot . '/replaced-build';
            if (is_dir($root . '/build_production') && ! @rename($root . '/build_production', $replacedBuild)) {
                throw new RuntimeException('UPGRADE_PROMOTION_FAILED:Previous verified build could not be staged.');
            }
            if (! @rename($upgradeRoot . '/verified-build', $root . '/build_production')) {
                if (is_dir($replacedBuild)) {
                    @rename($replacedBuild, $root . '/build_production');
                }
                throw new RuntimeException('UPGRADE_PROMOTION_FAILED:Verified candidate build could not be promoted.');
            }
            if (is_dir($replacedBuild) && ! is_dir($rollback . '/build_production')) {
                @rename($replacedBuild, $rollback . '/build_production');
            } elseif (is_dir($replacedBuild)) {
                $this->files->deleteDirectory($replacedBuild);
            }

            $journal = $this->advance($upgradeRoot, $journal, 'applied');
            $journal['after'] = [
                'composer_lock_sha256' => hash_file('sha256', $root . '/composer.lock'),
                'vendor_sha256' => $this->treeHash($root . '/vendor'),
                'engine_sha256' => $this->treeHash($root . '/.docara/engine'),
                'build_sha256' => $this->treeHash($root . '/build_production'),
            ];
            $this->writeJournal($upgradeRoot, $journal);
        } catch (\Throwable $exception) {
            $this->compensate($root, $upgradeRoot, $rollback);
            $journal['phase'] = 'compensated';
            $journal['completed_phases'][] = 'compensated';
            $journal['failure_code'] = $this->errorCode($exception);
            $this->writeJournal($upgradeRoot, $journal);
            throw $exception;
        }

        $result = [
            'schema' => 'docara.upgrade_result.v1',
            'status' => 'applied',
            'upgrade_id' => $plan['upgrade_id'],
            'rollback_id' => $plan['upgrade_id'],
            'from_version' => $plan['current_version'],
            'to_version' => $plan['target_version'],
            'plan_sha256' => $planId,
            'build_sha256' => $journal['after']['build_sha256'],
            'project_owned_preserved' => true,
        ];
        (new SchemaRepository($this->packageRoot . '/resources/schemas'))->assertValid($result, 'upgrade-result.schema.json');

        return OperationResult::success('upgrade.apply', (string) $plan['target_version'], $result, $this->provenance($plan));
    }

    public function rollback(string $root, string $id): OperationResult
    {
        $root = $this->projectRoot($root);
        $upgradeRoot = $this->resolveRollback($root, $id);
        $journal = $this->jsonFile($upgradeRoot . '/journal.json', 'UPGRADE_JOURNAL_INVALID:Upgrade journal is missing or invalid.');
        if (($journal['schema'] ?? null) !== self::JOURNAL_SCHEMA || in_array($journal['phase'] ?? null, ['rolled_back', 'compensated'], true)) {
            throw new RuntimeException('UPGRADE_ROLLBACK_UNAVAILABLE:Upgrade has no restorable transaction.');
        }
        if (($journal['phase'] ?? null) === 'applied') {
            $after = is_array($journal['after'] ?? null) ? $journal['after'] : [];
            foreach (['composer_lock_sha256' => $root . '/composer.lock', 'vendor_sha256' => $root . '/vendor', 'engine_sha256' => $root . '/.docara/engine', 'build_sha256' => $root . '/build_production'] as $key => $path) {
                $actual = is_dir($path) ? $this->treeHash($path) : (hash_file('sha256', $path) ?: '');
                if (! is_string($after[$key] ?? null) || ! hash_equals($after[$key], $actual)) {
                    throw new RuntimeException('UPGRADE_ROLLBACK_STALE:Applied runtime changed after upgrade; rollback is refused.');
                }
            }
        }

        $rollback = $upgradeRoot . '/rollback';
        $this->compensate($root, $upgradeRoot, $rollback, true);
        $journal['phase'] = 'rolled_back';
        $journal['completed_phases'][] = 'rolled_back';
        $this->writeJournal($upgradeRoot, $journal);
        $result = [
            'schema' => 'docara.upgrade_result.v1',
            'status' => 'rolled_back',
            'upgrade_id' => $journal['upgrade_id'],
            'rollback_id' => $journal['upgrade_id'],
            'from_version' => $journal['target_version'],
            'to_version' => $journal['current_version'],
            'plan_sha256' => $journal['plan_sha256'],
            'build_sha256' => is_dir($root . '/build_production') ? $this->treeHash($root . '/build_production') : null,
            'project_owned_preserved' => true,
        ];
        (new SchemaRepository($this->packageRoot . '/resources/schemas'))->assertValid($result, 'upgrade-result.schema.json');

        return OperationResult::success('upgrade.rollback', (string) $journal['current_version'], $result, ['upgrade_id' => $journal['upgrade_id']]);
    }

    /** @return array<string, mixed> */
    private function prepare(string $root, ?string $target): array
    {
        $root = $this->projectRoot($root);
        $this->assertNoActiveUpgrade($root);
        $this->exactTarget($target);
        $current = $this->lockedVersion($root . '/composer.lock');
        if ($target !== null && $this->stableVersion($target, 'UPGRADE_TARGET_VERSION_INVALID')[0] !== $this->stableVersion($current, 'UPGRADE_CURRENT_VERSION_INVALID')[0]) {
            throw new RuntimeException('MAJOR_UPGRADE_REQUIRED:Automatic upgrade is limited to the current major; prepare a separate migration report.');
        }
        $constraint = $this->composerConstraint($root);
        $inputs = $this->inputHashes($root);
        $seed = hash('sha256', CanonicalJson::encode([$inputs, $target, $current]));
        $upgradeId = $seed;
        $upgradeRoot = $root . '/.docara/upgrades/' . $upgradeId;
        if (is_dir($upgradeRoot) && ! is_link($upgradeRoot)) {
            if (is_file($upgradeRoot . '/plan.json') && ! is_link($upgradeRoot . '/plan.json')) {
                $existing = $this->jsonFile($upgradeRoot . '/plan.json', 'UPGRADE_PLAN_INVALID:Upgrade plan is invalid.');
                $existingId = is_string($existing['plan_sha256'] ?? null) ? $existing['plan_sha256'] : '';
                $this->assertPlan($existing, $existingId);
                $this->assertPlanInputsCurrent($root, $existing);

                return $existing;
            }
            if (is_file($upgradeRoot . '/journal.json')) {
                throw new RuntimeException('UPGRADE_INCOMPLETE:An existing upgrade transaction must be rolled back before retrying.');
            }
            if (! $this->files->deleteDirectory($upgradeRoot)) {
                throw new RuntimeException('UPGRADE_STATE_CLEANUP_FAILED:Incomplete generated candidate could not be cleaned.');
            }
        } elseif (is_link($upgradeRoot)) {
            throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Symbolic-link upgrade directory is forbidden.');
        }
        $candidate = $upgradeRoot . '/candidate';
        $this->makeDirectory($candidate);
        $this->copyRegularFile($root . '/composer.json', $candidate . '/composer.json');
        $this->copyRegularFile($root . '/composer.lock', $candidate . '/composer.lock');

        $candidateData = $this->candidatePreparer !== null
            ? ($this->candidatePreparer)($root, $candidate, $target)
            : $this->prepareComposerCandidate($root, $candidate, $target);
        if (! is_array($candidateData)) {
            throw new RuntimeException('UPGRADE_CANDIDATE_INVALID:Candidate preparer returned an invalid result.');
        }
        $targetVersion = $this->lockedVersion($candidate . '/composer.lock');
        $this->assertCompatibleTarget($current, $targetVersion, $target);
        if ($targetVersion === $current) {
            $currentPlan = [
                'schema' => self::PLAN_SCHEMA,
                'status' => 'current',
                'current_version' => $current,
                'target_version' => $targetVersion,
                'project_owned_preserved' => true,
            ];
            (new SchemaRepository($this->packageRoot . '/resources/schemas'))->assertValid($currentPlan, 'upgrade-plan.schema.json');

            return $currentPlan;
        }

        $verified = $this->candidateVerifier !== null
            ? ($this->candidateVerifier)($root, $candidate, $upgradeRoot)
            : $this->verifyCandidate($root, $candidate, $upgradeRoot);
        if (! is_array($verified) || ! is_dir($upgradeRoot . '/verified-build')) {
            throw new RuntimeException('UPGRADE_CANDIDATE_INVALID:Candidate verification did not produce a verified build.');
        }
        $plan = [
            'schema' => self::PLAN_SCHEMA,
            'status' => 'upgrade_available',
            'upgrade_id' => $upgradeId,
            'current_version' => $current,
            'target_version' => $targetVersion,
            'composer_constraint' => $constraint,
            'inputs' => $inputs,
            'candidate_lock_sha256' => hash_file('sha256', $candidate . '/composer.lock'),
            'dependency_graph_sha256' => $this->dependencyGraphHash($candidate . '/composer.lock'),
            'candidate_vendor_sha256' => $this->treeHash($candidate . '/vendor'),
            'verified_build_sha256' => $this->treeHash($upgradeRoot . '/verified-build'),
            'verification' => $verified,
            'project_owned_preserved' => true,
        ];
        $plan['plan_sha256'] = hash('sha256', CanonicalJson::encode($plan));
        (new SchemaRepository($this->packageRoot . '/resources/schemas'))->assertValid($plan, 'upgrade-plan.schema.json');
        $this->atomicPut($upgradeRoot . '/plan.json', CanonicalJson::encodePretty($plan));

        return $plan;
    }

    /** @return array<string, mixed> */
    private function prepareComposerCandidate(string $root, string $candidate, ?string $target): array
    {
        $composerJson = $this->jsonFile($candidate . '/composer.json', 'UPGRADE_COMPOSER_INVALID:composer.json is invalid.');
        foreach ($composerJson['repositories'] ?? [] as $index => $repository) {
            if (! is_array($repository) || ($repository['type'] ?? null) !== 'path' || ! is_string($repository['url'] ?? null)) {
                continue;
            }
            if (! $this->absolutePath($repository['url'])) {
                $composerJson['repositories'][$index]['url'] = $root . '/' . ltrim($repository['url'], '/\\');
            }
        }
        $this->atomicPut($candidate . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
        $command = [$this->composer(), 'update', 'simai/docara', '--with-all-dependencies', '--no-interaction', '--prefer-dist', '--no-progress', '--no-ansi'];
        if ($target !== null) {
            $command[] = '--with';
            $command[] = 'simai/docara:' . $target;
        }
        $this->run($command, $candidate, 900, 'UPGRADE_COMPOSER_FAILED');

        return ['composer' => 'resolved', 'network_allowed' => true];
    }

    /** @return array<string, mixed> */
    private function verifyCandidate(string $root, string $candidate, string $upgradeRoot): array
    {
        $verification = $upgradeRoot . '/verification-project';
        $this->copyProject($root, $verification);
        $this->atomicCopy($candidate . '/composer.json', $verification . '/composer.json');
        $this->atomicCopy($candidate . '/composer.lock', $verification . '/composer.lock');
        $binary = $candidate . '/vendor/bin/docara';
        $checks = [
            'doctor' => ['doctor', '--json'],
            'validate_project' => ['validate', 'project', '--json'],
            'engine_plan' => ['update', '--dry-run', '--json'],
            'engine_apply' => ['update', '--apply', '--json'],
            'build_production' => ['build', 'production'],
            'verify_static' => ['verify-static', 'build_production'],
        ];
        $results = [];
        foreach ($checks as $id => $arguments) {
            $results[$id] = $this->runDocara($binary, $verification, $arguments);
        }
        if (! @rename($verification . '/build_production', $upgradeRoot . '/verified-build')) {
            throw new RuntimeException('UPGRADE_CANDIDATE_INVALID:Candidate verified build could not be retained.');
        }

        return ['checks' => array_keys($checks), 'outputs_sha256' => hash('sha256', CanonicalJson::encode($results))];
    }

    /** @return array<string, string> */
    private function inputHashes(string $root): array
    {
        $inputs = [
            'composer_json_sha256' => hash_file('sha256', $root . '/composer.json'),
            'composer_lock_sha256' => hash_file('sha256', $root . '/composer.lock'),
            'project_owned_sha256' => $this->projectOwnedHash($root),
            'engine_sha256' => $this->treeHash($root . '/.docara/engine'),
            'build_sha256' => is_dir($root . '/build_production') && ! is_link($root . '/build_production') ? $this->treeHash($root . '/build_production') : hash('sha256', 'absent'),
        ];
        ksort($inputs, SORT_STRING);

        return $inputs;
    }

    private function projectOwnedHash(string $root): string
    {
        $records = [];
        $casePaths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            $relative = ltrim(substr($path, strlen($root)), '/');
            if ($this->excludedProjectPath($relative)) {
                continue;
            }
            if ($file->isLink()) {
                throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Symbolic links are forbidden in upgrade inputs.');
            }
            if (! $file->isFile()) {
                continue;
            }
            $caseKey = strtolower($relative);
            if (isset($casePaths[$caseKey]) && $casePaths[$caseKey] !== $relative) {
                throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Case-conflicting paths are forbidden in upgrade inputs.');
            }
            $casePaths[$caseKey] = $relative;
            $stat = $file->getPathname() === '' ? false : @stat($file->getPathname());
            if (is_array($stat) && ($stat['nlink'] ?? 1) > 1) {
                throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Hardlinked files are forbidden in upgrade inputs.');
            }
            $records[$relative] = hash_file('sha256', $file->getPathname());
        }
        ksort($records, SORT_STRING);

        return hash('sha256', CanonicalJson::encode($records));
    }

    private function excludedProjectPath(string $relative): bool
    {
        return $relative === '.git' || str_starts_with($relative, '.git/')
            || $relative === 'vendor' || str_starts_with($relative, 'vendor/')
            || preg_match('#^build_[^/]+(?:/|$)#', $relative) === 1
            || $relative === '.docara/upgrades' || str_starts_with($relative, '.docara/upgrades/')
            || $relative === '.docara/rollbacks' || str_starts_with($relative, '.docara/rollbacks/')
            || $relative === '.docara/sdk-plans' || str_starts_with($relative, '.docara/sdk-plans/')
            || str_starts_with($relative, '.docara/update-')
            || $relative === '.docara/engine' || str_starts_with($relative, '.docara/engine/');
    }

    private function excludedCopyPath(string $relative): bool
    {
        return $relative === '.git' || str_starts_with($relative, '.git/')
            || $relative === 'vendor' || str_starts_with($relative, 'vendor/')
            || preg_match('#^build_[^/]+(?:/|$)#', $relative) === 1
            || $relative === '.docara/upgrades' || str_starts_with($relative, '.docara/upgrades/')
            || $relative === '.docara/rollbacks' || str_starts_with($relative, '.docara/rollbacks/')
            || $relative === '.docara/sdk-plans' || str_starts_with($relative, '.docara/sdk-plans/')
            || str_starts_with($relative, '.docara/update-');
    }

    private function assertPlanInputsCurrent(string $root, array $plan): void
    {
        $actual = $this->inputHashes($root);
        if (! is_array($plan['inputs'] ?? null) || ! hash_equals(hash('sha256', CanonicalJson::encode($plan['inputs'])), hash('sha256', CanonicalJson::encode($actual)))) {
            throw new RuntimeException('UPGRADE_PLAN_STALE:Project inputs changed after planning.');
        }
    }

    private function projectRoot(string $root): string
    {
        if (is_link($root) || ($real = realpath($root)) === false || ! is_dir($real)) {
            throw new RuntimeException('UPGRADE_PROJECT_UNSAFE:Project root is missing or unsafe.');
        }
        $root = str_replace('\\', '/', $real);
        foreach (['composer.json', 'composer.lock', 'vendor/bin/docara', 'docara.json', 'simai-framework.lock.json', '.docara/engine'] as $relative) {
            $path = $root . '/' . $relative;
            if ($relative === '.docara/engine' && ! is_dir($path) && ! is_link($path)) {
                throw new RuntimeException('UPGRADE_ENGINE_ADOPTION_REQUIRED:This pre-manifest project needs one explicit engine adoption. Run `php vendor/bin/docara update --dry-run --adopt --json`, review the plan, then run `php vendor/bin/docara update --apply --json` and retry upgrade.');
            }
            if ((str_ends_with($relative, 'engine') ? ! is_dir($path) : ! is_file($path)) || is_link($path)) {
                if (str_starts_with($relative, 'composer') || str_starts_with($relative, 'vendor/')) {
                    throw new RuntimeException('PROJECT_LOCAL_RUNTIME_REQUIRED:Create project-local composer.json, composer.lock and vendor with `composer require simai/docara:^2.0`, then run upgrade again. The legacy engine is left untouched.');
                }
                throw new RuntimeException('UPGRADE_PROJECT_INVALID:Required project marker [' . $relative . '] is missing or unsafe.');
            }
        }
        foreach (['.docara', '.docara/engine', '.docara/upgrades'] as $relative) {
            if (is_link($root . '/' . $relative)) {
                throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Symbolic links are forbidden in upgrade state.');
            }
        }

        return $root;
    }

    private function assertCompatibleTarget(string $current, string $target, ?string $requested): void
    {
        $currentParts = $this->stableVersion($current, 'UPGRADE_CURRENT_VERSION_INVALID');
        $targetParts = $this->stableVersion($target, 'UPGRADE_TARGET_VERSION_INVALID');
        if ($requested !== null && $target !== $requested) {
            throw new RuntimeException('UPGRADE_TARGET_RESOLUTION_MISMATCH:Composer did not resolve the exact requested version.');
        }
        if ($targetParts[0] !== $currentParts[0]) {
            throw new RuntimeException('MAJOR_UPGRADE_REQUIRED:Automatic upgrade is limited to the current major; prepare a separate migration report.');
        }
        if (version_compare($target, $current, '<')) {
            throw new RuntimeException('UPGRADE_DOWNGRADE_FORBIDDEN:Upgrade cannot select an older version.');
        }
    }

    private function exactTarget(?string $target): void
    {
        if ($target !== null) {
            $this->stableVersion($target, 'UPGRADE_TARGET_VERSION_INVALID');
        }
    }

    /** @return array{int,int,int} */
    private function stableVersion(string $version, string $code): array
    {
        if (preg_match('/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D', $version, $matches) !== 1) {
            throw new RuntimeException($code . ':Only an exact stable X.Y.Z version is allowed.');
        }

        return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
    }

    private function lockedVersion(string $path): string
    {
        $lock = $this->jsonFile($path, 'UPGRADE_COMPOSER_LOCK_INVALID:composer.lock is invalid.');
        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            if (is_array($package) && ($package['name'] ?? null) === 'simai/docara' && is_string($package['version'] ?? null)) {
                return ltrim($package['version'], 'v');
            }
        }

        throw new RuntimeException('PROJECT_LOCAL_RUNTIME_REQUIRED:composer.lock does not contain an exact simai/docara package.');
    }

    private function composerConstraint(string $root): string
    {
        $composer = $this->jsonFile($root . '/composer.json', 'UPGRADE_COMPOSER_INVALID:composer.json is invalid.');
        $constraint = $composer['require']['simai/docara'] ?? null;
        if (! is_string($constraint) || trim($constraint) === '') {
            throw new RuntimeException('PROJECT_LOCAL_RUNTIME_REQUIRED:composer.json must require simai/docara.');
        }

        return $constraint;
    }

    private function dependencyGraphHash(string $lockPath): string
    {
        $lock = $this->jsonFile($lockPath, 'UPGRADE_COMPOSER_LOCK_INVALID:composer.lock is invalid.');
        $packages = [];
        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            if (! is_array($package) || ! is_string($package['name'] ?? null) || ! is_string($package['version'] ?? null)) {
                continue;
            }
            $packages[$package['name']] = ['version' => $package['version'], 'reference' => $package['dist']['reference'] ?? $package['source']['reference'] ?? null];
        }
        ksort($packages, SORT_STRING);

        return hash('sha256', CanonicalJson::encode($packages));
    }

    private function treeHash(string $directory): string
    {
        $this->assertSafeDirectory($directory, 'hashed directory');
        $records = [];
        $casePaths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Symbolic links are forbidden in upgrade state.');
            }
            if ($file->isFile()) {
                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($directory))), '/');
                $caseKey = strtolower($relative);
                if (isset($casePaths[$caseKey]) && $casePaths[$caseKey] !== $relative) {
                    throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Case-conflicting paths are forbidden in upgrade state.');
                }
                $casePaths[$caseKey] = $relative;
                $stat = @stat($file->getPathname());
                if (is_array($stat) && ($stat['nlink'] ?? 1) > 1) {
                    throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Hardlinked files are forbidden in upgrade state.');
                }
                $records[$relative] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($records, SORT_STRING);

        return hash('sha256', CanonicalJson::encode($records));
    }

    private function copyProject(string $source, string $target): void
    {
        $this->makeDirectory($target);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($source))), '/');
            if ($relative === '' || $this->excludedCopyPath($relative)) {
                continue;
            }
            $destination = $target . '/' . $relative;
            if ($file->isLink()) {
                throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Symbolic links are forbidden in upgrade inputs.');
            }
            if ($file->isDir()) {
                $this->files->ensureDirectoryExists($destination);
            } elseif ($file->isFile()) {
                $this->files->ensureDirectoryExists(dirname($destination));
                $this->files->copy($file->getPathname(), $destination);
            }
        }
    }

    private function copyTree(string $source, string $target): void
    {
        $this->assertSafeDirectory($source, 'copy source');
        $this->treeHash($source);
        if (file_exists($target) || is_link($target)) {
            throw new RuntimeException('UPGRADE_STATE_COLLISION:Refusing to overwrite staged upgrade state.');
        }
        if (! $this->files->copyDirectory($source, $target)) {
            throw new RuntimeException('UPGRADE_STATE_COPY_FAILED:Could not retain rollback state.');
        }
    }

    private function compensate(string $root, string $upgradeRoot, string $rollback, bool $fromApplied = false): void
    {
        $failed = $upgradeRoot . '/failed-' . gmdate('His');
        $this->files->ensureDirectoryExists($failed);
        foreach (['vendor' => 'vendor', '.docara/engine' => 'engine', 'build_production' => 'build_production'] as $current => $backup) {
            $currentPath = $root . '/' . $current;
            $backupPath = $rollback . '/' . $backup;
            if (file_exists($backupPath) && ! is_link($backupPath)) {
                if (file_exists($currentPath) && ! is_link($currentPath)) {
                    @rename($currentPath, $failed . '/' . str_replace('/', '-', $current));
                }
                @rename($backupPath, $currentPath);
            }
        }
        if (is_file($rollback . '/composer.lock') && ! is_link($rollback . '/composer.lock')) {
            $this->atomicCopy($rollback . '/composer.lock', $root . '/composer.lock');
        }
        if (! $fromApplied && ! is_dir($root . '/vendor')) {
            throw new RuntimeException('UPGRADE_COMPENSATION_FAILED:Previous dependency runtime could not be restored.');
        }
    }

    /** @return array<string, mixed> */
    private function findPlan(string $root, string $planId): array
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $planId) !== 1) {
            throw new RuntimeException('UPGRADE_PLAN_ID_INVALID:Apply requires the exact SHA-256 returned by dry-run.');
        }
        foreach ($this->upgradeDirectories($root) as $directory) {
            $path = $directory . '/plan.json';
            if (! is_link($directory) && is_file($path) && ! is_link($path)) {
                $plan = $this->jsonFile($path, 'UPGRADE_PLAN_INVALID:Upgrade plan is invalid.');
                if (($plan['plan_sha256'] ?? null) === $planId) {
                    return $plan;
                }
            }
        }

        throw new RuntimeException('UPGRADE_PLAN_NOT_FOUND:Upgrade plan was not found.');
    }

    private function assertPlan(array $plan, string $planId): void
    {
        (new SchemaRepository($this->packageRoot . '/resources/schemas'))->assertValid($plan, 'upgrade-plan.schema.json');
        $unsigned = $plan;
        unset($unsigned['plan_sha256']);
        if (! hash_equals($planId, hash('sha256', CanonicalJson::encode($unsigned)))) {
            throw new RuntimeException('UPGRADE_PLAN_HASH_MISMATCH:Upgrade plan contents do not match its id.');
        }
    }

    private function assertNoActiveUpgrade(string $root, ?string $allowed = null): void
    {
        foreach ($this->upgradeDirectories($root) as $directory) {
            if (is_link($directory) || ! is_file($directory . '/journal.json')) {
                continue;
            }
            $journal = $this->jsonFile($directory . '/journal.json', 'UPGRADE_JOURNAL_INVALID:Upgrade journal is invalid.');
            if (($journal['upgrade_id'] ?? null) !== $allowed && ! in_array($journal['phase'] ?? null, ['applied', 'rolled_back', 'compensated'], true)) {
                throw new RuntimeException('UPGRADE_INCOMPLETE:An unfinished upgrade must be rolled back before another upgrade starts.');
            }
        }
    }

    private function resolveRollback(string $root, string $id): string
    {
        $directories = array_values(array_filter($this->upgradeDirectories($root), static fn (string $path): bool => ! is_link($path)));
        usort($directories, static fn (string $left, string $right): int => [filemtime($right . '/journal.json') ?: 0, $right] <=> [filemtime($left . '/journal.json') ?: 0, $left]);
        if ($id === 'latest') {
            foreach ($directories as $directory) {
                if (is_file($directory . '/journal.json')) {
                    $journal = $this->jsonFile($directory . '/journal.json', 'UPGRADE_JOURNAL_INVALID:Upgrade journal is invalid.');
                    if (! in_array($journal['phase'] ?? null, ['rolled_back', 'compensated'], true)) {
                        return $directory;
                    }
                }
            }
            throw new RuntimeException('UPGRADE_ROLLBACK_UNAVAILABLE:No applied upgrade is available.');
        }
        if (preg_match('/^[a-f0-9]{64}$/D', $id) !== 1) {
            throw new RuntimeException('UPGRADE_ROLLBACK_ID_INVALID:Rollback id is invalid.');
        }
        $path = $root . '/.docara/upgrades/' . $id;
        $this->assertSafeDirectory($path, 'upgrade rollback');

        return $path;
    }

    /** @param array<string, mixed> $journal @return array<string, mixed> */
    private function advance(string $upgradeRoot, array $journal, string $phase): array
    {
        $journal['phase'] = $phase;
        $journal['completed_phases'][] = $phase;
        $this->writeJournal($upgradeRoot, $journal);

        return $journal;
    }

    /** @param array<string, mixed> $journal */
    private function writeJournal(string $upgradeRoot, array $journal): void
    {
        (new SchemaRepository($this->packageRoot . '/resources/schemas'))->assertValid($journal, 'upgrade-journal.schema.json');
        $this->atomicPut($upgradeRoot . '/journal.json', CanonicalJson::encodePretty($journal));
    }

    /** @return array<string, mixed> */
    private function jsonFile(string $path, string $message): array
    {
        try {
            $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new RuntimeException($message, 0, $exception);
        }
        if (! is_array($value)) {
            throw new RuntimeException($message);
        }

        return $value;
    }

    private function runDocara(string $binary, string $cwd, array $arguments): string
    {
        return $this->run(array_merge([PHP_BINARY, $binary], $arguments), $cwd, 900, 'UPGRADE_CANDIDATE_CHECK_FAILED');
    }

    private function run(array $command, string $cwd, int $timeout, string $code): string
    {
        $process = new Process($command, $cwd);
        $process->setTimeout($timeout);
        $process->run();
        if (! $process->isSuccessful()) {
            $message = trim($process->getErrorOutput() . "\n" . $process->getOutput());
            throw new RuntimeException($code . ':' . ($message === '' ? 'External command failed.' : $message));
        }

        return $process->getOutput();
    }

    private function composer(): string
    {
        $binary = $this->composerBinary ?? (new ExecutableFinder)->find('composer');
        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('UPGRADE_COMPOSER_MISSING:Composer executable was not found.');
        }

        return $binary;
    }

    private function makeDirectory(string $path): void
    {
        if (is_link($path)) {
            throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Symbolic-link upgrade directory is forbidden.');
        }
        $this->files->ensureDirectoryExists($path);
    }

    /** @return list<string> */
    private function upgradeDirectories(string $root): array
    {
        $path = $root . '/.docara/upgrades';
        if (! is_dir($path)) {
            return [];
        }

        return array_values($this->files->directories($path));
    }

    private function assertSafeDirectory(string $path, string $label): void
    {
        if (! is_dir($path) || is_link($path)) {
            throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Missing or unsafe ' . $label . '.');
        }
    }

    private function copyRegularFile(string $source, string $target): void
    {
        if (! is_file($source) || is_link($source) || file_exists($target) || is_link($target)) {
            throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Unsafe file copy boundary.');
        }
        $this->files->ensureDirectoryExists(dirname($target));
        if (! $this->files->copy($source, $target)) {
            throw new RuntimeException('UPGRADE_STATE_COPY_FAILED:Could not copy upgrade state.');
        }
    }

    private function atomicCopy(string $source, string $target): void
    {
        if (! is_file($source) || is_link($source) || is_link($target)) {
            throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Unsafe atomic-copy boundary.');
        }
        $this->atomicPut($target, (string) file_get_contents($source));
    }

    private function atomicPut(string $path, string $bytes): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $temporary = $path . '.tmp';
        if (is_link($path) || is_link($temporary)) {
            throw new RuntimeException('UPGRADE_PROJECT_PATH_UNSAFE:Symbolic-link atomic-write boundary is forbidden.');
        }
        $this->files->put($temporary, $bytes);
        if (! @rename($temporary, $path)) {
            $this->files->delete($temporary);
            throw new RuntimeException('UPGRADE_STATE_WRITE_FAILED:Could not atomically write upgrade state.');
        }
    }

    private function absolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function errorCode(\Throwable $exception): string
    {
        return preg_match('/\b([A-Z][A-Z0-9_]+):/', $exception->getMessage(), $matches) === 1 ? $matches[1] : 'UPGRADE_FAILED';
    }

    /** @param array<string, mixed> $plan @return array<string, mixed> */
    private function provenance(array $plan): array
    {
        return [
            'package' => 'simai/docara',
            'upgrade_id' => $plan['upgrade_id'] ?? null,
            'plan_sha256' => $plan['plan_sha256'] ?? null,
        ];
    }
}
