<?php

namespace Simai\Docara\Providers;

use Simai\Docara\Cache\BuildCache;
use Simai\Docara\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BuildCache::class, function ($app) {
            $useCache = $app['config']->get('cache');
            $pretty = false;
            $cache = new BuildCache($useCache, $pretty);
            $cache->setGithubSha($app['config']->get('sha'));
            $value = filter_var($useCache, FILTER_VALIDATE_BOOLEAN);
            $app['consoleOutput']->writeln(PHP_EOL . sprintf(
                '<comment>=== Use cache: %s ===</comment>',
                $value ? 'true' : 'false'
            ));
            $customCachePath = $app['config']->get('cachePath');
            $paths = $app['buildPath'];
            $destination = $paths['destination'];
            $cache->setCachePath($customCachePath ?: $app->cachePath(), $destination);

            return $cache;
        });
    }
}
