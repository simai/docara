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

            $docsDir = trim($_ENV['DOCS_DIR'] ?? getenv('DOCS_DIR') ?? 'docs', '/\\');
            $targetDocs = $this->base . '/source/' . $docsDir;
            $hasDocs = $this->files->isDirectory($targetDocs) || file_exists($targetDocs);
            $this->log("DOCS_DIR={$docsDir}; target docs exists: " . ($hasDocs ? 'yes' : 'no'));

            foreach (array_diff(scandir($stubs) ?: [], ['.', '..']) as $item) {
                $src = $stubs . '/' . $item;
                $dest = $this->base . '/' . $item;

                if ($item === 'source' && $this->files->isDirectory($src)) {
                    foreach (array_diff(scandir($src) ?: [], ['.', '..']) as $sourceItem) {
                        $srcChild = $src . '/' . $sourceItem;
                        $destChild = $dest . '/' . $sourceItem;

                        if ($sourceItem === $docsDir && $hasDocs) {
                            $this->log("Skip copying docs from stubs ({$sourceItem}) because target exists.");
                            continue;
                        }

                        if ($this->files->isDirectory($srcChild)) {
                            $this->files->copyDirectory($srcChild, $destChild);
                            if ($sourceItem === $docsDir) {
                                $this->log("Copied docs from stubs to {$destChild} (target was missing).");
                            }
                        } else {
                            $this->files->copy($srcChild, $destChild);
                        }
                    }
                } else {
                    if ($this->files->isDirectory($src)) {
                        $this->files->copyDirectory($src, $dest);
                    } else {
                        $this->files->copy($src, $dest);
                    }
                }
            }

            if ($existingConfig !== null) {
                $this->files->put($configPath, $existingConfig);
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
    }
