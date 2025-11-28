<?php

namespace Simai\Docara\Providers;

use Simai\Docara\Support\ServiceProvider;

class BootstrapFileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (file_exists($bootstrapFile = $this->app->path('bootstrap.php'))) {
            $events = $this->app->events;
            $container = $this->app;
            $cachePath = $this->app->cachePath();
            $envPath = $this->app->path('.env');
            $bladeCompiler = $this->app['blade.compiler'];

            include $bootstrapFile;
        }
    }
}
