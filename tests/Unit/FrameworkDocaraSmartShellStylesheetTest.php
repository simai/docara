<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;

final class FrameworkDocaraSmartShellStylesheetTest extends TestCase
{
    #[Test]
    public function the_shell_carries_the_stylesheets_of_the_docara_smart_elements_the_page_draws(): void
    {
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $planner = new FrameworkAssetPlanner(
            FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock)),
            '/_docara/framework-runtime',
        );
        // The navigation is drawn by the page; the brand only inside an
        // example frame and a code sample, which are not the page's own.
        $plan = $planner->planForHtml(
            '<!doctype html><html lang="en"><head></head><body>'
            . '<nav class="docara-navigation" data-docara-smart="docara.navigation"></nav>'
            . '<pre><code>&lt;a data-docara-smart="docara.brand"&gt;</code></pre>'
            . '<iframe data-docara-example-frame srcdoc="&lt;a data-docara-smart=&quot;docara.brand&quot;&gt;&lt;/a&gt;"></iframe>'
            . '</body></html>',
            [],
        );

        self::assertSame(['docara.smart.navigation.css'], $plan->shellSmartAssetKeys());
        $first = $plan->generatedAssets[0];
        self::assertSame(
            FrameworkAssetPlanner::DOCARA_SMART_CSS_SOURCE . 'docara.smart.navigation.css',
            $first['inputs'][0]['source'],
        );
        $bytes = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/smart/assets/navigation.css');
        self::assertSame(hash('sha256', $bytes), $first['inputs'][0]['sha256']);
        self::assertStringContainsString($bytes, (string) $first['content']);

        $plain = $planner->planForHtml('<!doctype html><html><head></head><body><p>Text</p></body></html>', []);
        self::assertSame([], $plain->shellSmartAssetKeys());
    }
}
