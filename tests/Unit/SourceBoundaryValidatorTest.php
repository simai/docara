<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Content\SourceBoundaryValidator;
use Simai\Docara\Portable\PortableConfigurationException;

final class SourceBoundaryValidatorTest extends TestCase
{
    public function test_target_composition_accepts_structure_and_rejects_page_prose_html_markdown_and_css(): void
    {
        $validator = new SourceBoundaryValidator;
        $validator->assertComposition([
            'schema' => 'docara.page.v2',
            'layout' => ['preset' => 'docs'],
            'search' => ['enabled' => true],
        ], 'page');

        foreach ([
            ['title' => 'Parallel title'],
            ['body' => 'Parallel article'],
            ['region' => '<section>HTML</section>'],
            ['theme' => '```css'],
            ['style' => 'color: red;'],
        ] as $configuration) {
            $this->assertError(
                'TARGET_CONFIG_PROSE_FORBIDDEN',
                fn () => $validator->assertComposition($configuration, 'page'),
            );
        }
    }

    public function test_lang_contract_accepts_shared_interface_copy_and_rejects_page_or_component_namespaces(): void
    {
        $validator = new SourceBoundaryValidator;
        $validator->assertLanguage([
            'schema' => 'docara.lang.v1',
            'version' => 1,
            'search' => ['placeholder' => 'Поиск'],
            'accessibility' => ['copy_code' => 'Копировать код'],
        ]);

        $this->assertError('SCHEMA_VALIDATION_FAILED', fn () => $validator->assertLanguage([
            'schema' => 'docara.lang.v1',
            'version' => 1,
            'pages' => ['badge' => 'Документация компонента'],
        ]));
        $this->assertError('TARGET_LANG_VALUE_INVALID', fn () => $validator->assertLanguage([
            'schema' => 'docara.lang.v1',
            'version' => 1,
            'common' => ['rich' => '<strong>Copy</strong>'],
        ]));
    }

    public function test_repository_lang_record_contains_shared_ui_only(): void
    {
        $language = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/content/ru/lang.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        (new SourceBoundaryValidator)->assertLanguage($language);
        self::assertArrayHasKey('search', $language);
        self::assertArrayHasKey('navigation', $language);
        self::assertArrayHasKey('reader', $language);
        self::assertArrayNotHasKey('catalog', $language);
        self::assertSame('Примеры макетов', $language['examples']['title'] ?? null);
        self::assertSame('Открыть пример', $language['examples']['open'] ?? null);
        self::assertArrayNotHasKey('components', $language);
        self::assertArrayNotHasKey('pages', $language);
    }

    public function test_public_builder_inputs_reject_legacy_and_package_owned_message_paths(): void
    {
        $validator = new SourceBoundaryValidator;
        $validator->assertPublicInputPath('content/ru/lang.json');

        $this->assertError('TARGET_SITE_JSON_FORBIDDEN', fn () => $validator->assertPublicInputPath('site.json'));
        foreach (['resources/i18n/ru.json', 'resources/language-packs/ru.json', 'resources/system-messages/ru.json'] as $path) {
            $this->assertError('TARGET_PUBLIC_INPUT_FORBIDDEN', fn () => $validator->assertPublicInputPath($path));
        }
    }

    public function test_component_manifests_reject_public_page_copy(): void
    {
        $validator = new SourceBoundaryValidator;
        $validator->assertComponentManifest([
            'id' => 'docara.badge',
            'aliases' => ['badge'],
            'renderer' => 'smart',
            'props' => ['type' => ['type' => 'string']],
        ]);

        foreach (['title', 'description', 'examples', 'markdown', 'html'] as $field) {
            $this->assertError(
                'TARGET_COMPONENT_PROSE_FORBIDDEN',
                fn () => $validator->assertComponentManifest([$field => 'Public prose']),
            );
        }
    }

    private function assertError(string $code, callable $callback): void
    {
        try {
            $callback();
            self::fail("Expected [$code].");
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode);
        }
    }
}
