<?php

declare(strict_types=1);

namespace Larena\Docara;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Access\Runtime\AccessOperationRegistry;
use Larena\Access\ValueObjects\AccessOperationDescriptor;
use Larena\Docara\Authoring\DocumentationPageAuthoringService;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Persistence\EloquentDocumentationPageRepository;
use Larena\Admin\Navigation\AdminNavigationRegistry;
use Larena\Docara\Navigation\DocaraAdminNavigationContributor;
use Larena\Docara\Navigation\DocumentationNavigationService;

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
        $this->app->bind(DocumentationNavigationService::class, static fn (Application $app): DocumentationNavigationService => new DocumentationNavigationService(
            $app->make(DatabaseManager::class), $app->make(AuditEventPipeline::class),
        ));
    }

    public function boot(): void
    {
        $operations = $this->app->make(AccessOperationRegistry::class);
        $operations->register(new AccessOperationDescriptor('docara.page.read', 'larena/docara', 'larena-docara::operations.page_read', 'docara.page:all', 'read', 'normal'));
        $operations->register(new AccessOperationDescriptor('docara.page.write', 'larena/docara', 'larena-docara::operations.page_write', 'docara.page:all', 'write', 'high'));
        $operations->register(new AccessOperationDescriptor('docara.page.publish', 'larena/docara', 'larena-docara::operations.page_publish', 'docara.page:all', 'publish', 'critical'));
        $operations->register(new AccessOperationDescriptor('docara.navigation.read', 'larena/docara', 'larena-docara::operations.navigation_read', 'docara.navigation:all', 'read', 'normal'));
        $operations->register(new AccessOperationDescriptor('docara.navigation.write', 'larena/docara', 'larena-docara::operations.navigation_write', 'docara.navigation:all', 'write', 'high'));
        $operations->register(new AccessOperationDescriptor('docara.navigation.delete', 'larena/docara', 'larena-docara::operations.navigation_delete', 'docara.navigation:all', 'delete', 'critical'));

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larena-docara');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'larena-docara');

        if ($this->app->bound(AdminNavigationRegistry::class)) {
            $this->app->make(AdminNavigationRegistry::class)
                ->registerContributor(new DocaraAdminNavigationContributor());
        }

        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);
        if ($this->app->environment((array) $config->get('larena-docara.admin.allowed_environments', ['local', 'testing']))
            && (bool) $config->get('larena-docara.admin.enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        }
        if ($this->app->environment((array) $config->get('larena-docara.public.allowed_environments', ['local', 'testing']))
            && (bool) $config->get('larena-docara.public.enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/public.php');
        }
    }
}
