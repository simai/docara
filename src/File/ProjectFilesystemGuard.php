<?php

declare(strict_types=1);

namespace Simai\Docara\File;

use Simai\Docara\Portable\FilesystemPath;
use Simai\Docara\Portable\PortableConfigurationException;

final readonly class ProjectFilesystemGuard
{
    /** @var list<string> */
    private const WRITABLE_ROOTS = [
        '.docara',
        '.docara-preview',
        '.docara-qa',
        'build_preview-cache',
        'build_preview-cache.docara-candidate',
        'build_preview-cache.docara-rollback',
        'design',
        'smart',
    ];

    public function examplePath(string $projectRoot, string $relative, bool $allowMissing = true): string
    {
        return $this->inspect($projectRoot, $relative, ['examples'], $allowMissing);
    }

    public function putNewExample(string $projectRoot, string $relative, string $contents, string $collisionCode): string
    {
        $directory = dirname(str_replace('\\', '/', $relative));
        $root = $this->root($projectRoot);
        $segments = $this->segments($directory, ['examples']);
        $current = $root;
        foreach ($segments as $segment) {
            $this->assertCase($current, $segment);
            $current .= '/' . $segment;
            if (file_exists($current) || is_link($current)) {
                $this->assertNode($root, $current, true);

                continue;
            }
            if (! mkdir($current, 0755)) {
                throw $this->unsafe("Example directory [$directory] could not be created safely.");
            }
        }
        $path = $this->examplePath($projectRoot, $relative);
        if (file_exists($path) || is_link($path)) {
            throw new PortableConfigurationException($collisionCode, "Generated path [$relative] already exists.");
        }
        $temporary = dirname($path) . '/.docara-tmp-' . bin2hex(random_bytes(12));
        if (file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
            @unlink($temporary);
            throw $this->unsafe("Example path [$relative] could not be written completely.");
        }
        try {
            $this->assertNode($root, $temporary, false);
            if (! @link($temporary, $path)) {
                throw new PortableConfigurationException($collisionCode, "Generated path [$relative] appeared before atomic publish.");
            }
        } finally {
            @unlink($temporary);
        }
        $this->assertNode($root, $path, false);

        return $path;
    }

    public function deleteExampleFile(string $projectRoot, string $relative): void
    {
        $path = $this->examplePath($projectRoot, $relative, false);
        $this->assertNode($this->root($projectRoot), $path, false);
        if (! unlink($path)) {
            throw $this->unsafe("Example file [$relative] could not be removed safely.");
        }
    }

    public function authoringPath(string $projectRoot, string $relative, string $contentRoot, bool $allowMissing = true): string
    {
        $relative = str_replace('\\', '/', $relative);
        $contentRoot = trim(str_replace('\\', '/', $contentRoot), '/');
        if ($contentRoot === '' || ($relative !== $contentRoot && ! str_starts_with($relative, $contentRoot . '/'))) {
            throw $this->unsafe("Authoring path [$relative] is outside configured content root [$contentRoot].");
        }

        return $this->inspect($projectRoot, $relative, [explode('/', $contentRoot)[0]], $allowMissing);
    }

    public function putNewAuthoring(string $projectRoot, string $relative, string $contentRoot, string $contents, string $collisionCode): string
    {
        $directory = dirname(str_replace('\\', '/', $relative));
        $root = $this->root($projectRoot);
        $segments = $this->segments($directory, [explode('/', trim($contentRoot, '/'))[0]]);
        $current = $root;
        foreach ($segments as $segment) {
            $this->assertCase($current, $segment);
            $current .= '/' . $segment;
            if (file_exists($current) || is_link($current)) {
                $this->assertNode($root, $current, true);

                continue;
            }
            if (! mkdir($current, 0755)) {
                throw $this->unsafe("Authoring directory [$directory] could not be created safely.");
            }
        }
        $path = $this->authoringPath($projectRoot, $relative, $contentRoot);
        if (file_exists($path) || is_link($path)) {
            throw new PortableConfigurationException($collisionCode, "Generated path [$relative] already exists.");
        }
        $temporary = dirname($path) . '/.docara-tmp-' . bin2hex(random_bytes(12));
        if (file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
            @unlink($temporary);
            throw $this->unsafe("Authoring path [$relative] could not be written completely.");
        }
        $this->assertNode($root, $temporary, false);
        if (! @link($temporary, $path)) {
            @unlink($temporary);
            throw new PortableConfigurationException($collisionCode, "Generated path [$relative] appeared before atomic publish.");
        }
        @unlink($temporary);
        $this->assertNode($root, $path, false);

        return $path;
    }

    public function deleteAuthoringFile(string $projectRoot, string $relative, string $contentRoot): void
    {
        $path = $this->authoringPath($projectRoot, $relative, $contentRoot, false);
        $this->assertNode($this->root($projectRoot), $path, false);
        if (! unlink($path)) {
            throw $this->unsafe("Authoring file [$relative] could not be removed safely.");
        }
    }

    public function root(string $projectRoot): string
    {
        $lexical = rtrim($projectRoot, '/\\');
        $real = realpath($lexical);
        $stat = @lstat($lexical);
        if ($lexical === '' || $real === false || ! is_dir($real) || is_link($lexical)
            || ! is_array($stat) || (($stat['mode'] ?? 0) & 0170000) !== 0040000) {
            throw $this->unsafe('Project root must be a real directory and not a symbolic link.');
        }

        return rtrim(FilesystemPath::normalize($real), '/');
    }

    public function writablePath(string $projectRoot, string $relative, bool $allowMissing = true): string
    {
        return $this->inspect($projectRoot, $relative, self::WRITABLE_ROOTS, $allowMissing);
    }

    public function directoryPath(
        string $projectRoot,
        string $relative,
        bool $allowMissing = true,
        ?string $caseCollisionCode = null,
    ): string {
        $path = $caseCollisionCode === null
            ? $this->writablePath($projectRoot, $relative, $allowMissing)
            : $this->inspect($projectRoot, $relative, self::WRITABLE_ROOTS, $allowMissing, $caseCollisionCode);
        if (file_exists($path) || is_link($path)) {
            $this->assertNode($this->root($projectRoot), $path, true);
        }

        return $path;
    }

    public function ensureDirectory(string $projectRoot, string $relative): string
    {
        $root = $this->root($projectRoot);
        $segments = $this->segments($relative, self::WRITABLE_ROOTS);
        $current = $root;
        foreach ($segments as $segment) {
            $this->assertCase($current, $segment);
            $current .= '/' . $segment;
            if (file_exists($current) || is_link($current)) {
                $this->assertNode($root, $current, true);

                continue;
            }
            if (! mkdir($current, 0755)) {
                throw $this->unsafe("Generated directory [$relative] could not be created safely.");
            }
            $this->assertNode($root, $current, true);
        }

        return $current;
    }

    public function regularFile(string $projectRoot, string $relative): string
    {
        $path = $this->writablePath($projectRoot, $relative, false);
        $root = $this->root($projectRoot);
        $this->assertNode($root, $path, false);

        return $path;
    }

    public function putNewOrIdentical(
        string $projectRoot,
        string $relative,
        string $contents,
        string $collisionCode = 'SDK_WRITE_COLLISION',
    ): string {
        $directory = dirname(str_replace('\\', '/', $relative));
        if ($directory !== '.') {
            $this->ensureDirectory($projectRoot, $directory);
        }
        $path = $this->writablePath($projectRoot, $relative);
        if (file_exists($path) || is_link($path)) {
            $this->assertNode($this->root($projectRoot), $path, false);
            if (hash_equals(hash('sha256', (string) file_get_contents($path)), hash('sha256', $contents))) {
                return $path;
            }

            throw new PortableConfigurationException($collisionCode, "Generated path [$relative] contains different contents.");
        }

        return $this->publishFile($projectRoot, $relative, $contents, $collisionCode);
    }

    public function putNew(
        string $projectRoot,
        string $relative,
        string $contents,
        string $collisionCode = 'SDK_WRITE_COLLISION',
    ): string {
        $directory = dirname(str_replace('\\', '/', $relative));
        if ($directory !== '.') {
            $this->ensureDirectory($projectRoot, $directory);
        }
        $path = $this->writablePath($projectRoot, $relative);
        if (file_exists($path) || is_link($path)) {
            throw new PortableConfigurationException($collisionCode, "Generated path [$relative] already exists.");
        }

        return $this->publishFile($projectRoot, $relative, $contents, $collisionCode);
    }

    public function copyNew(
        string $projectRoot,
        string $source,
        string $relative,
        string $collisionCode = 'SDK_WRITE_COLLISION',
    ): string {
        $contents = file_get_contents($source);
        if (! is_string($contents)) {
            throw $this->unsafe("Source for generated path [$relative] could not be read.");
        }

        return $this->putNew($projectRoot, $relative, $contents, $collisionCode);
    }

    public function deleteDirectory(string $projectRoot, string $relative, Filesystem $files): void
    {
        $path = $this->writablePath($projectRoot, $relative);
        if (! file_exists($path) && ! is_link($path)) {
            return;
        }
        $this->assertSafeTree($projectRoot, $relative);
        if (! $files->deleteDirectory($path)) {
            throw $this->unsafe("Generated directory [$relative] could not be removed safely.");
        }
    }

    public function deleteFile(string $projectRoot, string $relative): void
    {
        $path = $this->regularFile($projectRoot, $relative);
        if (! unlink($path)) {
            throw $this->unsafe("Generated file [$relative] could not be removed safely.");
        }
    }

    public function moveDirectory(string $projectRoot, string $from, string $to): void
    {
        $source = $this->writablePath($projectRoot, $from, false);
        $this->assertSafeTree($projectRoot, $from);
        $target = $this->writablePath($projectRoot, $to);
        if (file_exists($target) || is_link($target)) {
            throw new PortableConfigurationException('SDK_WRITE_COLLISION', "Generated move target [$to] already exists.");
        }
        if (! rename($source, $target)) {
            throw $this->unsafe("Generated directory [$from] could not be moved to [$to].");
        }
        $this->assertSafeTree($projectRoot, $to);
    }

    public function assertSafeTree(string $projectRoot, string $relative): void
    {
        $root = $this->root($projectRoot);
        $path = $this->writablePath($projectRoot, $relative, false);
        $this->assertNode($root, $path, true);
        $this->walk($root, $path);
    }

    /** @param list<string> $allowedRoots */
    private function inspect(
        string $projectRoot,
        string $relative,
        array $allowedRoots,
        bool $allowMissing,
        ?string $caseCollisionCode = null,
    ): string {
        $root = $this->root($projectRoot);
        $segments = $this->segments($relative, $allowedRoots);
        $current = $root;
        $missing = false;
        foreach ($segments as $index => $segment) {
            if (! $missing) {
                $this->assertCase($current, $segment, $caseCollisionCode);
            }
            $current .= '/' . $segment;
            if ($missing || (! file_exists($current) && ! is_link($current))) {
                $missing = true;

                continue;
            }
            if ($index < count($segments) - 1) {
                $this->assertNode($root, $current, true);
            } else {
                $this->assertAnyNode($root, $current);
            }
        }
        if ($missing && ! $allowMissing) {
            throw $this->unsafe("Generated path [$relative] is missing.");
        }

        return $current;
    }

    /** @param list<string> $allowedRoots @return list<string> */
    private function segments(string $relative, array $allowedRoots): array
    {
        $relative = str_replace('\\', '/', $relative);
        if ($relative === '' || str_contains($relative, "\0") || str_starts_with($relative, '/')
            || str_ends_with($relative, '/') || str_contains('/' . $relative . '/', '/../')
            || str_contains('/' . $relative . '/', '/./')) {
            throw $this->unsafe("Generated path [$relative] is outside the project root.");
        }
        $segments = explode('/', $relative);
        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z0-9._-]+$/D', $segment) !== 1) {
                throw $this->unsafe("Generated path [$relative] contains an unsafe segment.");
            }
        }
        $first = $segments[0];
        if (! in_array($first, $allowedRoots, true)) {
            throw $this->unsafe("Generated path [$relative] is outside owned writable roots.");
        }

        return $segments;
    }

    private function assertCase(string $parent, string $segment, ?string $collisionCode = null): void
    {
        if (! is_dir($parent) || is_link($parent)) {
            throw $this->unsafe('Generated path parent is missing or unsafe.');
        }
        $entries = scandir($parent);
        if (! is_array($entries)) {
            throw $this->unsafe('Generated path parent could not be inspected.');
        }
        foreach ($entries as $entry) {
            if ($entry !== $segment && strcasecmp($entry, $segment) === 0) {
                if ($collisionCode !== null) {
                    throw new PortableConfigurationException(
                        $collisionCode,
                        "Generated path has a case-colliding entry [$entry].",
                    );
                }
                throw $this->unsafe("Generated path has a case-colliding entry [$entry].");
            }
        }
    }

    private function assertNode(string $root, string $path, bool $directory): void
    {
        $stat = @lstat($path);
        $real = realpath($path);
        $type = is_array($stat) ? (($stat['mode'] ?? 0) & 0170000) : 0;
        if (! is_array($stat) || $real === false || is_link($path)
            || ($directory && $type !== 0040000)
            || (! $directory && $type !== 0100000)
            || (! $directory && ($stat['nlink'] ?? 1) !== 1)
            || ! $this->contains($root, $real)) {
            throw $this->unsafe('Generated path contains a symlink, hardlink, special node or root escape.');
        }
    }

    private function assertAnyNode(string $root, string $path): void
    {
        $stat = @lstat($path);
        $real = realpath($path);
        $type = is_array($stat) ? (($stat['mode'] ?? 0) & 0170000) : 0;
        if (! is_array($stat) || $real === false || is_link($path)
            || ! in_array($type, [0040000, 0100000], true)
            || ($type === 0100000 && ($stat['nlink'] ?? 1) !== 1)
            || ! $this->contains($root, $real)) {
            throw $this->unsafe('Generated path contains a symlink, hardlink, special node or root escape.');
        }
    }

    private function walk(string $root, string $directory): void
    {
        $entries = scandir($directory);
        if (! is_array($entries)) {
            throw $this->unsafe('Generated tree could not be inspected.');
        }
        $case = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $folded = strtolower($entry);
            if (isset($case[$folded]) && $case[$folded] !== $entry) {
                throw $this->unsafe("Generated tree contains case-colliding entries [$entry].");
            }
            $case[$folded] = $entry;
            $path = $directory . '/' . $entry;
            $stat = @lstat($path);
            $type = is_array($stat) ? (($stat['mode'] ?? 0) & 0170000) : 0;
            if ($type === 0040000) {
                $this->assertNode($root, $path, true);
                $this->walk($root, $path);

                continue;
            }
            $this->assertNode($root, $path, false);
        }
    }

    private function publishFile(string $projectRoot, string $relative, string $contents, string $collisionCode): string
    {
        $root = $this->root($projectRoot);
        $path = $this->writablePath($root, $relative);
        $temporary = dirname($path) . '/.docara-tmp-' . bin2hex(random_bytes(12));
        $handle = @fopen($temporary, 'x+b');
        if ($handle === false) {
            throw $this->unsafe("Temporary file for [$relative] could not be created.");
        }
        try {
            $written = fwrite($handle, $contents);
            if ($written !== strlen($contents) || ! fflush($handle)) {
                throw $this->unsafe("Generated path [$relative] could not be written completely.");
            }
            if (function_exists('fsync')) {
                fsync($handle);
            }
        } finally {
            fclose($handle);
        }
        try {
            $this->assertNode($root, $temporary, false);
            if (! @link($temporary, $path)) {
                throw new PortableConfigurationException($collisionCode, "Generated path [$relative] appeared before atomic publish.");
            }
        } finally {
            @unlink($temporary);
        }
        $this->assertNode($root, $path, false);

        return $path;
    }

    private function contains(string $root, string $path): bool
    {
        $root = rtrim(FilesystemPath::normalize($root), '/');
        $path = FilesystemPath::normalize($path);

        return $path === $root || str_starts_with($path, $root . '/');
    }

    private function unsafe(string $message): PortableConfigurationException
    {
        return new PortableConfigurationException('SDK_WRITE_PATH_UNSAFE', $message);
    }
}
