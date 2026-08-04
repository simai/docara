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
            ['appearance.theme' => 'system', 'appearance.modal_blur' => 'none'],
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
        self::assertSame('appearance.modal_blur', $manifest['groups'][0]['fields'][1]['id']);
        self::assertSame('none', $manifest['groups'][0]['fields'][1]['configured']);
        self::assertSame('docara.modal_blur', $manifest['groups'][0]['fields'][1]['effect']);
        self::assertSame(['none', 'small', 'medium', 'large'], $manifest['groups'][0]['fields'][1]['values']);
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
            'reader.modal_blur_title' => 'Размытие фона модальных окон',
            'reader.modal_blur_description' => 'Выбор степени размытия.',
            'reader.modal_blur_none' => 'Без размытия',
            'reader.modal_blur_none_description' => 'Только затемнение.',
            'reader.modal_blur_small' => 'Слабое',
            'reader.modal_blur_small_description' => 'Слабое размытие.',
            'reader.modal_blur_medium' => 'Среднее',
            'reader.modal_blur_medium_description' => 'Среднее размытие.',
            'reader.modal_blur_large' => 'Максимальное',
            'reader.modal_blur_large_description' => 'Максимальное размытие.',
        ];
    }
}
