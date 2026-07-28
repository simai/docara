<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Markdown\CommonMarkInspector;
use Simai\Docara\Markdown\DirectiveBlockStartParser;
use Simai\Docara\Markdown\DirectiveLimitExceeded;

final class CommonMarkInspectorTest extends TestCase
{
    #[Test]
    public function directive_iteration_probe_observes_normal_extraction(): void
    {
        $iterations = 0;
        $inspector = new CommonMarkInspector(
            static function () use (&$iterations): void {
                $iterations++;
            },
        );

        $inspection = $inspector->inspectDirectives(
            ":::card\nBody\n:::\n",
            DirectiveBlockStartParser::PORTABLE,
        );

        self::assertCount(1, $inspection['directives']);
        self::assertSame(1, $iterations);
    }

    #[Test]
    public function combined_source_budget_rejects_before_any_directive_iteration(): void
    {
        $portable = ":::card\nBody\n:::\n";
        $framework = ":::ui.button\n{}\n:::\n";
        $cases = [
            [str_repeat($portable, 32) . str_repeat($framework, 33), DirectiveBlockStartParser::FRAMEWORK],
            [str_repeat($framework, 32) . str_repeat($portable, 33), DirectiveBlockStartParser::PORTABLE],
            [str_repeat($framework, 65), DirectiveBlockStartParser::FRAMEWORK],
            [str_repeat($portable, 65), DirectiveBlockStartParser::PORTABLE],
        ];

        foreach ($cases as [$markdown, $expectedFamily]) {
            $iterations = 0;
            $inspector = new CommonMarkInspector(
                static function () use (&$iterations): void {
                    $iterations++;
                },
            );

            try {
                $inspector->inspectDirectives($markdown, DirectiveBlockStartParser::PORTABLE);
                self::fail("Directive source overflow unexpectedly reached parsing for [$expectedFamily].");
            } catch (DirectiveLimitExceeded $exception) {
                self::assertSame($expectedFamily, $exception->family);
                self::assertSame(0, $iterations);
            }
        }
    }
}
