<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simai\Docara\I18n\LanguagePackRepository;
use Simai\Docara\I18n\LocaleRegistry;
use Simai\Docara\I18n\LocaleTag;
use Simai\Docara\I18n\Translator;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Portable\SchemaRepository;

final class LocaleRuntimeTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/docara-i18n-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/languages', 0777, true);
    }

    protected function tearDown(): void
    {
        if (! is_dir($this->root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function test_it_canonicalizes_well_formed_bcp_47_tags_without_a_language_allowlist(): void
    {
        self::assertSame('ru', LocaleTag::from('RU')->value());
        self::assertSame('en', LocaleTag::from('en')->value());
        self::assertSame('ar', LocaleTag::from('ar')->value());
        self::assertSame('zh-Hans', LocaleTag::from('zh-hans')->value());
        self::assertSame('fr-CA', LocaleTag::from('fr-ca')->value());
        self::assertSame('sr-Latn-RS', LocaleTag::from('sr-latn-rs')->value());
        self::assertSame('de-CH-1901', LocaleTag::from('de-ch-1901')->value());
        self::assertSame('sl-rozaj-biske-1994', LocaleTag::from('SL-ROZAJ-BISKE-1994')->value());
        self::assertSame('en-u-ca-gregory', LocaleTag::from('EN-u-CA-GREGORY')->value());
        self::assertSame(
            'zh-CN-a-myext-x-private',
            LocaleTag::from('ZH-cn-A-MYEXT-X-PRIVATE')->value(),
        );

        self::assertFalse(LocaleTag::isWellFormed('pt_BR'));
        self::assertFalse(LocaleTag::isWellFormed('../ru'));
        self::assertFalse(LocaleTag::isWellFormed('en--US'));
    }

    public function test_site_schema_accepts_a_dynamic_five_locale_registry_and_rejects_invalid_keys(): void
    {
        $site = $this->site();
        (new SchemaRepository)->assertValid($site, 'site.schema.json');

        $site['locales']['zh_Hans'] = $site['locales']['zh-Hans'];
        unset($site['locales']['zh-Hans']);

        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('docara-bcp47');
        (new SchemaRepository)->assertValid($site, 'site.schema.json');
    }

    public function test_registry_preserves_arbitrary_locales_directions_roots_prefixes_and_fallback_order(): void
    {
        $registry = LocaleRegistry::fromSite($this->site());

        self::assertSame('ru', $registry->default()->tag->value());
        self::assertSame('rtl', $registry->get('ar')->direction);
        self::assertSame('content/zh-Hans', $registry->get('zh-hans')->contentRoot);
        self::assertSame('fr-ca', $registry->get('fr-CA')->publicPrefix);
        self::assertSame(
            ['fr-CA', 'fr', 'en'],
            array_map(static fn ($locale): string => $locale->tag->value(), $registry->fallbackChain('fr-ca')),
        );
        self::assertCount(6, $registry->all());
    }

    public function test_registry_fails_closed_for_missing_fallbacks_cycles_and_duplicate_prefixes(): void
    {
        $site = $this->site();
        $site['locales']['fr']['fallbacks'] = ['fr-CA'];
        $this->assertConfigurationError('LOCALE_FALLBACK_CYCLE', fn () => LocaleRegistry::fromSite($site));

        $site = $this->site();
        $site['locales']['ar']['fallbacks'] = ['fa'];
        $this->assertConfigurationError('LOCALE_FALLBACK_NOT_CONFIGURED', fn () => LocaleRegistry::fromSite($site));

        $site = $this->site();
        $site['locales']['ar']['public_prefix'] = 'en';
        $this->assertConfigurationError('LOCALE_PUBLIC_PREFIX_DUPLICATE', fn () => LocaleRegistry::fromSite($site));
    }

    public function test_language_packs_and_translator_resolve_exact_and_configured_fallback_copy(): void
    {
        $this->writePack('en', [
            'common.greeting' => 'Hello, {name}!',
            'common.only_en' => 'English fallback',
        ], [
            'ui.alert' => [
                'title' => 'Alert',
                'description' => 'Shows an important message.',
                'limitations' => [],
                'states' => ['info' => 'Information'],
                'parameters' => [
                    'tone' => ['label' => 'Tone', 'description' => 'Visual intent.'],
                ],
            ],
        ]);
        $this->writePack('fr', [
            'common.greeting' => 'Bonjour, {name}!',
        ], [
            'ui.alert' => [
                'title' => 'Alerte',
                'states' => ['info' => 'Information'],
                'parameters' => ['tone' => ['label' => 'Ton']],
            ],
        ]);
        $this->writePack('fr-CA', [
            'common.continue' => 'Continuer',
        ], []);

        $site = $this->site();
        foreach (['en', 'fr', 'fr-CA'] as $tag) {
            $site['locales'][$tag]['language_pack'] = "languages/$tag.json";
        }
        $translator = new Translator(
            LocaleRegistry::fromSite($site),
            new LanguagePackRepository($this->root),
        );

        self::assertSame('Continuer', $translator->message('fr-ca', 'common.continue'));
        self::assertSame('Bonjour, Rim!', $translator->message('fr-CA', 'common.greeting', ['name' => 'Rim']));
        self::assertSame('English fallback', $translator->message('fr-CA', 'common.only_en'));
        self::assertSame([
            'title' => 'Alerte',
            'description' => 'Shows an important message.',
            'limitations' => [],
            'states' => ['info' => 'Information'],
            'parameters' => [
                'tone' => ['label' => 'Ton', 'description' => 'Visual intent.'],
            ],
        ], $translator->component('fr-CA', 'ui.alert'));
    }

    public function test_bundled_packs_cover_the_acceptance_locales_and_project_references_are_confined(): void
    {
        $site = $this->site();
        $repository = new LanguagePackRepository($this->root);
        $registry = LocaleRegistry::fromSite($site);

        foreach (['ru', 'en', 'ar', 'zh-Hans', 'fr-CA'] as $tag) {
            self::assertSame($tag, $repository->load($registry->get($tag))->locale->value());
        }

        $site['locales']['ru']['language_pack'] = '../outside.json';
        $registry = LocaleRegistry::fromSite($site);
        $this->assertConfigurationError(
            'LANGUAGE_PACK_REFERENCE_INVALID',
            fn () => $repository->load($registry->get('ru')),
        );
    }

    /** @return array<string, mixed> */
    private function site(): array
    {
        return [
            'schema' => 'docara.site.v1',
            'preset' => 'docs',
            'title' => 'Language-independent site',
            'content_root' => 'content/ru',
            'framework_lock' => 'framework.lock.json',
            'default_locale' => 'ru',
            'locales' => [
                'ru' => $this->locale('Русский', 'ltr', 'content/ru', '@docara/ru', '', []),
                'en' => $this->locale('English', 'ltr', 'content/en', '@docara/en', 'en', []),
                'ar' => $this->locale('العربية', 'rtl', 'content/ar', '@docara/ar', 'ar', ['en']),
                'zh-Hans' => $this->locale('简体中文', 'ltr', 'content/zh-Hans', '@docara/zh-Hans', 'zh-hans', ['en']),
                'fr' => $this->locale('Français', 'ltr', 'content/fr', 'languages/fr.json', 'fr', ['en']),
                'fr-CA' => $this->locale('Français (Canada)', 'ltr', 'content/fr-CA', '@docara/fr-CA', 'fr-ca', ['fr']),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function locale(
        string $label,
        string $direction,
        string $root,
        string $pack,
        string $prefix,
        array $fallbacks,
    ): array {
        return [
            'label' => $label,
            'direction' => $direction,
            'content_root' => $root,
            'language_pack' => $pack,
            'public_prefix' => $prefix,
            'fallbacks' => $fallbacks,
        ];
    }

    /** @param array<string, string> $messages @param array<string, array<string, mixed>> $components */
    private function writePack(string $locale, array $messages, array $components): void
    {
        file_put_contents($this->root . "/languages/$locale.json", json_encode([
            'schema' => 'docara.language_pack.v1',
            'locale' => $locale,
            'messages' => $messages,
            'components' => $components,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function assertConfigurationError(string $code, callable $callback): void
    {
        try {
            $callback();
            self::fail("Expected configuration error [$code].");
        } catch (PortableConfigurationException $exception) {
            self::assertSame($code, $exception->errorCode);
        }
    }
}
