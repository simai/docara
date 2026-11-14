<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;

final class FrameworkAliasTagStylesheetTest extends TestCase
{
    #[Test]
    public function an_alias_tag_of_a_preloaded_rule_does_not_link_the_shell_stylesheet_again(): void
    {
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock));
        // sf-button-icon requires sf-close and sf-icon-button: the same
        // elements under the other tag of their rules.
        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->planForHtml(
            '<!doctype html><html lang="en"><head></head><body><main>'
            . '<sf-button-icon icon="close"></sf-button-icon>'
            . '<sf-button-close></sf-button-close>'
            . '</main></body></html>',
            [],
        );

        $shellSources = [];
        foreach ($plan->generatedAssets as $asset) {
            $shellSources[] = $asset['inputs'][0]['source'];
        }
        $components = $repository->runtime()['components'];
        foreach (['sf-close' => 'sf-button-close', 'sf-icon-button' => 'sf-button-icon'] as $alias => $tag) {
            self::assertSame($components[$tag]['css'], $components[$alias]['css']);
            $stylesheet = substr((string) $components[$tag]['css'], strlen('smart/'));
            $minified = preg_replace('/\.css$/', '.min.css', $stylesheet);
            self::assertContains('smart/' . $minified, $shellSources, $tag . ' is not in the shell');
            foreach ($plan->assets as $asset) {
                if (($asset['kind'] ?? null) === 'css') {
                    self::assertStringNotContainsString(
                        $minified,
                        (string) $asset['url'],
                        $alias . ' links the stylesheet the shell already holds',
                    );
                }
            }
            self::assertStringNotContainsString(
                'simai.framework.' . str_replace('-', '_', $alias) . '.css',
                $plan->headHtml(),
            );
        }
    }
}
