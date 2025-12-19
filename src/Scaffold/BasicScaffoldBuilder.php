<?php

namespace Simai\Docara\Scaffold;

class BasicScaffoldBuilder extends ScaffoldBuilder
{
    public function init($preset = null)
    {
        return $this;
    }

    public function build()
    {
        $stubs = __DIR__ . '/../../stubs/site';
        $configPath = $this->base . '/config.php';
        $existingConfig = $this->files->exists($configPath) ? $this->files->get($configPath) : null;
        $envPath = $this->base . '/.env';
        $existingEnv = $this->files->exists($envPath) ? $this->files->get($envPath) : null;

        $docsDirEnv = $_ENV['DOCS_DIR'] ?? getenv('DOCS_DIR') ?? null;
        $docsDir = trim((string) $docsDirEnv, '/\\');
        if ($docsDir === '') {
            $docsDir = 'docs';
        }
        $stubDocs = $stubs . '/source/' . $docsDir;
        $targetDocs = $this->base . '/source/' . $docsDir;
        $hasDocs = $this->files->isDirectory($targetDocs) || file_exists($targetDocs);
        $this->log("DOCS_DIR={$docsDir}; target docs exists: " . ($hasDocs ? 'yes' : 'no'));

        foreach (array_diff(scandir($stubs) ?: [], ['.', '..']) as $item) {
            $src = $stubs . '/' . $item;
            $dest = $this->base . '/' . $item;

            if ($item === '.env' && $existingEnv !== null) {
                $this->log('Skip copying .env from stubs because it already exists.');
                continue;
            }

            if ($item === '.env.example' && $this->files->exists($dest)) {
                $this->log('Skip copying .env.example from stubs because it already exists.');
                continue;
            }

            if ($item === '.gitignore' && $this->files->exists($dest)) {
                $this->log('Skip copying .gitignore from stubs because it already exists.');
                continue;
            }

            if ($item === 'source' && $this->files->isDirectory($src)) {
                foreach (array_diff(scandir($src) ?: [], ['.', '..']) as $sourceItem) {
                    $srcChild = $src . '/' . $sourceItem;
                    $destChild = $dest . '/' . $sourceItem;

                    if ($sourceItem === '_core') {
                        [$copied, $updated, $skipped] = $this->copyDirectoryPreservingUserChanges($srcChild, $destChild);
                        $this->log("Copied _core (preserve user changes): copied={$copied}, updated={$updated}, skipped={$skipped}");
                        continue;
                    }

                    if ($sourceItem === $docsDir && $hasDocs) {
                        $this->log("Skip copying docs from stubs ({$sourceItem}) because target exists.");
                        continue;
                    }

                    if (! $this->files->exists($srcChild)) {
                        $this->log("Skip missing stub entry: {$srcChild}");
                        continue;
                    }

                    if ($this->files->isDirectory($srcChild)) {
                        $this->files->copyDirectory($srcChild, $destChild);
                        if ($sourceItem === $docsDir) {
                            $this->log("Copied docs from stubs to {$destChild} (target was missing).");
                        }
                    } else {
                        $destDir = dirname($destChild);
                        if (! $this->files->isDirectory($destDir)) {
                            $this->files->makeDirectory($destDir, 0755, true);
                        }
                        $this->files->copy($srcChild, $destChild);
                    }
                }
            } else {
                if (! $this->files->exists($src)) {
                    $this->log("Skip missing stub entry: {$src}");
                    continue;
                }
                if ($this->files->isDirectory($src)) {
                    $this->files->copyDirectory($src, $dest);
                } else {
                    $destDir = dirname($dest);
                    if (! $this->files->isDirectory($destDir)) {
                        $this->files->makeDirectory($destDir, 0755, true);
                    }
                    $this->files->copy($src, $dest);
                }
            }
        }

        if ($existingConfig !== null) {
            $this->files->put($configPath, $existingConfig);
        }

        if ($existingEnv !== null) {
            $this->files->put($envPath, $existingEnv);
        }

        return $this;
    }

    private function log(string $message): void
    {
        if (isset($this->console) && method_exists($this->console, 'comment')) {
            $this->console->comment($message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Copy directory contents while preserving user changes (whitespace-insensitive hash).
     * Returns [copied, updated, skipped].
     */
    private function copyDirectoryPreservingUserChanges(string $source, string $target): array
    {
        $copied = $updated = $skipped = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($source))), '/');
            if (str_starts_with($relative, '.git/')) {
                continue;
            }

            $dest = rtrim($target, '/\\') . '/' . $relative;
            $destDir = dirname($dest);
            if (! $this->files->isDirectory($destDir)) {
                $this->files->makeDirectory($destDir, 0755, true);
            }

            if (! $this->files->exists($dest)) {
                $this->files->copy($file->getPathname(), $dest);
                $copied++;
                continue;
            }

            $srcHash = $this->normalizedHash($file->getPathname());
            $dstHash = $this->normalizedHash($dest);

            if ($srcHash === $dstHash) {
                $this->files->copy($file->getPathname(), $dest);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return [$copied, $updated, $skipped];
    }

    /**
     * Normalize text file content (line endings + whitespace) before hashing.
     * Binary files are hashed raw.
     */
    private function normalizedHash(string $path): string
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        if (str_contains($contents, "\0")) {
            return md5($contents);
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $contents);
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        return md5($normalized);
    }
}
