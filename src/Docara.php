<?php

    namespace Simai\Docara;

    use Illuminate\Contracts\Container\BindingResolutionException;
    use Illuminate\Contracts\Container\Container;
    use Illuminate\Support\Traits\Macroable;
    use Simai\Docara\File\Filesystem;
    use Simai\Docara\Loaders\CollectionRemoteItemLoader;
    use Simai\Docara\Loaders\DataLoader;
    use Illuminate\Support\Collection;

    class Docara
    {
        use Macroable;

        public Container $app;

        public RuleLoader $ruleLoader;

        protected $pageInfo;

        protected Collection $outputPaths;

        protected $siteData;

        protected DataLoader $dataLoader;

        protected CollectionRemoteItemLoader $remoteItemLoader;

        protected SiteBuilder $siteBuilder;

        protected $verbose;

        protected static array $commands = [];

        public function __construct(
            Container $app,
            DataLoader $dataLoader,
            CollectionRemoteItemLoader $remoteItemLoader,
            SiteBuilder $siteBuilder,
        ) {
            $this->app = $app;
            $this->dataLoader = $dataLoader;
            $this->remoteItemLoader = $remoteItemLoader;
            $this->siteBuilder = $siteBuilder;
            $this->ruleLoader = $app->make(\Simai\Docara\RuleLoader::class);
        }

        public function build($useCache = false): Docara
        {
            $this->siteData = $this->dataLoader->loadSiteData($this->app->config);
            $this->ruleLoader->getRules();
            return $this->fireEvent('beforeBuild')
                ->buildCollections()
                ->fireEvent('afterCollections')
                ->buildSite($useCache)
                ->fireEvent('afterBuild')
                ->cleanup();
        }

        public static function registerCommand($command): void
        {
            self::$commands[] = $command;
        }

        /**
         * @throws BindingResolutionException
         */
        public function configurator(): Configurator
        {
            return $this->app->make(Configurator::class);
        }

        public static function addUserCommands($app, $container): void
        {
            foreach (self::$commands as $command) {
                $app->add(new $command($container));
            }
        }

        protected function buildCollections(): static
        {
            $this->remoteItemLoader->write($this->siteData->collections, $this->getSourcePath());
            $collectionData = $this->dataLoader->loadCollectionData($this->siteData, $this->getSourcePath());
            $this->siteData = $this->siteData->addCollectionData($collectionData);

            return $this;
        }

        protected function buildSite($useCache): static
        {
            $this->pageInfo = $this->siteBuilder
                ->setUseCache($useCache)
                ->build(
                    $this->getSourcePath(),
                    $this->getDestinationPath(),
                    $this->siteData,
                );
            $this->outputPaths = $this->pageInfo->keys();

            return $this;
        }

        protected function cleanup(): static
        {
            $this->remoteItemLoader->cleanup();

            return $this;
        }

        protected function fireEvent($event): static
        {
            $this->app->events->fire($event, $this);

            return $this;
        }

        public function getSiteData()
        {
            return $this->siteData;
        }

        public function getEnvironment()
        {
            return $this->app['env'];
        }

        public function getCollection($collection)
        {
            return $this->siteData->get($collection);
        }

        public function getCollections()
        {
            return $this->siteData->get('collections') ?
                $this->siteData->get('collections')->keys() :
                $this->siteData->except('page');
        }

        public function getConfig($key = null)
        {
            return $key ? data_get($this->siteData->page, $key) : $this->siteData->page;
        }

        public function setConfig($key, $value): static
        {
            $this->siteData->set($key, $value);
            $this->siteData->page->set($key, $value);

            return $this;
        }

        public function getSourcePath()
        {
            return $this->app->buildPath['source'];
        }

        public function setSourcePath($path): static
        {
            $this->app->buildPath = [
                'source' => $path,
                'destination' => $this->app->buildPath['destination'],
            ];

            return $this;
        }

        public function getDestinationPath()
        {
            return $this->app->buildPath['destination'];
        }

        public function setDestinationPath($path): static
        {
            $this->app->buildPath = [
                'source' => $this->app->buildPath['source'],
                'destination' => $path,
            ];

            return $this;
        }

        public function getFilesystem()
        {
            return $this->app->make(Filesystem::class);
        }

        public function getOutputPaths(): \Illuminate\Support\Collection
        {
            return $this->outputPaths ?: collect();
        }

        public function getPages(): \Illuminate\Support\Collection
        {
            return $this->pageInfo ?: collect();
        }

        public function readSourceFile($fileName)
        {
            return $this->getFilesystem()->get($this->getSourcePath() . '/' . $fileName);
        }

        public function writeSourceFile($fileName, $contents)
        {
            return $this->getFilesystem()->putWithDirectories($this->getSourcePath() . '/' . $fileName, $contents);
        }

        public function readOutputFile($fileName)
        {
            return $this->getFilesystem()->get($this->getDestinationPath() . '/' . $fileName);
        }

        public function writeOutputFile($fileName, $contents)
        {
            return $this->getFilesystem()->putWithDirectories($this->getDestinationPath() . '/' . $fileName, $contents);
        }
    }
