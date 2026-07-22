<?php

declare(strict_types=1);

namespace Simai\Docara\Template;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Simai\Docara\Portable\CanonicalJson;
use Simai\Docara\Portable\FilesystemPath;

final readonly class TemplateMirror
{
    public const CONTRACT_VERSION = 1;

    public const MANIFEST = 'docara-template-mirror.json';

    private string $repositoryRoot;

    private string $starterTree;

    private string $supportTree;

    private string $packageVersion;

    public function __construct(string $repositoryRoot, private string $sourceRevision)
    {
        if (preg_match('/\A[0-9a-f]{40}\z/', $sourceRevision) !== 1) {
            throw new RuntimeException('The Docara source revision must be an exact 40-character commit SHA.');
        }
        $this->assertNoSymlinkTraversal($repositoryRoot, 'The Docara repository root');
        $resolved = realpath($repositoryRoot);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new RuntimeException('The Docara repository root is missing or unsafe.');
        }
        $this->repositoryRoot = FilesystemPath::normalize($resolved);
        $this->assertExactSourceRevision($resolved);
        $this->packageVersion = $this->releaseVersionForRevision();
        $this->starterTree = $this->gitOutput([
            'rev-parse',
            $this->sourceRevision . ':stubs/portable',
        ], 'The exact portable starter tree is missing.');
        $this->supportTree = $this->gitOutput([
            'rev-parse',
            $this->sourceRevision . ':resources/template-mirror',
        ], 'The exact template mirror support tree is missing.');
    }

    /** @return array<string, string> */
    public function expectedFiles(): array
    {
        $files = $this->starterFiles();
        $sources = [];
        foreach (array_keys($files) as $relative) {
            $sources[$relative] = 'stubs/portable/' . $relative;
        }
        foreach ($this->supportFiles() as $relative => $contents) {
            if (array_key_exists($relative, $files)) {
                throw new RuntimeException("Template mirror source collision at [$relative].");
            }
            $files[$relative] = $contents;
            $sources[$relative] = 'resources/template-mirror/' . $relative;
        }
        if (array_key_exists(self::MANIFEST, $files)) {
            throw new RuntimeException('The canonical template sources cannot use the reserved mirror manifest path.');
        }
        ksort($files, SORT_STRING);

        $manifestFiles = [];
        foreach ($files as $relative => $contents) {
            $manifestFiles[$relative] = [
                'mode' => '100644',
                'sha256' => hash('sha256', $contents),
                'source' => $sources[$relative],
            ];
        }
        $files[self::MANIFEST] = CanonicalJson::encodePretty([
            'schema' => 'docara.template_mirror.v1',
            'contract_version' => self::CONTRACT_VERSION,
            'generated_from' => [
                'repository' => 'simai/docara',
                'revision' => $this->sourceRevision,
                'package_version' => $this->packageVersion,
                'tree' => $this->starterTree,
                'support_tree' => $this->supportTree,
                'starter' => 'stubs/portable',
            ],
            'files' => $manifestFiles,
        ]);
        ksort($files, SORT_STRING);

        return $files;
    }

    private function releaseVersionForRevision(): string
    {
        $tags = array_values(array_filter(
            preg_split('/\R/', trim($this->gitOutput([
                'tag',
                '--points-at',
                $this->sourceRevision,
            ], 'Unable to resolve the Docara release tag.', false))) ?: [],
            static fn (string $tag): bool => preg_match(
                '/\Av(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?\z/',
                $tag,
            ) === 1,
        ));
        if (count($tags) !== 1) {
            throw new RuntimeException(
                'Template mirror export requires exactly one SemVer release tag pointing at the exact Docara revision.',
            );
        }
        $resolved = $this->gitOutput([
            'rev-parse',
            $tags[0] . '^{commit}',
        ], 'Unable to resolve the Docara release tag commit.');
        if (! hash_equals($this->sourceRevision, $resolved)) {
            throw new RuntimeException('The Docara release tag does not resolve to the exact source revision.');
        }

        return $tags[0];
    }

    /** @return list<string> */
    public function export(string $destination): array
    {
        $destination = $this->prepareEmptyDestination($destination);
        $written = [];

        foreach ($this->expectedFiles() as $relative => $contents) {
            $target = $destination . '/' . $relative;
            $directory = dirname($target);
            if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
                throw new RuntimeException("Unable to create template mirror directory [$directory].");
            }
            if (file_put_contents($target, $contents) === false) {
                throw new RuntimeException("Unable to write template mirror file [$relative].");
            }
            if (! chmod($target, 0644)) {
                throw new RuntimeException("Unable to set canonical template mirror mode [$relative].");
            }
            $written[] = $relative;
        }

        return $written;
    }

    /**
     * @return array{missing: list<string>, changed: list<string>, unexpected: list<string>}
     */
    public function diff(string $destination): array
    {
        $this->assertNoSymlinkTraversal($destination, 'The template mirror destination');
        $this->assertDestinationOutsideSource($destination);
        $resolved = realpath($destination);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new RuntimeException('The template mirror destination is missing or unsafe.');
        }

        $expected = $this->expectedFiles();
        $actual = $this->actualFiles($resolved);
        $missing = array_values(array_diff(array_keys($expected), array_keys($actual)));
        $unexpected = array_values(array_diff(array_keys($actual), array_keys($expected)));
        $changed = [];

        foreach (array_intersect(array_keys($expected), array_keys($actual)) as $relative) {
            if (! is_string($actual[$relative])
                || ! hash_equals(hash('sha256', $expected[$relative]), hash('sha256', $actual[$relative]))
            ) {
                $changed[] = $relative;
            }
        }
        sort($missing, SORT_STRING);
        sort($changed, SORT_STRING);
        sort($unexpected, SORT_STRING);

        return compact('missing', 'changed', 'unexpected');
    }

    /** @return array<string, string> */
    private function starterFiles(): array
    {
        $files = [];
        $tree = $this->gitOutput([
            'ls-tree',
            '-r',
            '-z',
            $this->sourceRevision,
            '--',
            'stubs/portable',
        ], 'Unable to enumerate the exact portable starter tree.', false);
        foreach (explode("\0", $tree) as $entry) {
            if ($entry === '') {
                continue;
            }
            if (preg_match('/\A([0-7]{6}) blob [0-9a-f]+\t(stubs\/portable\/(.+))\z/s', $entry, $match) !== 1) {
                throw new RuntimeException('The exact portable starter tree contains an unsupported Git entry.');
            }
            if ($match[1] !== '100644') {
                throw new RuntimeException('The canonical portable starter supports regular non-executable files only.');
            }
            $this->assertSafeRelativePath($match[3]);

            $files[$match[3]] = $this->gitOutput([
                'show',
                $this->sourceRevision . ':' . $match[2],
            ], "Unable to read canonical portable starter file [{$match[3]}].", false);
        }
        if ($files === []) {
            throw new RuntimeException('The exact portable starter tree is empty.');
        }
        ksort($files, SORT_STRING);

        return $files;
    }

    /** @return array<string, string> */
    private function supportFiles(): array
    {
        $files = [];
        $tree = $this->gitOutput([
            'ls-tree',
            '-r',
            '-z',
            $this->sourceRevision,
            '--',
            'resources/template-mirror',
        ], 'Unable to enumerate the exact template mirror support tree.', false);
        foreach (explode("\0", $tree) as $entry) {
            if ($entry === '') {
                continue;
            }
            if (preg_match('/\A([0-7]{6}) blob [0-9a-f]+\t(resources\/template-mirror\/(.+))\z/s', $entry, $match) !== 1
                || $match[1] !== '100644'
            ) {
                throw new RuntimeException('The template mirror support tree supports regular non-executable files only.');
            }
            $this->assertSafeRelativePath($match[3]);
            $contents = $this->gitOutput([
                'show',
                $this->sourceRevision . ':' . $match[2],
            ], "Unable to read template mirror support file [{$match[3]}].", false);
            if ($match[3] === 'README.md') {
                if (substr_count($contents, '{{DOCARA_PACKAGE_VERSION}}') !== 1) {
                    throw new RuntimeException('The template mirror README must contain exactly one package-version placeholder.');
                }
                $contents = str_replace('{{DOCARA_PACKAGE_VERSION}}', $this->packageVersion, $contents);
            }
            $files[$match[3]] = $contents;
        }
        foreach (['.github/workflows/sync.yml', '.gitignore', 'README.md'] as $required) {
            if (! array_key_exists($required, $files)) {
                throw new RuntimeException("The template mirror support file [$required] is missing.");
            }
        }
        ksort($files, SORT_STRING);

        return $files;
    }

    /** @return array<string, string|null> */
    private function actualFiles(string $destination): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($destination, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($destination) + 1));
            if ($relative === '.git' || str_starts_with($relative, '.git/')) {
                continue;
            }
            if ($file->isLink()) {
                $files[$relative] = null;

                continue;
            }
            if ($file->isDir()) {
                continue;
            }
            $stat = @lstat($file->getPathname());
            if (! $file->isFile()
                || ! is_array($stat)
                || (($stat['mode'] ?? 0) & 0170000) !== 0100000
                || (($stat['mode'] ?? 0) & 0777) !== 0644
                || ($stat['nlink'] ?? 1) > 1
            ) {
                $files[$relative] = null;

                continue;
            }
            $contents = file_get_contents($file->getPathname());
            $files[$relative] = is_string($contents) ? $contents : null;
        }
        ksort($files, SORT_STRING);

        return $files;
    }

    private function prepareEmptyDestination(string $destination): string
    {
        if ($destination === '' || str_contains($destination, "\0")) {
            throw new RuntimeException('The template mirror destination is empty or unsafe.');
        }
        $this->assertNoSymlinkTraversal($destination, 'The template mirror destination');
        $this->assertDestinationOutsideSource($destination);
        if (file_exists($destination) && ! is_dir($destination)) {
            throw new RuntimeException('The template mirror destination must be a directory.');
        }
        if (! file_exists($destination)
            && ! mkdir($destination, 0777, true)
            && ! is_dir($destination)
        ) {
            throw new RuntimeException("Unable to create template mirror destination [$destination].");
        }

        $resolved = realpath($destination);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new RuntimeException('The template mirror destination could not be resolved.');
        }
        $entries = array_values(array_diff(scandir($resolved) ?: [], ['.', '..']));
        if ($entries !== []) {
            throw new RuntimeException('Template export requires an empty destination and never overwrites existing files.');
        }

        return rtrim($resolved, DIRECTORY_SEPARATOR);
    }

    private function assertNoSymlinkTraversal(string $path, string $label): void
    {
        $candidate = rtrim($path, '/\\');
        while ($candidate !== '' && $candidate !== '.' && $candidate !== DIRECTORY_SEPARATOR) {
            if (is_link($candidate)) {
                throw new RuntimeException("$label cannot traverse a symbolic link.");
            }
            $parent = dirname($candidate);
            if ($parent === $candidate) {
                break;
            }
            $candidate = rtrim($parent, '/\\');
        }
    }

    private function assertSafeRelativePath(string $path): void
    {
        if (preg_match('/\A[A-Za-z0-9._-]+(?:\/[A-Za-z0-9._-]+)*\z/D', $path) !== 1) {
            throw new RuntimeException('The canonical template source contains an unsafe path.');
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '.' || $segment === '..' || strtolower($segment) === '.git') {
                throw new RuntimeException('The canonical template source contains a reserved path segment.');
            }
        }
    }

    private function assertExactSourceRevision(string $repositoryRoot): void
    {
        $head = $this->gitOutput(
            ['rev-parse', '--verify', 'HEAD^{commit}'],
            'Unable to verify the exact Docara source checkout.',
        );
        $status = $this->gitOutput(
            ['status', '--porcelain=v1', '--untracked-files=all'],
            'Unable to verify the exact Docara source checkout.',
            false,
        );

        if (preg_match('/\A[0-9a-f]{40}\z/', $head) !== 1) {
            throw new RuntimeException('The Docara source must be an exact Git commit checkout.');
        }
        if (! hash_equals($this->sourceRevision, $head)) {
            throw new RuntimeException('The requested Docara source revision does not match checkout HEAD.');
        }
        if (trim($status) !== '') {
            throw new RuntimeException('Template mirror export requires a clean Docara source checkout.');
        }
    }

    /** @param list<string> $arguments */
    private function gitOutput(array $arguments, string $failureMessage, bool $trim = true): string
    {
        $pipes = [];
        $process = @proc_open(
            ['git', '-C', $this->repositoryRoot, ...$arguments],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            null,
            ['bypass_shell' => true],
        );
        if (! is_resource($process)) {
            throw new RuntimeException($failureMessage);
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! is_string($output)) {
            throw new RuntimeException($failureMessage);
        }

        return $trim ? trim($output) : $output;
    }

    private function assertDestinationOutsideSource(string $destination): void
    {
        $candidate = $destination;
        $suffix = [];
        while (! file_exists($candidate)) {
            $name = basename($candidate);
            if ($name !== '' && $name !== '.') {
                array_unshift($suffix, $name);
            }
            $parent = dirname($candidate);
            if ($parent === $candidate) {
                break;
            }
            $candidate = $parent;
        }

        $resolved = realpath($candidate);
        if ($resolved === false) {
            throw new RuntimeException('The template mirror destination parent is missing or unsafe.');
        }
        foreach ($suffix as $segment) {
            $resolved = $segment === '..'
                ? dirname($resolved)
                : $resolved . DIRECTORY_SEPARATOR . $segment;
        }
        $resolved = FilesystemPath::normalize($resolved);
        if (FilesystemPath::isWithin($resolved, $this->repositoryRoot)) {
            throw new RuntimeException('The template mirror destination must be outside the Docara source checkout.');
        }
    }
}
