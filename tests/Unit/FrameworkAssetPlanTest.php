<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlan;

final class FrameworkAssetPlanTest extends TestCase
{
    public function test_generated_asset_content_can_be_released_without_changing_public_metadata(): void
    {
        $plan = new FrameworkAssetPlan(
            'pair',
            [['key' => 'core', 'kind' => 'css', 'url' => '/core.css']],
            [[
                'key' => 'docara.framework.shell.css',
                'kind' => 'shell_css',
                'filename' => 'docara-shell.' . str_repeat('a', 64) . '.css',
                'url' => '/_docara/docara-shell.' . str_repeat('a', 64) . '.css',
                'sha256' => str_repeat('a', 64),
                'content' => '.example{}',
            ]],
            ['mode' => 'test'],
        );

        $released = $plan->withoutGeneratedAssetContent();

        self::assertSame($plan->headHtml(), $released->headHtml());
        self::assertSame($plan->shellCssUrl(), $released->shellCssUrl());
        self::assertSame($plan->receipt(), $released->receipt());
        self::assertArrayNotHasKey('content', $released->generatedAssets[0]);
    }
}
