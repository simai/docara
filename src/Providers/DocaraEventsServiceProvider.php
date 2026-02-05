<?php

    namespace Simai\Docara\Providers;

    use Illuminate\Contracts\Container\BindingResolutionException;
    use Illuminate\Support\Collection;
    use Simai\Docara\Configurator;
    use Simai\Docara\Console\ConsoleOutput;
    use Simai\Docara\Docara;
    use Simai\Docara\ShaResolver;
    use Simai\Docara\Support\ServiceProvider;

    class DocaraEventsServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            $events = $this->app->events;

            $events->beforeBuild(fn (Docara $docara) => $this->onBeforeBuild($docara));

            $events->afterCollections(fn (Docara $docara) => $this->onAfterCollections($docara));

            $events->afterBuild(fn (Docara $docara) => $this->onAfterBuild($docara));
        }

        /**
         * @throws BindingResolutionException
         */
        private function onBeforeBuild(Docara $docara): void
        {
            $configurator = $docara->configurator();
            $locales = $this->normalizeLocales($docara->getConfig('locales'));

            $mergedLocales = $this->mergeLocales($locales);
            $docara->setConfig('locales', $mergedLocales);

            try {
                $configurator->prepare($mergedLocales, $docara);
                // keep configurator available in page data for Blade ($page->configurator)
                $docara->setConfig('configurator', $configurator);
            } catch (\Throwable $e) {
                $this->console()->warn("Configurator prepare failed: {$e->getMessage()}");
            }

            try {
                $sha = $this->app->make(ShaResolver::class)->resolve();
                if ($sha) {
                    $docara->setConfig('sha', $sha);
                }
            } catch (\Throwable $e) {
                $this->console()->warn("Fetch SHA failed: {$e->getMessage()}");
            }
        }

        private function mergeLocales(array $locales): array
        {
            $tempConfig = $this->tempConfigPath();
            if (! is_file($tempConfig)) {
                return $locales;
            }

            $merged = $locales;
            $tempConfigJson = json_decode(file_get_contents($tempConfig), true) ?: [];
            $tempLocales = $tempConfigJson['locales'] ?? $tempConfigJson;
            if (! is_array($tempLocales)) {
                return $merged;
            }
            foreach ($tempLocales as $key => $value) {
                if ($key === 'sha') {
                    continue;
                }
                $merged[$key] = $value;
            }

            return $merged;
        }

        private function normalizeLocales($locales): array
        {
            if ($locales instanceof Collection) {
                return $locales->toArray();
            }

            return is_array($locales) ? $locales : [];
        }

        /**
         * @throws BindingResolutionException
         */
        private function onAfterCollections(Docara $docara): void
        {
            $configurator = $docara->configurator();
            $index = [];
            $paths = [];

            foreach ($docara->getConfig('collections') as $collectionName => $config) {
                $collection = $docara->getCollection($collectionName);
                foreach ($collection as $page) {
                    $paths[] = $page->getPath();
                }
            }

            $configurator->setPaths($paths);
        }

        /**
         * @throws BindingResolutionException
         */
        private function onAfterBuild(Docara $docara): void
        {
            try {
                $this->writeSearchIndexes($docara);
            } catch (\Throwable $e) {
                $this->console()->warn("Write search indexes failed: {$e->getMessage()}");
            }
        }




        private function writeSearchIndexes(Docara $docara): void
        {
            $configurator = $docara->configurator();
            $index = $configurator->translateSources;
            $dest = $docara->getDestinationPath();
            if (! file_exists($dest)) {
                mkdir($dest, 0755, true);
            }
            foreach ($index as $lang => $page) {
                file_put_contents($dest . "/search-index_{$lang}.json", json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        private function console(): ConsoleOutput
        {
            return $this->app['consoleOutput'];
        }

        private function tempConfigPath(): string
        {
            return $this->app->path('temp/translations/.config.json');
        }

        private function readTempConfig(string $path): array
        {
            if (! is_file($path)) {
                return [];
            }

            $json = json_decode(file_get_contents($path), true);

            return is_array($json) ? $json : [];
        }

        private function writeTempConfig(string $path, array $config): void
        {
            $directory = dirname($path);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
