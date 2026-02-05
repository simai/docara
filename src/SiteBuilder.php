<?php

    namespace Simai\Docara;

    use Illuminate\Container\Container;
    use Illuminate\Support\Str;
    use Simai\Docara\Cache\BuildCache;
    use Simai\Docara\Console\ConsoleOutput;
    use Simai\Docara\File\Filesystem;
    use Simai\Docara\File\InputFile;
    use Simai\Docara\RuleLoader;

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
            $this->writeIndexRedirects($destination);
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

        private function writeIndexRedirects(string $destination): void
        {
            $redirects = Container::getInstance()->config->get('docara.indexRedirects', []);
            foreach ($redirects as $locale => $paths) {
                foreach ($paths as $from => $to) {
                    $fromPath = trim($from, '/');
                    $outputPath = resolvePath(urldecode($this->outputPathResolver->path(
                        $fromPath,
                        'index',
                        'html',
                    )));
                    $fullPath = rtrim($destination, '/\\') . '/' . ltrim($outputPath, '/');

                    if (file_exists($fullPath)) {
                        continue;
                    }

                    $redirectTo = '/' . trim($to, '/') . '/';
                    $this->prepareDirectory(dirname($fullPath));
                    file_put_contents($fullPath, $this->redirectTemplate($redirectTo));
                }
            }
        }

        private function redirectTemplate(string $redirectTo): string
        {
            $escaped = htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8');

            return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Redirecting…</title>
    <meta http-equiv="refresh" content="0;url={$escaped}">
    <link rel="canonical" href="{$escaped}">
</head>
<body>
    <p>Redirecting to <a href="{$escaped}">{$escaped}</a></p>
</body>
</html>
HTML;
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
            $page = $file->data()->page;
            $meta = $page->_meta ?? [];

            $outputPath = $this->getOutputPath($file);
            $directory = dirname($outputPath);
            $this->prepareDirectory("{$destination}/{$directory}");

            $contents = $file->contents();
            // If content is binary/null (e.g., CopyFile), just delegate to putContents without injections.
            if (! is_string($contents)) {
                $file->putContents("{$destination}/{$outputPath}");
            } else {
                // inject module assets on the fly (no need for afterBuild scan)
                $ruleLoader = Container::getInstance()->make(RuleLoader::class);
                $moduleArr = $ruleLoader->findModules($contents);
                if (! empty($moduleArr)) {
                    $contents = $this->injectAssets($contents, $moduleArr, $destination);
                }

                file_put_contents("{$destination}/{$outputPath}", $contents);
            }

            // Sync page path with final output path so menus/active states use the real URL.
            $webPath = '/' . trim(str_replace('\\', '/', $outputPath), '/');
            if ($meta->indexAsPage) {
                $webPath = substr($webPath, 0, -strlen('index'));
            }
            $page->_meta->path = rightTrimPath($webPath);

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

            $path = trimPath($this->outputPathResolver->link($relativePath, $filename, $file->getExtraBladeExtension() ?: 'html'));

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

            $path = resolvePath(urldecode($this->outputPathResolver->path(
                $file->path(),
                $file->name(),
                $file->extension(),
                $file->page(),
                $file->prefix(),
            )));


            $page = $file->data()->page;
            $meta = $page->_meta ?? [];
            if ($meta->indexAsPage) {
                $path = substr($path, 0, -strlen('/index.html')) . '/index/index.html';
            }

            return $path;
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

        /**
         * Inject CSS/JS modules into page HTML.
         */
        private function injectAssets(string $html, array $moduleArray, string $outputPath): string
        {
            $headClosePos = stripos($html, '</head>');
            if ($headClosePos === false) {
                return $html;
            }

            $useModuleCache = filter_var(Container::getInstance()->config->get('moduleCache', true), FILTER_VALIDATE_BOOLEAN);
            if ($useModuleCache && !empty($moduleArray['css'])) {
                $html = $this->stripCoreCssLink($html);
                $headClosePos = stripos($html, '</head>');
            }

            $cssInjection = '';
            $cssPreload = '';
            if (! empty($moduleArray['css'])) {
                $cssUrl = $this->publishAsset($moduleArray['css'], $outputPath, 'css');
                if ($cssUrl) {
                    $cssPreload = "<link rel=\"preload\" as=\"style\" href=\"{$cssUrl}\">";
                    $cssInjection = "<link rel=\"stylesheet\" href=\"{$cssUrl}\">";
                }
            }

            $scriptTag = '';
            if(isset($moduleArray['modules']) && !empty($moduleArray['modules'])) {
                $modules = $moduleArray['modules'];
                $preloaded = [
                    'modules' => array_keys($modules),
                    'loadedPlugins' => $modules ?? [],
                ];
                $json = json_encode($preloaded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $scriptTag = '<script>window.SF_PRELOADED = ' . $json . ';</script>';
            }

            $jsInjection = '';
            if (! empty($moduleArray['js'])) {
                $jsUrl = $this->publishAsset($moduleArray['js'], $outputPath, 'js');
                if ($jsUrl) {
                    $jsInjection .= "<script src=\"{$jsUrl}\"></script>";
                }
            }
            $jsInjection .= $scriptTag;

            if ($cssInjection !== '') {
                $cssPos = $this->findCoreCssPosition($html)
                    ?? $this->findProjectCssPosition($html)
                    ?? $this->findFirstScriptPosition($html)
                    ?? $headClosePos;
                $html = substr($html, 0, $cssPos) . $cssPreload . $cssInjection . substr($html, $cssPos);
                $headClosePos = stripos($html, '</head>');
            }

            if ($jsInjection === '') {
                return $html;
            }

            $cssTail = $this->findLastStylesheetPosition($html);
            $insertPos = $cssTail
                ?? $this->findCoreJsPosition($html)
                ?? ($headClosePos === false ? strlen($html) : $headClosePos);

            return substr($html, 0, $insertPos) . $jsInjection . substr($html, $insertPos);
        }

        private function publishAsset(string $sourcePath, string $outputPath, string $ext): ?string
        {
            if (! is_file($sourcePath)) {
                return null;
            }

            $extDir = $ext === 'js' ? 'js' : 'css';
            $assetsDir = rtrim($outputPath, '/\\') . '/assets/build/' . $extDir;
            if (! is_dir($assetsDir)) {
                @mkdir($assetsDir, 0775, true);
            }

            $fileName = basename($sourcePath);
            $destPath = $assetsDir . '/' . $fileName;
            @copy($sourcePath, $destPath);

            return '/assets/build/' . $extDir . '/' . $fileName;
        }

        private function findCoreJsPosition(string $html): ?int
        {
            if (preg_match('/<script[^>]+src=[\"\\\']?[^\"\\\'>]*core\\/js\\/core\\.js[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
                return $matches[0][1];
            }

            return null;
        }

        private function findCoreCssPosition(string $html): ?int
        {
            if (preg_match('/<link[^>]+href=[\"\\\']?[^\"\\\'>]*core\\/css\\/core\\.css[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
                return $matches[0][1] + strlen($matches[0][0]);
            }

            return null;
        }

        private function stripCoreCssLink(string $html): string
        {
            return preg_replace('/<link[^>]+href=[\"\\\']?[^\"\\\'>]*core\\/css\\/core\\.css[^>]*>\\s*/i', '', $html, 1);
        }

        private function findProjectCssPosition(string $html): ?int
        {
            if (preg_match('/<link[^>]+href=[\"\\\']?[^\"\\\'>]*assets\\/build\\/css\\/main\\.css[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
                return $matches[0][1];
            }

            return null;
        }

        private function findFirstScriptPosition(string $html): ?int
        {
            if (preg_match('/<script\\b[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
                return $matches[0][1];
            }

            return null;
        }

        private function findLastStylesheetPosition(string $html): ?int
        {
            if (preg_match_all('/<link[^>]+rel=[\"\\\']?stylesheet[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
                $last = end($matches[0]);
                if (is_array($last) && isset($last[1])) {
                    return $last[1] + strlen($last[0]);
                }
            }

            return null;
        }
    }
