<?php

namespace Simai\Docara\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\View\ViewException;
use Spatie\LaravelIgnition\Views\ViewExceptionMapper;
use Simai\Docara\Support\ServiceProvider;

class ExceptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->make(ExceptionHandler::class)->map(
            fn (ViewException $e) => $this->app->make(ViewExceptionMapper::class)->map($e),
        );
    }
}
