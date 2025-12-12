<?php

    namespace Simai\Docara\Handlers;

    use Illuminate\Support\Arr;
    use Illuminate\Support\Collection as BaseCollection;
    use Illuminate\Support\Str;
    use Simai\Docara\Cache\BuildCache;
    use Simai\Docara\Configurator;
    use Simai\Docara\File\OutputFile;

    class CollectionItemHandler
    {
        protected BaseCollection $myHandlers;

        protected BaseCollection $customConfig;

        protected Configurator $configurator;

        protected string $docDir = '';

        protected BuildCache $buildCache;

        protected array $docDirArray = [];

        public function __construct(BaseCollection $config, $handlers, Configurator $configurator, BuildCache $cache)
        {
            $this->customConfig = $config;
            $this->configurator = $configurator;
            $this->myHandlers = collect($handlers);
            $this->docDir = trim($_ENV['DOCS_DIR']);
            $this->docDirArray = explode('/', $this->docDir);
            $this->buildCache = $cache;
        }

        private function makeRelativePath($file): string
        {
            $segments = explode('/', trim(str_replace('\\', '/', $file->getRelativePath()), '/'));
            $segments = array_slice($segments, count($this->docDirArray));
            $segments[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            return implode('/', array_filter($segments));
        }

        public function handle($file, $pageData)
        {

            $relative = $this->makeRelativePath($file);
            $settingsHash = '';
            if (isset($this->configurator) && method_exists($this->configurator, 'settingsHashForFile')) {
                $settingsHash = $this->configurator->settingsHashForFile($file->getPathname());
            }
            $contentHash = md5($file->getContents() . $settingsHash . $this->buildCache->globalHash());
            if ($this->buildCache->isEnabled()) {
                if ($this->buildCache->shouldSkip($relative, $contentHash)) {
                    return collect();
                } else {
                    $this->configurator->console->writeln(PHP_EOL . "<comment>=== Build {$relative} ===</comment>");
                }
            }
            $handler = $this->myHandlers->first(function ($handler) use ($file) {
                return $handler->shouldHandle($file);
            });
            $name = collect(explode('/', trim(str_replace('\\', '/', $file->getRelativePath()), '/')))
                ->skip(count($this->docDirArray) + 1)
                ->implode('/');
            $filenameSlug = preg_replace('/(\.blade\.(md|php)|\.md|\.php)$/i', '', $file->getFilename());
            $name = $name . '/' . $filenameSlug;
            $pageData->setPageVariableToCollectionItem($this->getCollectionName($file), $name);

            $locale = method_exists($pageData->page, 'locale') ? $pageData->page->locale() : ($pageData->page->language ?? '');
            $resolvedPath = '/' . ltrim(($locale ? "{$locale}/" : '') . $name, '/');
            $pageData->resolveLayoutForPath($resolvedPath);
            if ($pageData->page === null) {
                return null;
            }

            $results = $handler->handleCollectionItem($file, $pageData)
                ->map(function ($outputFile, $templateToExtend) use ($file) {
                    if ($templateToExtend) {
                        $outputFile->data()->setExtending($templateToExtend);
                    }

                    $path = $outputFile->data()->page->getPath();

                    return $path ? new OutputFile(
                        $file,
                        dirname($path),
                        basename($path, '.' . $outputFile->extension()),
                        $outputFile->extension(),
                        $outputFile->contents(),
                        $outputFile->data(),
                    ) : null;
                })
                ->filter()
                ->values();
            if ($results->isNotEmpty()) {
                $outputPath = $results->first()->path() . '/' . $results->first()->name();
                $this->buildCache->store($relative, $contentHash, ['output' => $outputPath]);
            }

            return $results;
        }

        public function shouldHandle($file): bool
        {
            return $this->isInCollectionDirectory($file)
                && ! Str::startsWith($file->getFilename(), ['.', '_']);
        }

        private function isInCollectionDirectory($file): bool
        {
            $base = $file->topLevelDirectory();

            return $base === $this->docDirArray[0] && $this->hasCollectionNamed($this->getCollectionName($file));
        }

        private function hasCollectionNamed($candidate): bool
        {
            return Arr::get($this->customConfig, 'collections.' . $candidate) !== null;
        }

        private function getCollectionName($file): string
        {
            return $this->getName($file);
        }

        protected function getName($file): string
        {
            if (! count($this->docDirArray)) {
                return '';
            }

            return collect(explode('/', trim(str_replace('\\', '/', $file->getRelativePath()), '/')))
                ->take(count($this->docDirArray) + 1)
                ->implode('-');
        }
    }
