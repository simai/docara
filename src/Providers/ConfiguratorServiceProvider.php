<?php

namespace Simai\Docara\Providers;

use Simai\Docara\Configurator;
use Simai\Docara\Support\ServiceProvider;

class ConfiguratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Configurator::class, function ($app) {
            return new Configurator($app);
        });

        $this->app->alias(Configurator::class, 'configurator');
    }

    public function boot(): void
    {
        // Если нужно что-то выполнить после регистрации (например, подготовка)
        // $this->app->make(Configurator::class)->prepare(...);
    }
}
