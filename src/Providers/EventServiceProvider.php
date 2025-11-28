<?php

namespace Simai\Docara\Providers;

use Illuminate\Events\Dispatcher;
use Simai\Docara\Container;
use Simai\Docara\Events\EventBus;
use Simai\Docara\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('dispatcher', fn (Container $app) => new Dispatcher($app));

        $this->app->singleton('events', fn (Container $app) => new EventBus);
    }
}
