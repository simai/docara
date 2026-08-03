<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ComposerPackageSurfaceTest extends TestCase
{
    #[Test]
    public function composer_binary_uses_the_proxy_autoload_outside_the_consumer_root(): void
    {
        $binary = (string) file_get_contents(dirname(__DIR__, 2) . '/docara');

        self::assertStringContainsString('$GLOBALS[\'_composer_autoload_path\']', $binary);
        self::assertStringContainsString('require_once $composerAutoload;', $binary);
    }

    #[Test]
    public function composer_archive_excludes_local_state_and_development_surfaces(): void
    {
        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $excluded = $composer['archive']['exclude'] ?? [];

        self::assertIsArray($excluded);
        foreach ([
            '/.editorconfig',
            '/.env',
            '/.env.example',
            '/.git',
            '/.git-blame-ignore-revs',
            '/.gitattributes',
            '/.github',
            '/.markdownlint.json',
            '/composer.lock',
            '/CONTRIBUTING.md',
            '.hcs-audit',
            '.phpunit.cache',
            '.playwright-cli',
            'build_*',
            '/graph',
            '/output',
            '/phpunit.xml',
            '/pint.json',
            '/source',
            '/tests',
            '/vendor',
        ] as $path) {
            self::assertContains($path, $excluded, "Composer archive must exclude [$path].");
        }
    }

    #[Test]
    public function git_exports_exclude_internal_and_generated_surfaces(): void
    {
        $attributes = (string) file_get_contents(dirname(__DIR__, 2) . '/.gitattributes');

        foreach ([
            '/.editorconfig export-ignore',
            '/.env.example export-ignore',
            '/.git-blame-ignore-revs export-ignore',
            '/.gitattributes export-ignore',
            '/.github export-ignore',
            '/.markdownlint.json export-ignore',
            '/composer.lock export-ignore',
            '/CONTRIBUTING.md export-ignore',
            'build_* export-ignore',
            '/graph export-ignore',
            '/output export-ignore',
            '/phpunit.xml export-ignore',
            '/pint.json export-ignore',
            '/source/workflow export-ignore',
            '/tests export-ignore',
            '/vendor export-ignore',
        ] as $rule) {
            self::assertStringContainsString($rule, $attributes);
        }
    }

    #[Test]
    public function optional_mcp_is_packaged_but_not_registered_as_a_normal_runtime_binary_or_dependency(): void
    {
        $root = dirname(__DIR__, 2);
        $surface = json_decode((string) file_get_contents($root . '/resources/release/package-surface.json'), true, 512, JSON_THROW_ON_ERROR);
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertContains('tools/mcp-docara/', $surface['include']);
        self::assertContains('tools/mcp-docara/server.php', $surface['executable']);
        self::assertNotContains('tools/mcp-docara/server.php', $composer['bin']);
        self::assertArrayNotHasKey('node', $composer['require']);
        self::assertArrayNotHasKey('playwright', $composer['require']);
    }
}
