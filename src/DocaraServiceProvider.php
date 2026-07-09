<?php

declare(strict_types=1);

namespace Larena\Docara;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Docara\Authoring\DocumentationPageAuthoringService;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Persistence\EloquentDocumentationPageRepository;

final class DocaraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/larena-docara.php', 'larena-docara');
        $this->app->bind(
            DocumentationPageRepository::class,
            EloquentDocumentationPageRepository::class,
        );
        $this->app->bind(DocumentationPageAuthoringService::class, static function (Application $app): DocumentationPageAuthoringService {
            /** @var DatabaseManager $database */
            $database = $app->make(DatabaseManager::class);

            return new DocumentationPageAuthoringService(
                $app->make(DocumentationPageRepository::class),
                $app->make(AuditEventPipeline::class),
                $database->connection(),
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larena-docara');

        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        if ($this->app->environment((array) $config->get('larena-docara.admin.allowed_environments', ['local', 'testing']))
            && (bool) $config->get('larena-docara.admin.enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        }
    }
}
