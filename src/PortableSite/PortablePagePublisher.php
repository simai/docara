<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Declarative\DeclarativePageResult;
use Simai\Docara\Framework\FrameworkAssetPlan;

interface PortablePagePublisher
{
    public function id(): string;

    /**
     * @param  array<string, mixed>  $page
     * @param  list<array<string, mixed>>  $navigation
     */
    public function render(
        array $page,
        array $navigation,
        string $siteTitle,
        FrameworkAssetPlan $assets,
        ?DeclarativePageResult $declarative,
    ): string;
}
