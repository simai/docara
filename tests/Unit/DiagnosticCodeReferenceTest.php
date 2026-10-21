<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\DiagnosticCodeInventory;

final class DiagnosticCodeReferenceTest extends TestCase
{
    /** UPPER_SNAKE names the docs use that are not diagnostic codes. */
    private const DOCS_NOT_CODES = [
        'BASE_PATH' => 'GitHub Pages step output in a shell example',
        'SITE_URL' => 'GitHub Pages step output in a shell example',
        'DOCARA_NODE_BINARY' => 'environment variable',
        'DOCARA_SIMAI_UI_ROOT' => 'environment variable',
        'SIMAI_UI_ROOT' => 'environment variable',
    ];

    /**
     * Families an author or site owner meets first. Every code of these
     * families has a cause and a fix; a new one must be documented too.
     */
    private const COMPLETE_FAMILIES = [
        'FRONT_MATTER_', 'MARKDOWN_', 'FRAMEWORK_PROP_', 'FRAMEWORK_DIRECTIVE_', 'PORTABLE_REDIRECT_',
        'LOCALE_', 'DEFAULT_LOCALE_', 'TRANSLATION_', 'MESSAGE_', 'UPGRADE_', 'SEARCH_', 'EFFECTS_',
        'SITE_URL_', 'DISCOVERY_', 'THEME_', 'BRAND_', 'PORTAL_', 'READER_PREFERENCES_',
    ];

    /** Internal invariants inside those families that a user cannot cause. */
    private const INTERNAL = [
        'MARKDOWN_HERO_EFFECT_ANCHOR_MISSING' => 'LogicException: a renderer invariant',
    ];

    private DiagnosticCodeInventory $inventory;

    protected function setUp(): void
    {
        $this->inventory = new DiagnosticCodeInventory(dirname(__DIR__, 2));
    }

    #[Test]
    public function every_code_the_docs_name_exists_in_the_source(): void
    {
        $unknown = [];
        foreach ($this->inventory->mentionedIn('docs/site/content') as $code => $files) {
            if (str_starts_with($code, '@') || isset(self::DOCS_NOT_CODES[$code])) {
                continue;
            }
            if (! $this->inventory->knows($code)) {
                $unknown[] = $code . ' in ' . implode(', ', $files);
            }
        }

        self::assertSame([], $unknown, 'The docs name codes that Docara no longer raises.');
    }

    #[Test]
    public function every_marker_the_error_reference_names_exists_in_verify_static(): void
    {
        $markers = array_values(array_filter(
            $this->inventory->documented(),
            static fn (string $code): bool => str_starts_with($code, '@'),
        ));

        self::assertNotEmpty($markers);
        foreach ($markers as $marker) {
            self::assertTrue($this->inventory->knows($marker) || $marker === '@build', $marker);
        }
    }

    #[Test]
    public function the_most_common_families_are_documented_in_full(): void
    {
        $documented = array_fill_keys($this->inventory->documented(), true);
        $missing = [];
        foreach (array_keys($this->inventory->codes()) as $code) {
            foreach (self::COMPLETE_FAMILIES as $family) {
                if (str_starts_with($code, $family) && ! isset($documented[$code]) && ! isset(self::INTERNAL[$code])) {
                    $missing[] = $code;
                }
            }
        }
        foreach ($this->inventory->verifierMarkers() as $marker) {
            if (! isset($documented[$marker])) {
                $missing[] = $marker;
            }
        }

        self::assertSame([], $missing, 'Add a cause and a fix to docs/site/content/ru/reference/errors/.');
    }

    #[Test]
    public function the_coverage_list_is_current(): void
    {
        $path = dirname(__DIR__, 2) . '/' . DiagnosticCodeInventory::COVERAGE_FILE;
        $expected = json_encode($this->inventory->coverage(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";

        self::assertFileExists($path);
        self::assertSame(
            $expected,
            (string) file_get_contents($path),
            'Run php scripts/capture-diagnostic-code-coverage.php after adding or documenting a code.',
        );
    }

    #[Test]
    public function inventory_reads_codes_raised_through_messages_variables_and_prefixes(): void
    {
        $codes = $this->inventory->codes();
        foreach ([
            'FRONT_MATTER_KEY_UNKNOWN',              // thrown directly
            'UPGRADE_PLAN_STALE',                    // "CODE:message" in a RuntimeException
            'PREVIEW_BUILD_PURPOSE_FORBIDDEN',       // "[CODE] message" in verify-static
            'UPGRADE_COMPOSER_FAILED',               // passed through a variable
            'MARKDOWN_LOCAL_IMAGE_CASE_MISMATCH',    // literal prefix + suffix
        ] as $code) {
            self::assertArrayHasKey($code, $codes, $code);
        }
        self::assertArrayNotHasKey('DOCARA_NODE_BINARY', $codes);
        self::assertArrayNotHasKey('MARKDOWN_LOCAL_IMAGE', $codes);
        self::assertTrue($this->inventory->knows('MARKDOWN_SHOWCASE_IMAGE_ALT_REQUIRED'));
        self::assertTrue($this->inventory->knows('MARKDOWN_PROMO_LINK_UNSAFE'));
        self::assertFalse($this->inventory->knows('MARKDOWN_PROMO_'));
        self::assertContains('@missing-fragment', $this->inventory->verifierMarkers());
    }

    #[Test]
    public function troubleshooting_and_security_pages_point_to_the_reference(): void
    {
        $root = dirname(__DIR__, 2) . '/docs/site/content/ru';
        foreach (['troubleshooting.md', 'reference/security-and-errors.md', 'build/verify.md', 'reference.md'] as $page) {
            self::assertStringContainsString('(/reference/errors/', (string) file_get_contents($root . '/' . $page), $page);
        }
        // The old partial code table moved to the reference.
        self::assertStringNotContainsString('| Код | Причина |', (string) file_get_contents($root . '/reference/security-and-errors.md'));
    }
}
