<?php

namespace Simai\Docara\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Simai\Docara\Cache\BuildCache;
use Simai\Docara\Configurator;
use Simai\Docara\Console\ConsoleOutput;
use Simai\Docara\Docara;
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
            $sha = $this->fetchSha();
            if ($sha) {
                $docara->setConfig('sha', $sha);
            }
            $this->setCacheSha($sha);
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

    private function fetchSha(): ?string
    {
        $tempConfigPath = $this->tempConfigPath();
        $tempConfig = $this->readTempConfig($tempConfigPath);
        if (isset($tempConfig['sha']) && is_string($tempConfig['sha'])) {
            return $tempConfig['sha'];
        }

        $url = 'https://api.github.com/repos/simai/ui/commits/main';
        $context = stream_context_create([
            'http' => [
                'header' => [
                    'User-Agent: Docara',
                    'Accept: application/vnd.github.v3+json',
                ],
                'timeout' => 3,
            ],
        ]);

        $json = @file_get_contents($url, false, $context);
        if (! $json) {
            return null;
        }

        $data = json_decode($json, true);
        $sha = $data['sha'] ?? null;
        if ($sha) {
            $tempConfig['sha'] = $sha;
            $this->writeTempConfig($tempConfigPath, $tempConfig);
        }

        return $sha;
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
                $text = trim(html_entity_decode(strip_tags($match[3])));
                $id = $configurator->makeUniqueHeadingId($path, $match[1], $key);
                $issetId = strlen(trim($match[2])) > 0;
                if ($issetId) {
                    $id = trim($match[2]);
                }
                $fingerPrint = $configurator->mkFingerprint($match[3]);
                $configurator->setFingerprint($id, $fingerPrint);
                $rightMenuHeadings[$id] = [
                    'level' => $match[1],
                    'id' => $id,
                    'type' => preg_replace('/h/', '', $match[1]),
                    'anchor' => $match[2],
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

    private function injectAnchors(Configurator $configurator, string $relativePath, string $html): string
    {
        $count = 0;
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        return preg_replace_callback(
            '/<(h[1-6])( [^>]*)?>(.*?)<\/\1>/si',
            function ($match) use (&$count, $relativePath, $configurator) {
                $fingerPrint = $configurator->mkFingerprint($match[3]);
                if (! isset($configurator->fingerPrint[$fingerPrint])) {
                    return $match[0];
                }
                $tag = $match[1];
                $attrs = $match[2] ?? '';
                if (str_contains($attrs, 'id=')) {
                    return $match[0];
                }
                $id = $configurator->makeUniqueHeadingId($relativePath, $tag, $count);
                $count++;
                $match[3] = preg_replace(
                    '/(\S+)$/u',
                    '<span class="nowrap">$1<span class="sf-icon">link</span></span>',
                    $match[3]
                );

                return "<$tag$attrs id=\"$id\"><a href='#{$id}' onclick='copyAnchor(this)' aria-disabled='false' class='header-anchor'>{$match[3]}</a></$tag>";
            },
            $html
        );
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

    private function setCacheSha(?string $sha): void
    {
        if (! $this->app->bound(BuildCache::class)) {
            return;
        }

        try {
            $this->app->make(BuildCache::class)->setGithubSha($sha);
        } catch (\Throwable $e) {
            $this->console()->warn("Cache SHA update failed: {$e->getMessage()}");
        }
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
