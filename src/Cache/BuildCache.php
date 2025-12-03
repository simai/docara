<?php

    namespace Simai\Docara\Cache;

    use FilesystemIterator;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    /**
     * Class BuildCache
     * Helper class for building and managing cache
     */
    class BuildCache
    {
        private string $cachePath;

        private string $outputRoot;

        private $globalHash;
        private ?string $githubSha = null;
        private bool $pretty;

        private array $manifest = [
            'version' => 1,
            'global' => null,
            'sha' => null,
            'docs' => [],
        ];

        private string $manifestFile;

        private bool $dirty = false;

        private bool $cacheEnable;

        /**
         * BuildCache constructor
         */
        public function __construct(bool $cacheEnable = false, bool $pretty = false)
        {
            $this->cacheEnable = $cacheEnable;
            $this->pretty = $pretty;
        }

        public function __destruct()
        {
            $this->saveManifest();
        }

        public function useCache(): bool
        {
            return $this->cacheEnable;
        }

        public function isEnabled(): bool
        {
            return $this->cacheEnable;
        }

        public function globalHash(): string
        {
            if ($this->globalHash === null) {
                $this->globalHash = $this->computeGlobalHash();
            }

            return $this->globalHash;
        }

        public function setGithubSha(?string $sha): void
        {
            // If SHA changes, invalidate cached docs so everything rebuilds.
            if ($sha !== null && isset($this->manifest['sha']) && $this->manifest['sha'] !== $sha) {
                $this->manifest['docs'] = [];
                $this->manifest['global'] = null;
                $this->manifest['sha'] = null;
                $this->markDirty();
            }

            $this->githubSha = $sha;
        }

        private function basePath($fileName): string
        {

            $root = getcwd();

            return $root . DIRECTORY_SEPARATOR . $fileName;
        }

        private function computeGlobalHash(): string
        {

            $targets = [
                $this->basePath('/config.php'),
                $this->basePath('/bootstrap.php'),
                $this->basePath('/source/_core'),
            ];

            $files = [];
            foreach ($targets as $path) {
                if (is_dir($path)) {
                    $files = array_merge($files, $this->collectFiles($path));
                } elseif (is_file($path)) {
                    $files[] = $path;
                }
            }

            sort($files);
            $ctx = hash_init('md5');
            foreach ($files as $file) {
                hash_update($ctx, $file);
                hash_update($ctx, (string) filemtime($file));
                hash_update($ctx, file_get_contents($file) ?: '');
            }

            return hash_final($ctx);
        }

        private function collectFiles(string $root): array
        {
            $files = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
            sort($files);

            return $files;
        }

        private function loadManifest(): void
        {
            if (! is_file($this->manifestFile)) {
                return;
            }

            $json = file_get_contents($this->manifestFile);
            if ($json === false) {
                return;
            }

            $data = json_decode($json, true);
            if (! is_array($data) || ! isset($data['version'])) {
                return;
            }

            if ((int) $data['version'] !== 1) {
                return;
            }

            $this->manifest = array_merge($this->manifest, $data);
        }

        public function saveManifest(): void
        {
            if (! $this->dirty) {
                return;
            }

            if (! is_dir($this->cachePath)) {

                mkdir($this->cachePath, 0777, true);
            }

            file_put_contents(
                $this->manifestFile,

                json_encode($this->manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
            $this->dirty = false;
        }

        public function markDirty(): void
        {

            $this->dirty = true;
        }

        /**
         * Set cache path
         */
        public function setCachePath(string $path, string $outputRoot): void
        {
            $this->outputRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $outputRoot), DIRECTORY_SEPARATOR);
            $this->cachePath = rtrim($path, DIRECTORY_SEPARATOR);
            $this->manifestFile = $this->cachePath . DIRECTORY_SEPARATOR . 'docs-cache.json';
            $this->loadManifest();
        }

        public function shouldSkip(string $relativePath, string $contentHash): bool
        {
            if (! $this->isEnabled()) {
                return false;
            }

            $doc = $this->manifest['docs'][$relativePath] ?? null;
            if (! $doc) {
                return false;
            }

            if (($this->manifest['global'] ?? null) !== $this->globalHash()) {
                return false;
            }

            if ($this->githubSha !== null && ($this->manifest['sha'] ?? null) !== $this->githubSha) {
                return false;
            }

            $output = $doc['output'] ?? null;
            $root = rtrim($this->outputRoot, '/\\');
            $relative = ltrim(str_replace('\\', '/', $output), '/');
            $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (! str_ends_with($path, 'index.html')) {
                if($this->pretty) {
                    $path .= DIRECTORY_SEPARATOR . 'index.html';
                } else {
                    $path .= '.html';
                }
            }
            if (! $output || ! is_file($path)) {
                return false;
            }

            // TODO - добавить лог для console что идет процессинг файла
            return ($doc['hash'] ?? null) === $contentHash;
        }

        public function store(string $relativePath, string $contentHash, array $meta = []): void
        {
            if (! $this->isEnabled()) {
                return;
            }

            $this->manifest['docs'][$relativePath] = array_merge(
                [
                    'hash' => $contentHash,
                    'updated_at' => time(),
                ],
                $meta
            );
            $this->manifest['global'] = $this->globalHash();
            $this->manifest['sha'] = $this->githubSha;
            $this->markDirty();
        }
    }
