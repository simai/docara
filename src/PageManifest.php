<?php

    namespace Simai\Docara;

    class PageManifest
    {
        private string $path;

        public function __construct(string $cachePath)
        {
            $this->path = $cachePath;
        }

        public function load(): array
        {
            if (!is_file($this->path)) {
                return [];
            }
            $data = json_decode(file_get_contents($this->path) ?: '[]', true);
            return is_array($data) ? $data : [];
        }

        public function save(array $data): void
        {
            $dir = dirname($this->path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            file_put_contents(
                $this->path,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }


        public function get(string $hash): ?array
        {
            $all = $this->load();
            return $all[$hash] ?? null;
        }


        public function put(string $hash, array $entry): void
        {
            $all = $this->load();
            $all[$hash] = $entry;
            $this->save($all);
        }
    }
