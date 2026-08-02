<?php

declare(strict_types=1);

namespace Simai\Docara\Release;

use JsonException;
use RuntimeException;

final class ReleasePackageBuilder
{
    public function __construct(private readonly DeterministicZipWriter $zip = new DeterministicZipWriter) {}

    /** @return array<string, mixed> */
    public function build(string $repository, string $revision, string $version, string $tag, string $outputDirectory): array
    {
        $repository = realpath($repository) ?: '';
        if ($repository === '' || ! file_exists($repository . '/.git')) {
            throw new RuntimeException('Release source must be a Git checkout.');
        }
        if (preg_match('/^[a-f0-9]{40}$/', $revision) !== 1 || $this->git($repository, ['rev-parse', $revision . '^{commit}']) !== $revision) {
            throw new RuntimeException('Release revision must be an exact 40-character commit SHA.');
        }
        if ($this->git($repository, ['rev-parse', 'HEAD']) !== $revision) {
            throw new RuntimeException('Release checkout HEAD does not match the requested exact revision.');
        }
        if ($this->git($repository, ['status', '--porcelain=v1', '--untracked-files=all']) !== '') {
            throw new RuntimeException('Release checkout is not clean.');
        }
        if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:-[0-9A-Za-z.-]+)?$/', $version) !== 1) {
            throw new RuntimeException('Release version must be SemVer without a leading v.');
        }
        if ($tag === '' || preg_match('/^[0-9A-Za-z._-]+$/', $tag) !== 1) {
            throw new RuntimeException('Release tag parameter is invalid.');
        }
        if ((file_exists($outputDirectory) && is_link($outputDirectory)) || (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0775, true))) {
            throw new RuntimeException('Release output directory is unavailable or unsafe.');
        }

        $surfaceBytes = $this->blob($repository, $revision, 'resources/release/package-surface.json');
        $surface = $this->decode($surfaceBytes, 'release surface');
        if (($surface['$schema'] ?? null) !== 'docara.release_surface.v1') {
            throw new RuntimeException('Release surface schema is invalid.');
        }
        $include = $surface['include'] ?? null;
        $executables = $surface['executable'] ?? null;
        $forbidden = $surface['forbidden_prefixes'] ?? null;
        if (! is_array($include) || ! is_array($executables) || ! is_array($forbidden)) {
            throw new RuntimeException('Release surface lists are invalid.');
        }

        $tracked = array_filter(explode("\0", $this->gitRaw($repository, ['ls-tree', '-rz', '--name-only', $revision])));
        sort($tracked, SORT_STRING);
        $files = [];
        $caseMap = [];
        foreach ($tracked as $path) {
            if (! $this->included($path, $include)) {
                continue;
            }
            foreach ($forbidden as $prefix) {
                if (is_string($prefix) && ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix))) {
                    throw new RuntimeException("Forbidden release path selected [{$path}].");
                }
            }
            $lower = strtolower($path);
            if (isset($caseMap[$lower])) {
                throw new RuntimeException("Case-colliding release paths [{$caseMap[$lower]}] and [{$path}].");
            }
            $caseMap[$lower] = $path;
            $mode = $this->git($repository, ['ls-tree', $revision, '--', $path]);
            if (! str_starts_with($mode, '100644 ') && ! str_starts_with($mode, '100755 ')) {
                throw new RuntimeException("Symlink or unsupported Git mode selected [{$path}].");
            }
            $contents = $this->blob($repository, $revision, $path);
            $this->assertNoPrivateLeak($path, $contents);
            $files[$path] = ['contents' => $contents, 'executable' => in_array($path, $executables, true)];
        }
        if ($files === []) {
            throw new RuntimeException('Release surface selected no files.');
        }

        $composer = $this->decode($files['composer.json']['contents'] ?? '', 'composer.json');
        $sbom = $this->sbom($composer, $version, $revision);
        $files['RELEASE-SBOM.cdx.json'] = ['contents' => $this->json($sbom), 'executable' => false];

        $ledger = [];
        foreach ($files as $path => $file) {
            $ledger[$path] = ['sha256' => hash('sha256', $file['contents']), 'size' => strlen($file['contents']), 'mode' => $file['executable'] ? '0755' : '0644'];
        }
        ksort($ledger, SORT_STRING);
        $contentManifest = [
            'schema' => 'docara.release_content_manifest.v1',
            'product' => 'Docara',
            'package' => (string) ($composer['name'] ?? ''),
            'version' => $version,
            'tag_parameter' => $tag,
            'published' => false,
            'source_revision' => $revision,
            'build_tool_sha256' => hash('sha256', $this->blob($repository, $revision, 'src/Release/ReleasePackageBuilder.php')),
            'surface_sha256' => hash('sha256', $surfaceBytes),
            'dependency_lock_contract' => 'consumer-owned composer.lock; moving source references forbidden',
            'files' => $ledger,
        ];
        $files['RELEASE-MANIFEST.json'] = ['contents' => $this->json($contentManifest), 'executable' => false];
        ksort($files, SORT_STRING);

        $base = 'docara-' . $version;
        $archivePath = rtrim($outputDirectory, '/') . '/' . $base . '.zip';
        $this->zip->write($archivePath, $files);
        $archiveSha = hash_file('sha256', $archivePath);
        if (! is_string($archiveSha)) {
            throw new RuntimeException('Could not hash release archive.');
        }

        $artifactLedger = [];
        foreach ($files as $path => $file) {
            $artifactLedger[$path] = hash('sha256', $file['contents']);
        }
        $external = [
            'schema' => 'docara.release_artifact_manifest.v1',
            'product' => 'Docara',
            'package' => (string) ($composer['name'] ?? ''),
            'version' => $version,
            'tag_parameter' => $tag,
            'published' => false,
            'source_revision' => $revision,
            'archive' => basename($archivePath),
            'archive_sha256' => $archiveSha,
            'file_count' => count($files),
            'files' => $artifactLedger,
            'framework_tuple' => $this->frameworkTuple($files),
            'dependency_lock_contract' => 'consumer-owned composer.lock; release verification records its SHA-256 and resolved packages',
            'sbom_path' => 'RELEASE-SBOM.cdx.json',
            'content_manifest_path' => 'RELEASE-MANIFEST.json',
        ];
        $manifestPath = rtrim($outputDirectory, '/') . '/' . $base . '.release-manifest.json';
        file_put_contents($manifestPath, $this->json($external), LOCK_EX);
        $checksumsPath = rtrim($outputDirectory, '/') . '/' . $base . '.sha256';
        file_put_contents($checksumsPath, $archiveSha . '  ' . basename($archivePath) . "\n", LOCK_EX);

        return $external + ['archive_path' => $archivePath, 'manifest_path' => $manifestPath, 'checksums_path' => $checksumsPath];
    }

    /** @param list<mixed> $include */
    private function included(string $path, array $include): bool
    {
        foreach ($include as $entry) {
            if (! is_string($entry)) {
                continue;
            }
            if (str_ends_with($entry, '/') ? str_starts_with($path, $entry) : $path === $entry) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function decode(string $json, string $label): array
    {
        try {
            $value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid {$label}: {$exception->getMessage()}", 0, $exception);
        }
        if (! is_array($value)) {
            throw new RuntimeException("Invalid {$label}: object expected.");
        }

        return $value;
    }

    /** @param array<string, mixed> $composer @return array<string, mixed> */
    private function sbom(array $composer, string $version, string $revision): array
    {
        $components = [];
        foreach (($composer['require'] ?? []) as $name => $constraint) {
            $components[] = [
                'type' => $name === 'php' ? 'platform' : 'library',
                'name' => $name,
                'version' => (string) $constraint,
                'scope' => 'required',
            ];
        }
        usort($components, static fn (array $left, array $right): int => strcmp($left['name'], $right['name']));

        return [
            'bomFormat' => 'CycloneDX',
            'specVersion' => '1.5',
            'version' => 1,
            'metadata' => ['component' => ['type' => 'application', 'name' => (string) ($composer['name'] ?? 'simai/docara'), 'version' => $version, 'properties' => [['name' => 'docara:source_revision', 'value' => $revision]]]],
            'components' => $components,
            'properties' => [['name' => 'docara:resolution', 'value' => 'Constraints only; exact resolved inventory belongs to the fresh consumer composer.lock evidence.']],
        ];
    }

    /** @param array<string, array{contents: string, executable: bool}> $files @return array<string, mixed> */
    private function frameworkTuple(array $files): array
    {
        $path = 'resources/framework/runtime-lock.json';

        return isset($files[$path]) ? $this->decode($files[$path]['contents'], $path) : [];
    }

    private function assertNoPrivateLeak(string $path, string $contents): void
    {
        if (preg_match('#(?:/Users/[^/\s]+|/home/[^/\s]+|[A-Za-z]:\\\\Users\\\\[^\\\\\s]+)#', $contents) === 1
            || preg_match('/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/', $contents) === 1) {
            throw new RuntimeException("Private path or key material detected in release file [{$path}].");
        }
    }

    private function blob(string $repository, string $revision, string $path): string
    {
        return $this->gitRaw($repository, ['show', $revision . ':' . $path]);
    }

    /** @param list<string> $arguments */
    private function git(string $repository, array $arguments): string
    {
        return rtrim($this->gitRaw($repository, $arguments), "\r\n");
    }

    /** @param list<string> $arguments */
    private function gitRaw(string $repository, array $arguments): string
    {
        $command = array_merge(['git', '-C', $repository], $arguments);
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new RuntimeException('Could not start Git.');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0 || ! is_string($stdout)) {
            throw new RuntimeException('Git command failed: ' . trim((string) $stderr));
        }

        return $stdout;
    }

    /** @param array<string, mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }
}
