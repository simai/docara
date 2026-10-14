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
            '/.git',
            '/.gitattributes',
            '/.github',
            '/.gitignore',
            '/.markdownlint.json',
            '/composer.lock',
            '/CONTRIBUTING.md',
            '/output',
            '/phpunit.xml',
            '/pint.json',
            '/resources/framework/runtime-packages',
            '/tests',
            '/vendor',
            '/.phpunit.cache',
            'build_*',
        ] as $path) {
            self::assertContains($path, $excluded, "Composer archive must exclude [$path].");
        }
    }

    #[Test]
    public function git_exports_exclude_development_surfaces(): void
    {
        $attributes = (string) file_get_contents(dirname(__DIR__, 2) . '/.gitattributes');

        foreach ([
            '/.editorconfig export-ignore',
            '/.gitattributes export-ignore',
            '/.github export-ignore',
            '/.gitignore export-ignore',
            '/.markdownlint.json export-ignore',
            '/CONTRIBUTING.md export-ignore',
            '/phpunit.xml export-ignore',
            '/pint.json export-ignore',
            '/tests export-ignore',
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

    #[Test]
    public function the_ignore_rules_do_not_ignore_tracked_files(): void
    {
        $root = dirname(__DIR__, 2);
        if (! is_dir($root . '/.git') && ! is_file($root . '/.git')) {
            self::markTestSkipped('Not a Git checkout.');
        }
        $output = [];
        exec('git -C ' . escapeshellarg($root) . ' ls-files -ci --exclude-standard 2>&1', $output, $status);

        self::assertSame(0, $status, implode("\n", $output));
        self::assertSame([], $output, 'Tracked files matched by an ignore rule.');
    }
}
