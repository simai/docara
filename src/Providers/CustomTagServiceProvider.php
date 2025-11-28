<?php

namespace Simai\Docara\Providers;

use Simai\Docara\CustomTags\CustomTagRegistry;
use Simai\Docara\CustomTags\TagRegistry;
use Simai\Docara\Interface\CustomTagInterface;
use Simai\Docara\Parsers\FrontMatterParser;
use Simai\Docara\Parser;
use Simai\Docara\Support\ServiceProvider;

class CustomTagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FrontMatterParser::class, Parser::class);

        $this->app->bind(CustomTagRegistry::class, function ($app) {
            $namespace = 'App\\Helpers\\CustomTags\\';
            $shorts = (array) $app['config']->get('tags', []);
            $instances = [];

            foreach ($shorts as $short) {
                $class = $namespace . $short;
                if (class_exists($class)) {
                    $obj = new $class;
                    if ($obj instanceof CustomTagInterface) {
                        $instances[] = $obj;
                    }
                }
            }

            return TagRegistry::register($instances);
        });
    }
}
