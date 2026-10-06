<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\Preferences\ReaderPreferenceCompiler;

final class ReaderPreferenceCompilerTest extends TestCase
{
    public function test_it_compiles_the_bundled_appearance_fields_into_a_localized_manifest(): void
    {
        $manifest = (new ReaderPreferenceCompiler)->compile(
            ReaderPreferenceCompiler::defaultConfiguration(),
            ['appearance.theme' => 'system', 'appearance.font_size' => 'normal'],
            $this->copy(),
            'docara.preferences.site.v1',
        );

        self::assertTrue($manifest['enabled']);
        self::assertSame('side-panel', $manifest['view']);
        self::assertSame(1, $manifest['schema']);
        self::assertSame('docara.preferences.site.v1', $manifest['storage_key']);
        self::assertSame('appearance', $manifest['groups'][0]['id']);
        self::assertSame('Оформление', $manifest['groups'][0]['title']);
        self::assertSame('appearance.theme', $manifest['groups'][0]['fields'][0]['id']);
        self::assertSame('system', $manifest['groups'][0]['fields'][0]['configured']);
        self::assertSame('docara.theme', $manifest['groups'][0]['fields'][0]['effect']);
        self::assertSame(['system', 'light', 'dark'], $manifest['groups'][0]['fields'][0]['values']);
        self::assertSame('Тема', $manifest['groups'][0]['fields'][0]['title']);
        self::assertSame('appearance.font_size', $manifest['groups'][0]['fields'][1]['id']);
        self::assertSame('normal', $manifest['groups'][0]['fields'][1]['configured']);
        self::assertSame('docara.font_size', $manifest['groups'][0]['fields'][1]['effect']);
        self::assertSame('prepaint', $manifest['groups'][0]['fields'][1]['apply_phase']);
        self::assertSame(['small', 'normal', 'large'], $manifest['groups'][0]['fields'][1]['values']);
        self::assertSame(['', '', ''], array_column($manifest['groups'][0]['fields'][1]['options'], 'description'));
        self::assertCount(2, $manifest['groups'][0]['fields']);
    }

    public function test_retired_fields_listed_by_an_older_site_configuration_are_skipped(): void
    {
        $manifest = (new ReaderPreferenceCompiler)->compile(
            [
                'enabled' => true,
                'view' => 'side-panel',
                'groups' => [
                    ['id' => 'appearance', 'fields' => ['appearance.theme', 'appearance.modal_blur', 'appearance.ui_radius']],
                ],
            ],
            ['appearance.theme' => 'dark'],
            $this->copy(),
            'docara.preferences.site.v1',
        );

        self::assertSame(['appearance.theme'], array_column($manifest['groups'][0]['fields'], 'id'));
    }

    public function test_it_fails_closed_for_an_unknown_field(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('Reader preference field [appearance.unknown] is not registered.');

        (new ReaderPreferenceCompiler)->compile(
            [
                'enabled' => true,
                'view' => 'side-panel',
                'groups' => [
                    ['id' => 'appearance', 'fields' => ['appearance.unknown']],
                ],
            ],
            ['appearance.theme' => 'system'],
            $this->copy(),
            'docara.preferences.site.v1',
        );
    }

    public function test_it_fails_closed_when_a_field_is_projected_twice(): void
    {
        $this->expectException(PortableConfigurationException::class);
        $this->expectExceptionMessage('Reader preference field [appearance.theme] is projected more than once.');

        (new ReaderPreferenceCompiler)->compile(
            [
                'enabled' => true,
                'view' => 'side-panel',
                'groups' => [
                    ['id' => 'appearance', 'fields' => ['appearance.theme']],
                    ['id' => 'secondary', 'fields' => ['appearance.theme']],
                ],
            ],
            ['appearance.theme' => 'system'],
            $this->copy(),
            'docara.preferences.site.v1',
        );
    }

    public function test_storage_key_is_stable_and_isolated_by_base_url(): void
    {
        $first = ReaderPreferenceCompiler::storageKey(['base_url' => '/docs/']);

        self::assertSame($first, ReaderPreferenceCompiler::storageKey(['base_url' => '/docs/']));
        self::assertNotSame($first, ReaderPreferenceCompiler::storageKey(['base_url' => '/portal/']));
        self::assertMatchesRegularExpression('/^docara\.preferences\.[a-f0-9]{16}\.v1$/', $first);
    }

    /** @return array<string, string> */
    private function copy(): array
    {
        return [
            'reader.appearance' => 'Оформление',
            'reader.help' => 'Настройки сохраняются в этом браузере.',
            'reader.appearance_description' => 'Настройки внешнего вида.',
            'reader.theme_title' => 'Тема',
            'reader.theme_description' => 'Выбор темы.',
            'reader.theme_system' => 'Как в системе',
            'reader.theme_system_description' => 'Следовать теме устройства.',
            'reader.theme_light' => 'Светлая',
            'reader.theme_light_description' => 'Всегда светлая тема.',
            'reader.theme_dark' => 'Тёмная',
            'reader.theme_dark_description' => 'Всегда тёмная тема.',
            'reader.font_size_title' => 'Размер шрифта',
            'reader.font_size_description' => 'Размер текста страницы.',
            'reader.font_size_small' => 'Мелкий',
            'reader.font_size_normal' => 'Обычный',
            'reader.font_size_large' => 'Крупный',
        ];
    }
}
