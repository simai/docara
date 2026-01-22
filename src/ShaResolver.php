<?php

namespace Simai\Docara;

use Illuminate\Support\Env;
use Simai\Docara\Cache\BuildCache;
use Simai\Docara\Console\ConsoleOutput;

class ShaResolver
{
    private Container $app;

    private ?string $resolvedSha = null;

    private bool $resolved = false;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function resolve(bool $force = false): ?string
    {
        if ($this->resolved && ! $force) {
            $this->syncCache($this->resolvedSha);

            return $this->resolvedSha;
        }

        $config = $this->app['config'];
        $existingSha = $config->get('sha');

        if ($this->shouldSkipFetch()) {
            $this->log('<comment>Skipping SHA fetch (DOCARA_SKIP_SHA_FETCH/skipShaFetch).</comment>');

            return $this->remember($existingSha);
        }

        if ($existingSha) {
            $this->log("<comment>Using existing SHA from config: {$existingSha}</comment>");

            return $this->remember($existingSha);
        }

        try {
            $sha = $this->fetchSha();
            if ($sha) {
                $config->put('sha', $sha);
                $this->log("<comment>Fetched SHA: {$sha}</comment>");
            } else {
                $this->log('<comment>Fetched SHA: null</comment>');
            }

            return $this->remember($sha);
        } catch (\Throwable $e) {
            $this->log("Fetch SHA failed: {$e->getMessage()}", 'warn');

            return $this->remember($existingSha);
        }
    }

    private function shouldSkipFetch(): bool
    {
        $skipShaFetch = $this->app['config']->get('skipShaFetch');
        $skipEnv = Env::get('DOCARA_SKIP_SHA_FETCH');

        return filter_var($skipShaFetch ?? $skipEnv ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function fetchSha(): ?string
    {
        $cacheFile = $this->app->cachePath('docs-cache.json');
        $cacheJson = $this->readCacheJson($cacheFile);
        if (isset($cacheJson['sha']) && is_string($cacheJson['sha'])) {
            return $cacheJson['sha'];
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
            $cacheJson['sha'] = $sha;
            $cacheJson = $this->withCacheDefaults($cacheJson);
            $dir = dirname($cacheFile);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            file_put_contents($cacheFile, json_encode($cacheJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $sha;
    }

    private function readCacheJson(string $cacheFile): array
    {
        if (! is_file($cacheFile)) {
            return [];
        }

        $json = file_get_contents($cacheFile);

        return is_string($json) ? (json_decode($json, true) ?: []) : [];
    }

    private function withCacheDefaults(array $cacheJson): array
    {
        if (! isset($cacheJson['version'])) {
            $cacheJson = array_merge([
                'version' => 1,
                'global' => null,
                'docs' => [],
            ], $cacheJson);
        }

        return $cacheJson;
    }

    private function remember(?string $sha): ?string
    {
        $this->resolvedSha = $sha;
        $this->resolved = true;
        $this->syncCache($sha);

        return $sha;
    }

    private function syncCache(?string $sha): void
    {
        if (! $this->app->bound(BuildCache::class)) {
            return;
        }

        try {
            $this->app->make(BuildCache::class)->setGithubSha($sha);
        } catch (\Throwable $e) {
            $this->log("Cache SHA update failed: {$e->getMessage()}", 'warn');
        }
    }

    private function log(string $message, string $level = 'info'): void
    {
        $console = $this->console();
        if (! $console) {
            return;
        }

        if ($level === 'warn') {
            $console->warn($message);

            return;
        }

        $console->writeln($message);
    }

    private function console(): ?ConsoleOutput
    {
        return $this->app->bound('consoleOutput') ? $this->app['consoleOutput'] : null;
    }
}
