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
                try {
                    [$headings, $rightMenuHeadings, $plain] = $this->extractHeadings($configurator, $page->getPath(), $page->getContent());
                } catch (\Throwable $e) {
                    $this->console()->warn("Headings parse failed for {$page->getPath()}: {$e->getMessage()}");

                    continue;
                }

                $configurator->setHeading($page->getPath(), $rightMenuHeadings);
                $page->set('headings', $rightMenuHeadings);

                $title = $page->title ?? '';
                $contentLines = preg_split('/\r\n|\r|\n/', strip_tags($plain));
                if ($title !== '' && isset($contentLines[0]) && trim($contentLines[0]) === $title) {
                    array_shift($contentLines);
                }

                $cleanedContent = implode("\n", $contentLines);
                $index[$page->language][] = [
                    'title' => $title,
                    'url' => $page->getUrl(),
                    'lang' => $page->language ?? '',
                    'content' => trim($cleanedContent),
                    'headings' => $headings,
                ];
            }
        }

        $configurator->setPaths($paths);
        $docara->setConfig('INDEXES', $index);
    }

    private function extractHeadings(Configurator $configurator, string $path, string $html): array
    {
        $plain = strip_tags($html);
        $headings = [];
        $rightMenuHeadings = [];

        if (preg_match_all('/<h2.*?id="(.*?)".*?>(.*?)<\/h2>/si', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $headings[] = [
                    'anchor' => $match[1],
                    'text' => trim(html_entity_decode(strip_tags($match[2]))),
                ];
            }
        }

        if (preg_match_all('/<(h[1-4])(?: [^>]*id="([^"]*)")?[^>]*>(.*?)<\/\1>/si', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $key => $match) {
                [$inlineAnchorId, $cleanHeadingHtml] = $this->extractInlineHeadingAnchor($match[3]);
                $text = trim(html_entity_decode(strip_tags($cleanHeadingHtml)));
                $id = $configurator->makeUniqueHeadingId($path, $match[1], $key);
                $issetId = strlen(trim($match[2])) > 0;
                if ($issetId) {
                    $id = trim($match[2]);
                } elseif ($inlineAnchorId !== null && $inlineAnchorId !== '') {
                    $id = $inlineAnchorId;
                }
                $fingerPrint = $configurator->mkFingerprint($cleanHeadingHtml);
                $configurator->setFingerprint($id, $fingerPrint);
                $rightMenuHeadings[$id] = [
                    'level' => $match[1],
                    'id' => $id,
                    'type' => preg_replace('/h/', '', $match[1]),
                    'anchor' => $match[2] ?: $inlineAnchorId,
                    'text' => $text,
                ];
            }
        }

        return [$headings, $rightMenuHeadings, $plain];
    }


    /**
     * @throws BindingResolutionException
     */
    private function onAfterBuild(Docara $docara): void
    {
        $configurator = $docara->configurator();
        $outputPath = $docara->getDestinationPath();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($outputPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.html')) {
                $relativePath = str_replace($outputPath, '', preg_replace('#[\\/\\\\]index\.html$#i', '', $file->getPathname()));
                $relativePath = str_replace('\\', '/', $relativePath);
                $html = file_get_contents($file->getPathname());
                $moduleArr = $this->app->ruleLoader->findModules($html);
                if (! empty($moduleArr)) {
                    $html = $this->injectAssets($html, $moduleArr, $outputPath);
                }
                try {
                    $html = $this->injectAnchors($configurator, $relativePath, $html);
                    file_put_contents($file->getPathname(), $html);
                } catch (\Throwable $e) {
                    $this->console()->warn("Anchor inject failed for {$relativePath}: {$e->getMessage()}");
                }
            }
        }

        try {
            $this->writeSearchIndexes($docara);
        } catch (\Throwable $e) {
            $this->console()->warn("Write search indexes failed: {$e->getMessage()}");
        }
    }

    private function injectAssets(string $html, array $moduleArray, string $outputPath): string
    {
        $headClosePos = stripos($html, '</head>');
        if ($headClosePos === false) {
            return $html;
        }

        $useModuleCache = filter_var($this->app['config']->get('moduleCache', true), FILTER_VALIDATE_BOOLEAN);
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

    private function injectAnchors(Configurator $configurator, string $relativePath, string $html): string
    {
        $count = 0;
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        return preg_replace_callback(
            '/<(h[1-6])( [^>]*)?>(.*?)<\/\1>/si',
            function ($match) use (&$count, $relativePath, $configurator) {
                [$inlineAnchorId, $cleanHeadingHtml] = $this->extractInlineHeadingAnchor($match[3]);
                $fingerPrint = $configurator->mkFingerprint($cleanHeadingHtml);
                if (! isset($configurator->fingerPrint[$fingerPrint])) {
                    return $match[0];
                }
                $tag = $match[1];
                $attrs = $match[2] ?? '';
                if (str_contains($attrs, 'id=')) {
                    return $match[0];
                }
                $id = $configurator->makeUniqueHeadingId($relativePath, $tag, $count);
                if ($inlineAnchorId !== null && $inlineAnchorId !== '') {
                    $id = $inlineAnchorId;
                }
                $count++;
                $cleanHeadingHtml = preg_replace(
                    '/(\S+)$/u',
                    '<span class="nowrap">$1<span class="sf-icon sf-icon--rotate-135">link</span></span>',
                    $cleanHeadingHtml
                );

                return "<$tag$attrs id=\"$id\"><a href='#{$id}' onclick='copyAnchor(this)' aria-disabled='false' class='header-anchor'>{$cleanHeadingHtml}</a></$tag>";
            },
            $html
        );
    }

    private function extractInlineHeadingAnchor(string $html): array
    {
        $anchorId = null;
        $cleanHtml = $html;

        if (preg_match('/<a([^>]*)\\bname\\s*=\\s*[\"\\\']([^\"\\\']+)[\"\\\']([^>]*)>(.*?)<\\/a>/si', $html, $match)) {
            $anchorId = trim($match[2]);
            $inner = $match[4] ?? '';
            $cleanHtml = trim(str_replace($match[0], $inner, $html));
        } elseif (preg_match('/<a((?!href)[^>]*)\\bid\\s*=\\s*[\"\\\']([^\"\\\']+)[\"\\\']((?!href)[^>]*)>(.*?)<\\/a>/si', $html, $match)) {
            $anchorId = trim($match[2]);
            $inner = $match[5] ?? '';
            $cleanHtml = trim(str_replace($match[0], $inner, $html));
        }

        return [$anchorId, $cleanHtml];
    }

    private function writeSearchIndexes(Docara $docara): void
    {
        $index = $docara->getConfig('INDEXES');
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
