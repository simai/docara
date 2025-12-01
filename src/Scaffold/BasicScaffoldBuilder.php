<?php

    namespace Simai\Docara\Scaffold;

    class BasicScaffoldBuilder extends ScaffoldBuilder
    {
        public function init($preset = null): static
        {
            return $this;
        }

        public function build(): static
        {
            $stubs = __DIR__ . '/../../stubs/site';
            $configPath = $this->base . '/config.php';
            $existingConfig = $this->files->exists($configPath) ? $this->files->get($configPath) : null;

            $this->files->copyDirectory($stubs, $this->base);

            $targetDocs = $this->base . '/source/docs';
            $stubDocs = $stubs . '/source/docs';
            if ($this->files->isDirectory($targetDocs) && $this->files->isDirectory($stubDocs)) {
                $this->files->deleteDirectory($targetDocs);
            }
            if ($this->files->isDirectory($stubDocs) && ! $this->files->isDirectory($targetDocs)) {
                $this->files->copyDirectory($stubDocs, $targetDocs);
            }

            if ($existingConfig !== null) {
                $this->files->put($configPath, $existingConfig);
            }

            return $this;
        }
    }
