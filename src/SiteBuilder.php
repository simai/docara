<?php

namespace Simai\Docara;

use Illuminate\Container\Container;
use Simai\Docara\Cache\BuildCache;
use Simai\Docara\Console\ConsoleOutput;
use Simai\Docara\File\Filesystem;
use Simai\Docara\File\InputFile;

class SiteBuilder
{
    private string $cachePath;

    private Filesystem $files;

    private array $handlers;

    private $outputPathResolver;

    private ConsoleOutput $consoleOutput;

    private bool $useCache = false;

    private ?BuildCache $buildCache;

    public function __construct(
        Filesystem $files,
        string $cachePath,
        $outputPathResolver,
        ConsoleOutput $consoleOutput,
        array $handlers = [],
        ?BuildCache $buildCache = null,
    ) {
        $this->files = $files;
        $this->cachePath = $cachePath;
        $this->outputPathResolver = $outputPathResolver;
        $this->consoleOutput = $consoleOutput;
        $this->handlers = $handlers;
        $this->buildCache = $buildCache;
    }

    public function setUseCache($useCache): static
    {
        $this->useCache = (bool) $useCache;

        return $this;
    }

    public function build($source, $destination, $siteData)
    {
        $this->prepareDirectory($this->cachePath, ! $this->useCache);
        $generatedFiles = $this->generateFiles($source, $siteData);
        $this->prepareDirectory($destination, $this->shouldCleanDestination());
        $outputFiles = $this->writeFiles($generatedFiles, $destination);
        $this->cleanup();

        return $outputFiles;
    }

    public function registerHandler($handler): void
    {
        $this->handlers[] = $handler;
    }

    private function shouldCleanDestination(): bool
    {
        if ($this->buildCache && $this->buildCache->isEnabled()) {
            return false;
        }

        return true;
    }

    private function prepareDirectory($directory, $clean = false): void
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        if ($clean) {
            $this->files->cleanDirectory($directory);
        }
    }

    private function cleanup(): void
    {
        if (!$this->useCache) {
            $this->files->deleteDirectory($this->cachePath);
        }
    }

    private function generateFiles($source, $siteData)
    {
        $files = collect($this->files->files($source));
        $this->consoleOutput->startProgressBar('build', $files->count());

        return $files->map(fn ($file) => new InputFile($file))
            ->flatMap(function ($file) use ($siteData) {
                $this->consoleOutput->progressBar('build')->advance();

                return $this->handle($file, $siteData);
            });
    }

    private function writeFiles($files, $destination)
    {
        $this->consoleOutput->writeWritingFiles();

        return $files->mapWithKeys(function ($file) use ($destination) {
            $outputLink = $this->writeFile($file, $destination);

            return [$outputLink => $file->inputFile()->getPageData()];
        });
    }

    private function writeFile($file, $destination): string
    {
        $directory = $this->getOutputDirectory($file);
        $this->prepareDirectory("{$destination}/{$directory}");
        $file->putContents("{$destination}/{$this->getOutputPath($file)}");

        return $this->getOutputLink($file);
    }

    private function handle($file, $siteData)
    {
        $meta = $this->getMetaData($file, $siteData->page->baseUrl);

        $pageData = PageData::withPageMetaData($siteData, $meta);
        Container::getInstance()->instance('pageData', $pageData);
//        $pageData->resolveLayoutForPath($meta['path'] ?? '/');

        return $this->getHandler($file)->handle($file, $pageData);
    }

    private function getHandler($file)
    {
        return collect($this->handlers)->first(function ($handler) use ($file) {
            return $handler->shouldHandle($file);
        });
    }

    private function getMetaData($file, $baseUrl): array
    {
        $filename = $file->getFilenameWithoutExtension();
        $extension = $file->getFullExtension();
        $relativePath = str_replace('\\', '/', $file->getRelativePath());
        $path = rightTrimPath($this->outputPathResolver->link($relativePath, $filename, $file->getExtraBladeExtension() ?: 'html'));
        $url = rightTrimPath($baseUrl) . '/' . trimPath($path);
        $modifiedTime = $file->getLastModifiedTime();

        return compact('filename', 'baseUrl', 'path', 'relativePath', 'extension', 'url', 'modifiedTime');
    }

    private function getOutputDirectory($file): string
    {
        if ($permalink = $this->getFilePermalink($file)) {
            return urldecode(dirname($permalink));
        }

        return urldecode($this->outputPathResolver->directory($file->path(), $file->name(), $file->extension(), $file->page(), $file->prefix()));
    }

    private function getOutputPath($file): string
    {
        if ($permalink = $this->getFilePermalink($file)) {
            return $permalink;
        }

        return resolvePath(urldecode($this->outputPathResolver->path(
            $file->path(),
            $file->name(),
            $file->extension(),
            $file->page(),
            $file->prefix(),
        )));
    }

    private function getOutputLink($file): string
    {
        if ($permalink = $this->getFilePermalink($file)) {
            return $permalink;
        }

        return rightTrimPath(urldecode($this->outputPathResolver->link(
            str_replace('\\', '/', $file->path()),
            $file->name(),
            $file->extension(),
            $file->page(),
        )));
    }

    private function getFilePermalink($file): ?string
    {
        return $file->data()->page->permalink ? '/' . resolvePath(urldecode($file->data()->page->permalink)) : null;
    }
}
