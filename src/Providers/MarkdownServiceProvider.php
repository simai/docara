<?php

namespace Simai\Docara\Providers;

use Mni\FrontYAML\Bridge\Symfony\SymfonyYAMLParser;
use Mni\FrontYAML\Markdown\MarkdownParser as FrontYAMLMarkdownParser;
use Mni\FrontYAML\Parser;
use Mni\FrontYAML\YAML\YAMLParser;
use Simai\Docara\Container;
use Simai\Docara\Parsers\CommonMarkParser;
use Simai\Docara\Parsers\FrontMatterParser;
use Simai\Docara\Parsers\DocaraMarkdownParser;
use Simai\Docara\Parsers\MarkdownParser;
use Simai\Docara\Parsers\MarkdownParserContract;
use Simai\Docara\Support\ServiceProvider;

class MarkdownServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(YAMLParser::class, SymfonyYAMLParser::class);

        $this->app->bind(MarkdownParserContract::class, function (Container $app) {
            return $app['config']->get('commonmark') ? new CommonMarkParser : new DocaraMarkdownParser;
        });

        $this->app->singleton('markdownParser', fn (Container $app) => new MarkdownParser($app[MarkdownParserContract::class]));

        // Make the FrontYAML package use our own Markdown parser internally
        $this->app->bind(FrontYAMLMarkdownParser::class, fn (Container $app) => $app['markdownParser']);

        $this->app->bind(Parser::class, function (Container $app) {
            return new Parser($app[YAMLParser::class], $app[FrontYAMLMarkdownParser::class]);
        });

        $this->app->bind(FrontMatterParser::class, function (Container $app) {
            return new FrontMatterParser($app[Parser::class]);
        });
    }
}
