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
            $stubDocs = $stubs . '/source/' . $docsDir;

            $preserveDocs = $this->files->isDirectory($targetDocs);
            $docsBackup = null;

            if ($preserveDocs) {
                $docsBackup = $this->base . '/source/' . $docsDir . '_backup_' . uniqid();
                $this->files->move($targetDocs, $docsBackup);
            }

            $this->files->copyDirectory($stubs, $this->base);

            if ($preserveDocs) {
                if ($this->files->isDirectory($targetDocs)) {
                    $this->files->deleteDirectory($targetDocs);
                }
                $this->files->move($docsBackup, $targetDocs);
            } elseif ($this->files->isDirectory($stubDocs) && ! $this->files->isDirectory($targetDocs)) {
                $this->files->copyDirectory($stubDocs, $targetDocs);
            }

            if ($existingConfig !== null) {
                $this->files->put($configPath, $existingConfig);
            }

            return $this;
        }
    }
