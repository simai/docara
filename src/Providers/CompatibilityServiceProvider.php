<?php

namespace Simai\Docara\Providers;

use Simai\Docara\Console\ConsoleOutput;
use Simai\Docara\Support\ServiceProvider;

class CompatibilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('cwd', $this->app->path());

        $this->app->singleton('consoleOutput', fn () => new ConsoleOutput);
    }
}
