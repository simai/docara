<?php

namespace Simai\Docara\Providers;

use Simai\Docara\File\Filesystem;
use Simai\Docara\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('files', fn () => new Filesystem);
    }
}
