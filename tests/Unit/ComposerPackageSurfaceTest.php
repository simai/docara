<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ComposerPackageSurfaceTest extends TestCase
{
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
            '/.env',
            '/.git',
            '/.github',
            '.hcs-audit',
            '.phpunit.cache',
            '.playwright-cli',
            'build_*',
            '/output',
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
            '/.github export-ignore',
            'build_* export-ignore',
            '/output export-ignore',
            '/source/workflow export-ignore',
            '/tests export-ignore',
            '/vendor export-ignore',
        ] as $rule) {
            self::assertStringContainsString($rule, $attributes);
        }
    }
}
