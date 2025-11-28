<?php

namespace Simai\Docara\Providers;

use Simai\Docara\Cache\BuildCache;
use Simai\Docara\Collection\CollectionPaginator;
use Simai\Docara\CollectionItemHandlers\BladeCollectionItemHandler;
use Simai\Docara\CollectionItemHandlers\MarkdownCollectionItemHandler;
use Simai\Docara\Container;
use Simai\Docara\Configurator;
use Simai\Docara\File\TemporaryFilesystem;
use Simai\Docara\Handlers\BladeHandler;
use Simai\Docara\Handlers\CollectionItemHandler;
use Simai\Docara\Handlers\DefaultHandler;
use Simai\Docara\Handlers\IgnoredHandler;
use Simai\Docara\Handlers\MarkdownHandler;
use Simai\Docara\Handlers\PaginatedPageHandler;
use Simai\Docara\Docara;
use Simai\Docara\Loaders\CollectionDataLoader;
use Simai\Docara\Loaders\CollectionRemoteItemLoader;
use Simai\Docara\Loaders\DataLoader;
use Simai\Docara\Parsers\FrontMatterParser;
use Simai\Docara\PathResolvers\BasicOutputPathResolver;
use Simai\Docara\PathResolvers\CollectionPathResolver;
use Simai\Docara\SiteBuilder;
use Simai\Docara\Support\ServiceProvider;
use Simai\Docara\View\ViewRenderer;

class CollectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('outputPathResolver', fn () => new BasicOutputPathResolver);

        $this->registerHandlers();
        $this->registerPathResolver();
        $this->registerLoaders();
        $this->registerPaginator();
        $this->registerSiteBuilder();

        $this->app->bind(Docara::class, function (Container $app) {
            return new Docara($app, $app[DataLoader::class], $app[CollectionRemoteItemLoader::class], $app[SiteBuilder::class]);
        });
    }

    private function registerHandlers(): void
    {
        $this->app->bind(BladeHandler::class, function (Container $app) {
            return new BladeHandler($app[TemporaryFilesystem::class], $app[FrontMatterParser::class], $app[ViewRenderer::class]);
        });

        $this->app->bind(MarkdownHandler::class, function (Container $app) {
            return new MarkdownHandler($app[TemporaryFilesystem::class], $app[FrontMatterParser::class], $app[ViewRenderer::class]);
        });

        $this->app->bind(CollectionItemHandler::class, function (Container $app) {
            return new CollectionItemHandler($app['config'], [
                $app[MarkdownHandler::class],
                $app[BladeHandler::class],
            ], $app->make(Configurator::class), $app->make(BuildCache::class));
        });
    }

    private function registerPathResolver(): void
    {
        $this->app->bind(CollectionPathResolver::class, function (Container $app) {
            return new CollectionPathResolver($app['outputPathResolver'], $app[ViewRenderer::class]);
        });
    }

    private function registerLoaders(): void
    {
        $this->app->bind(CollectionDataLoader::class, function (Container $app) {
            return new CollectionDataLoader($app['files'], $app['consoleOutput'], $app[CollectionPathResolver::class], [
                $app[MarkdownCollectionItemHandler::class],
                $app[BladeCollectionItemHandler::class],
            ]);
        });

        $this->app->bind(DataLoader::class, function (Container $app) {
            return new DataLoader($app[CollectionDataLoader::class]);
        });

        $this->app->bind(CollectionRemoteItemLoader::class, function (Container $app) {
            return new CollectionRemoteItemLoader($app['config'], $app['files']);
        });
    }

    private function registerPaginator(): void
    {
        $this->app->bind(CollectionPaginator::class, function (Container $app) {
            return new CollectionPaginator($app['outputPathResolver']);
        });

        $this->app->bind(PaginatedPageHandler::class, function (Container $app) {
            return new PaginatedPageHandler($app[CollectionPaginator::class], $app[FrontMatterParser::class], $app[TemporaryFilesystem::class], $app[ViewRenderer::class]);
        });
    }

    private function registerSiteBuilder(): void
    {
        $this->app->bind(SiteBuilder::class, function (Container $app) {
            return new SiteBuilder(
                $app['files'],
                $app->cachePath(),
                $app['outputPathResolver'],
                $app['consoleOutput'],
                [
                    $app[CollectionItemHandler::class],
                    new IgnoredHandler,
                    $app[PaginatedPageHandler::class],
                    $app[MarkdownHandler::class],
                    $app[BladeHandler::class],
                    $app[DefaultHandler::class],
                ],
                $app->make(BuildCache::class)
            );
        });
    }
}
