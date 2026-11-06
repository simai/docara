<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Declarative\Composition\PageCompositionContext;
use Simai\Docara\Declarative\Composition\RegionCompositionResolver;
use Simai\Docara\Declarative\DeclarativePageCompiler;
use Simai\Docara\Declarative\Document\DocumentParser;
use Simai\Docara\Declarative\Rendering\DeclarativePageRenderer;
use Simai\Docara\I18n\UiCopy;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;
use Simai\Docara\PortableSite\SetPassportCatalogue;

/**
 * shell.set-passport: a component set's footer line, built from the set's
 * passport set.json and its package receipt.
 */
final class SetPassportFooterTest extends TestCase
{
    private const REVISION = '4d7070623fe181ccc5f0b7b9230098c4ea24e8dd';

    #[Test]
    public function the_line_names_the_build_until_the_set_names_a_release(): void
    {
        $lines = $this->catalogue()->localized(UiCopy::buildDefaults('en'));

        self::assertSame([
            'name' => 'SIMAI View, build 4d70706',
            'framework' => 'For SIMAI Framework 1.25.0 and later',
            'licence' => 'Licence: PolyForm-Small-Business-1.0.0',
            'source' => ['href' => 'https://github.com/simai/ui-view', 'text' => 'Source code'],
            'history' => 'Version history',
        ], $lines['view']);

        $versioned = $this->catalogue(['version' => '1.2.0', 'framework' => ['core_runtime' => '^1.25']])
            ->localized(UiCopy::buildDefaults('ru'));
        self::assertSame('SIMAI View 1.2.0', $versioned['view']['name']);
        self::assertSame('Для SIMAI Framework ^1.25', $versioned['view']['framework']);
        self::assertSame('Лицензия: PolyForm-Small-Business-1.0.0', $versioned['view']['licence']);
    }

    #[Test]
    public function a_passport_that_is_not_a_component_set_is_refused(): void
    {
        foreach (['{', '{"schema":"simai.framework.component-set"}', '{"schema":"other","title":"X"}'] as $bytes) {
            try {
                SetPassportCatalogue::fromPassports(['view' => $bytes], ['view' => $this->receipt()]);
                self::fail('An invalid passport was accepted.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('SET_PASSPORT_INVALID', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function the_footer_block_renders_the_line_with_the_source_and_history_links(): void
    {
        $html = $this->render($this->layout(['history' => '/ru/guide/history/']), 'ru');

        self::assertStringContainsString('data-docara-set-passport="view"', $html);
        self::assertStringContainsString('<span class="docara-set-passport-name">SIMAI View, сборка 4d70706</span>', $html);
        self::assertStringContainsString('<a class="docara-set-passport-source" href="https://github.com/simai/ui-view" rel="noopener">Исходный код</a>', $html);
        self::assertStringContainsString('<a class="docara-set-passport-history" href="/ru/guide/history/">История версий</a>', $html);
        self::assertStringNotContainsString('docara-set-passport-history', $this->render($this->layout(), 'ru'));
    }

    #[Test]
    public function the_block_needs_a_known_set_and_belongs_in_the_footer(): void
    {
        $cases = [
            'SET_PASSPORT_PACKAGE_UNKNOWN' => $this->layout(['package' => 'chart']),
            'DECLARATIVE_REGION_SET_PASSPORT_INVALID' => $this->layout(['history' => 'https:evil']),
        ];
        $header = RegionCompositionResolver::defaults();
        $header['regions']['header']['sections'][] = [
            'id' => 'header-set',
            'section' => 'docara.shell',
            'blocks' => [['id' => 'set', 'block' => 'shell.set-passport', 'slot' => 'content', 'package' => 'view']],
        ];
        $forged = $this->layout();
        $forged['regions']['footer']['sections'][0]['blocks'][0]['passport'] = ['name' => 'Forged'];
        foreach ([...$cases, 'header' => $header, 'forged' => $forged] as $code => $layout) {
            try {
                $this->render($layout, 'en');
                self::fail("Layout [$code] was accepted.");
            } catch (PortableConfigurationException $exception) {
                self::assertSame(
                    is_int(strpos((string) $code, '_')) ? $code : 'DECLARATIVE_REGION_SET_PASSPORT_INVALID',
                    $exception->errorCode,
                );
            }
        }
    }

    /** @param array<string, mixed> $block @return array<string, mixed> */
    private function layout(array $block = []): array
    {
        $layout = RegionCompositionResolver::defaults();
        $layout['regions']['footer'] = [
            'enabled' => true,
            'sections' => [[
                'id' => 'site-footer',
                'section' => 'docara.shell',
                'blocks' => [['id' => 'set', 'block' => 'shell.set-passport', 'slot' => 'content', 'package' => 'view', ...$block]],
            ]],
        ];

        return $layout;
    }

    /** @param array<string, mixed> $layout */
    private function render(array $layout, string $locale): string
    {
        $plan = DeclarativePageCompiler::bundled($this->frameworkLock())->compile(
            (new DocumentParser)->parse('# Set', 'content/set.md'),
            'set',
            'Set',
            3,
            PageCompositionContext::fromBuilder(
                ['title' => 'SIMAI View'],
                '/',
                [],
                [],
                setPassports: $this->catalogue()->localized(UiCopy::buildDefaults($locale)),
            ),
            $layout,
        );

        return (new DeclarativePageRenderer(new PortableMarkdownRenderer))->render($plan)->html;
    }

    /** @param array<string, mixed> $overrides */
    private function catalogue(array $overrides = []): SetPassportCatalogue
    {
        $passport = [
            'schema' => 'simai.framework.component-set',
            'schema_version' => 1,
            'id' => 'view',
            'title' => 'SIMAI View',
            'repository' => 'simai/ui-view',
            'licence' => ['class' => 'simai-pro', 'spdx' => 'PolyForm-Small-Business-1.0.0'],
            'framework' => ['core_runtime' => '>=1.25.0'],
            ...$overrides,
        ];

        return SetPassportCatalogue::fromPassports(
            ['view' => json_encode($passport, JSON_THROW_ON_ERROR)],
            ['view' => $this->receipt()],
        );
    }

    /** @return array<string, mixed> */
    private function receipt(): array
    {
        return ['package_source' => ['repository' => 'simai/ui-view', 'revision' => self::REVISION]];
    }

    /** @return array<string, mixed> */
    private function frameworkLock(): array
    {
        return json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
