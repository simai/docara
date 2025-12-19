<?php

namespace Simai\Docara\Loaders;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Simai\Docara\Collection\Collection;
use Simai\Docara\Collection\CollectionItem;
use Simai\Docara\Console\ConsoleOutput;
use Simai\Docara\File\InputFile;
use Simai\Docara\IterableObject;
use Simai\Docara\IterableObjectWithDefault;
use Simai\Docara\PageVariable;

class CollectionDataLoader
{
    private Filesystem $filesystem;

    private ConsoleOutput $consoleOutput;

    private $pathResolver;

    private $handlers;

    private $source;

    private $pageSettings;

    private $collectionSettings;

    public function __construct(Filesystem $filesystem, ConsoleOutput $consoleOutput, $pathResolver, $handlers = [])
    {
        $this->filesystem = $filesystem;
        $this->pathResolver = $pathResolver;
        $this->handlers = collect($handlers);
        $this->consoleOutput = $consoleOutput;
    }

    public function load($siteData, $source): array
    {
        $this->source = $source;
        $this->pageSettings = $siteData->page;
        $this->collectionSettings = collect($siteData->collections);
        $this->consoleOutput->startProgressBar('collections');

        $collections = $this->collectionSettings->map(function ($collectionSettings, $collectionName) {
            $collection = Collection::withSettings($collectionSettings, $collectionName);
            $collection->loadItems($this->buildCollection($collection));

            return $collection->updateItems($collection->map(function ($item) {
                return $this->addCollectionItemContent($item);
            }));
        });

        return $collections->all();
    }

    private function buildCollection($collection)
    {
        $collectionPath = explode('-', $collection->name);
        $collectionPath = implode('/', $collectionPath);
        $path = "{$this->source}/{$collectionPath}";
        if (! $this->filesystem->exists($path)) {
            return collect();
        }

        return collect($this->filesystem->files($path))
            ->reject(function ($file) {
                return Str::startsWith($file->getFilename(), '_');
            })->filter(function ($file) {
                return $this->hasHandler($file);
            })->tap(function ($files) {
                $this->consoleOutput->progressBar('collections')->addSteps($files->count());
            })->map(function ($file) {
                return new InputFile($file);
            })->map(function ($inputFile) use ($collection) {
                $this->consoleOutput->progressBar('collections')->advance();

                return $this->buildCollectionItem($inputFile, $collection);
            });
    }

    private function buildCollectionItem($file, $collection): CollectionItem
    {
        $data = $this->pageSettings
            ->merge(['section' => 'content'])
            ->merge($collection->settings)
            ->merge($this->getHandler($file)->getItemVariables($file));
        $data->put('_meta', new IterableObject($this->getMetaData($file, $collection, $data)));
        $path = $this->getPath($data, $collection);
        $data->_meta->put('path', $path)->put('url', $this->buildUrls($path));

        return CollectionItem::build($collection, $data);
    }

    private function addCollectionItemContent($item)
    {
        $file = $this->filesystem->getFile($item->getSource(), $item->getFilename() . '.' . $item->getExtension());

        if ($file) {
            $item->setContent($this->getHandler($file)->getItemContent($file));
        }

        return $item;
    }

    private function hasHandler($file): bool
    {
        return $this->handlers->contains(function ($handler) use ($file) {
            return $handler->shouldHandle($file);
        });
    }

    private function getHandler($file)
    {
        return $this->handlers->first(function ($handler) use ($file) {
            return $handler->shouldHandle($file);
        });
    }

    private function getMetaData($file, $collection, $data): array
    {
        $filename = $file->getFilenameWithoutExtension();
        $baseUrl = $data->baseUrl;
        $relativePath = $file->getRelativePath();
        $extension = $file->getFullExtension();
        $collectionName = $collection->name;
        $collection = $collectionName;
        $source = $file->getPath();
        $modifiedTime = $file->getLastModifiedTime();

        return compact('filename', 'baseUrl', 'relativePath', 'extension', 'collection', 'collectionName', 'source', 'modifiedTime');
    }

    private function buildUrls($paths): ?IterableObjectWithDefault
    {
        $urls = collect($paths)->map(function ($path) {
            $docsDir = $_ENV['DOCS_DIR'] ?? getenv('DOCS_DIR') ?? 'docs';
            $docsDir = trim($docsDir, '/\\') ?: 'docs';
            $pattern = '#' . preg_quote($docsDir, '#') . '#';
            $path = preg_replace($pattern, '', $path);

            return rightTrimPath($this->pageSettings->get('baseUrl')) . '/' . trimPath($path);
        });

        return $urls->count() ? new IterableObjectWithDefault($urls) : null;
    }

    private function getPath($data, $collection): ?IterableObjectWithDefault
    {
        $links = $this->pathResolver->link(
            $data->path,
            new PageVariable($data),
            Arr::get($collection->settings, 'transliterate', true),
        );

        return $links->count() ? new IterableObjectWithDefault($links) : null;
    }
}
