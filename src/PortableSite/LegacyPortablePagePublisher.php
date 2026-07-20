<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Declarative\DeclarativePageResult;
use Simai\Docara\Framework\FrameworkAssetPlan;

final readonly class LegacyPortablePagePublisher implements PortablePagePublisher
{
    public function __construct(private PortableHtmlRenderer $renderer) {}

    public function id(): string
    {
        return 'docara.legacy_html_renderer.v1';
    }

    public function render(
        array $page,
        array $navigation,
        string $siteTitle,
        FrameworkAssetPlan $assets,
        ?DeclarativePageResult $declarative,
    ): string {
        return $this->renderer->render($page, $navigation, $siteTitle, $assets);
    }
}
