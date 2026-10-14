<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

/**
 * The receipt of one product component package build
 * (`simai.framework.product-package-receipt` version 1), written by
 * ui-builder (`sf-build package`, `write-package-receipt.py`) beside the
 * package output.
 *
 * Docara takes a package by its receipt: the package name, the package source
 * revision and the free release (`framework.release`, `ui-<12>`) come from it,
 * and every file Docara lays is checked against the receipt's hashes, read
 * either from the deterministic archive the receipt names (whose SHA-256 must
 * match) or from an output directory holding `smart/`.
 */
final readonly class FrameworkPackageReceipt
{
    public const SCHEMA = 'simai.framework.product-package-receipt';

    public const SCHEMA_VERSION = 1;

    /** @var array<string, array{sha256: string, size: int}> */
    private array $files;

    /** @var list<string> */
    private array $requiredFiles;

    /** @param array<string, mixed> $data */
    private function __construct(private array $data)
    {
        $files = [];
        foreach ($data['files'] as $file) {
            $files[$file['path']] = ['sha256' => $file['sha256'], 'size' => $file['size']];
        }
        $this->files = $files;
        $this->requiredFiles = array_map(static fn (array $file): string => $file['path'], $data['required_files']);
    }

    public static function fromFile(string $path): self
    {
        $bytes = @file_get_contents($path);
        if (! is_string($bytes)) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_UNREADABLE', $path);
        }
        try {
            $data = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_INVALID', $exception->getMessage());
        }

        return self::fromArray(is_array($data) ? $data : []);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $package = $data['package'] ?? null;
        if (($data['schema_id'] ?? $data['schema'] ?? null) !== self::SCHEMA
            || ($data['schema_version'] ?? null) !== self::SCHEMA_VERSION
        ) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_SCHEMA_UNSUPPORTED');
        }
        if (! is_string($package) || preg_match('/\A[a-z][a-z0-9-]*\z/D', $package) !== 1) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_INVALID', 'package');
        }
        $invalid = static function (string $field): never {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_INVALID', $field);
        };
        if (($data['loader']['declaration'] ?? null) !== 'SF_BOOT_CONFIG.packages.' . $package . '.base'
            || ($data['loader']['rule_file'] ?? null) !== 'smart/rule.js'
        ) {
            $invalid('loader');
        }
        $source = $data['package_source'] ?? null;
        if (! is_array($source)
            || ! self::isRepository($source['repository'] ?? null)
            || ! self::isCommit($source['revision'] ?? null)
            || ! is_string($source['path'] ?? null)
            || ! self::isCommit($source['tree'] ?? null)
        ) {
            $invalid('package_source');
        }
        $release = $data['framework']['release'] ?? null;
        if ($release === null) {
            // A receipt written without --ui, or before the free release
            // existed, names no release: Docara cannot tell what to serve the
            // package beside.
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_RELEASE_MISSING', $package);
        }
        if (! is_string($release) || preg_match('/\Aui-[a-f0-9]{12}\z/D', $release) !== 1
            || ! self::isRepository($data['framework']['source']['repository'] ?? null)
            || ! self::isCommit($data['framework']['source']['revision'] ?? null)
        ) {
            $invalid('framework');
        }
        if (! self::isRepository($data['builder']['repository'] ?? null)
            || ! self::isCommit($data['builder']['revision'] ?? null)
        ) {
            $invalid('builder');
        }
        $output = $data['output'] ?? null;
        if (! is_array($output)
            || ($output['runtime_path'] ?? null) !== 'smart'
            || ! is_string($output['archive'] ?? null)
            || preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\.tar\z/D', $output['archive']) !== 1
            || ! self::isSha256($output['archive_sha256'] ?? null)
            || ! self::isSha256($output['manifest_sha256'] ?? null)
            || ! is_int($output['files_count'] ?? null)
        ) {
            $invalid('output');
        }
        foreach (['files', 'required_files'] as $list) {
            if (! is_array($data[$list] ?? null) || ! array_is_list($data[$list]) || $data[$list] === []) {
                $invalid($list);
            }
            foreach ($data[$list] as $file) {
                if (! is_array($file)
                    || ! is_string($file['path'] ?? null)
                    || ! self::isSafeRelativePath($file['path'])
                    || ! str_starts_with($file['path'], 'smart/')
                    || ! self::isSha256($file['sha256'] ?? null)
                    || ! is_int($file['size'] ?? null)
                    || $file['size'] < 0
                ) {
                    $invalid($list);
                }
            }
        }
        // The file list is the one the build hashed: the manifest digest is
        // the SHA-256 of the compact JSON list of {path, sha256, size}, in the
        // builder's order, followed by a newline.
        $manifest = array_map(
            static fn (array $file): array => ['path' => $file['path'], 'sha256' => $file['sha256'], 'size' => $file['size']],
            $data['files'],
        );
        if (count($manifest) !== $output['files_count']
            || hash('sha256', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n") !== $output['manifest_sha256']
        ) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_MANIFEST_MISMATCH', $package);
        }
        $byPath = array_column($manifest, null, 'path');
        if (count($byPath) !== count($manifest)) {
            $invalid('files');
        }
        $required = array_column($data['required_files'], 'path');
        if (! in_array('smart/rule.js', $required, true)) {
            $invalid('required_files');
        }
        foreach ($data['required_files'] as $file) {
            $listed = $byPath[$file['path']] ?? null;
            if ($listed === null || $listed['sha256'] !== $file['sha256'] || $listed['size'] !== $file['size']) {
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_RECEIPT_REQUIRED_FILE_UNLISTED', $file['path']);
            }
        }

        return new self($data);
    }

    public function package(): string
    {
        return $this->data['package'];
    }

    public function packageRevision(): string
    {
        return $this->data['package_source']['revision'];
    }

    public function frameworkRelease(): string
    {
        return $this->data['framework']['release'];
    }

    public function archiveName(): string
    {
        return $this->data['output']['archive'];
    }

    /** @return list<string> */
    public function requiredFiles(): array
    {
        return $this->requiredFiles;
    }

    /**
     * Every file of the package from its deterministic archive (ustar), after
     * the archive's SHA-256 is checked against the receipt. The archive must
     * hold exactly the receipt's files.
     *
     * @return array<string, string>
     */
    public function readArchive(string $path): array
    {
        $bytes = @file_get_contents($path);
        if (! is_string($bytes)) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_ARCHIVE_UNREADABLE', $path);
        }
        if (! hash_equals($this->data['output']['archive_sha256'], hash('sha256', $bytes))) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_ARCHIVE_HASH_MISMATCH', $path);
        }

        $files = [];
        $offset = 0;
        $length = strlen($bytes);
        while ($offset + 512 <= $length) {
            $header = substr($bytes, $offset, 512);
            if (trim($header, "\0") === '') {
                break;
            }
            $name = rtrim(substr($header, 0, 100), "\0");
            $prefix = rtrim(substr($header, 345, 155), "\0");
            $entry = $prefix === '' ? $name : $prefix . '/' . $name;
            $size = octdec(trim(substr($header, 124, 12), " \0"));
            $type = $header[156];
            $offset += 512;
            if ($type === '5') {
                continue;
            }
            if ($type !== '0' && $type !== "\0") {
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_ARCHIVE_ENTRY_FORBIDDEN', $entry);
            }
            $files[$entry] = substr($bytes, $offset, (int) $size);
            $offset += (int) (ceil($size / 512) * 512);
        }

        return $this->checked($files);
    }

    /**
     * Every file of the package from an output directory holding `smart/`,
     * each checked against the receipt. The directory must hold exactly the
     * receipt's files.
     *
     * @return array<string, string>
     */
    public function readDirectory(string $directory): array
    {
        $files = [];
        $root = rtrim($directory, '/') . '/smart';
        if (! is_dir($root)) {
            throw new FrameworkComponentException('FRAMEWORK_PACKAGE_OUTPUT_MISSING', $root);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $entry) {
            if ($entry->isLink()) {
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_OUTPUT_SYMLINK_FORBIDDEN', $entry->getPathname());
            }
            if (! $entry->isFile()) {
                continue;
            }
            $relativePath = 'smart/' . str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
            $files[$relativePath] = (string) file_get_contents($entry->getPathname());
        }

        return $this->checked($files);
    }

    /**
     * The lock entry Docara keeps for this package: the receipt's identity,
     * the release it requires, its own mount, and the hashes of the files laid.
     *
     * @param  array<string, array{sha256: string}>  $files
     * @return array<string, mixed>
     */
    public function projection(array $files): array
    {
        ksort($files, SORT_STRING);

        return [
            'schema' => FrameworkLock::PACKAGE_PROJECTION_SCHEMA,
            'schema_version' => FrameworkLock::PACKAGE_PROJECTION_SCHEMA_VERSION,
            'package' => $this->package(),
            'requires' => $this->frameworkRelease(),
            'mount' => '_docara/framework-packages/' . $this->package() . '/' . $this->packageRevision(),
            'receipt' => [
                'schema' => self::SCHEMA,
                'schema_version' => self::SCHEMA_VERSION,
                'package_source' => [
                    'repository' => $this->data['package_source']['repository'],
                    'revision' => $this->packageRevision(),
                    'path' => $this->data['package_source']['path'],
                    'tree' => $this->data['package_source']['tree'],
                ],
                'framework_source' => [
                    'repository' => $this->data['framework']['source']['repository'],
                    'revision' => $this->data['framework']['source']['revision'],
                ],
                'builder' => [
                    'repository' => $this->data['builder']['repository'],
                    'revision' => $this->data['builder']['revision'],
                ],
                'manifest_sha256' => $this->data['output']['manifest_sha256'],
                'archive_sha256' => $this->data['output']['archive_sha256'],
            ],
            'files' => $files,
        ];
    }

    /**
     * @param  array<string, string>  $files
     * @return array<string, string>
     */
    private function checked(array $files): array
    {
        foreach ($files as $relativePath => $bytes) {
            $record = $this->files[$relativePath] ?? null;
            if ($record === null) {
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_FILE_NOT_IN_RECEIPT', $relativePath);
            }
            if (strlen($bytes) !== $record['size'] || ! hash_equals($record['sha256'], hash('sha256', $bytes))) {
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_FILE_HASH_MISMATCH', $relativePath);
            }
        }
        foreach (array_keys($this->files) as $relativePath) {
            if (! isset($files[$relativePath])) {
                throw new FrameworkComponentException('FRAMEWORK_PACKAGE_FILE_MISSING', $relativePath);
            }
        }
        ksort($files, SORT_STRING);

        return $files;
    }

    private static function isCommit(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-f0-9]{40}\z/D', $value) === 1;
    }

    private static function isSha256(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-f0-9]{64}\z/D', $value) === 1;
    }

    private static function isRepository(mixed $value): bool
    {
        return is_string($value) && preg_match('~\A[a-z0-9-]+/[a-z0-9._-]+\z~D', $value) === 1;
    }

    private static function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, "\0")) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}
