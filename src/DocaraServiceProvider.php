<?php

declare(strict_types=1);

namespace Larena\Docara;

use Illuminate\Support\ServiceProvider;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Persistence\EloquentDocumentationPageRepository;

final class DocaraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DocumentationPageRepository::class,
            EloquentDocumentationPageRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
