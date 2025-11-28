<?php

namespace Simai\Docara\Support;

use Simai\Docara\Container;

abstract class ServiceProvider
{
    public function __construct(
        protected Container $app,
    ) {}

    public function register(): void
    {
        //
    }
}
