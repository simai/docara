<?php

declare(strict_types=1);

namespace Simai\Docara\Preferences;

final class CoreReaderPreferenceContribution implements ReaderPreferenceContribution
{
    public function contribute(ReaderPreferenceRegistryBuilder $registry): void
    {
        $registry->add(new ReaderPreferenceDefinition(
            'appearance.theme',
            'appearance',
            'choice',
            ['system', 'light', 'dark'],
            'docara.theme',
            'prepaint',
            'site',
            [
                'system' => 'reader.theme_system',
                'light' => 'reader.theme_light',
                'dark' => 'reader.theme_dark',
            ],
            [],
            'reader.theme_title',
            'reader.theme_description',
        ));
        $registry->add(new ReaderPreferenceDefinition(
            'appearance.font_size',
            'appearance',
            'choice',
            ['small', 'normal', 'large'],
            'docara.font_size',
            'prepaint',
            'site',
            [
                'small' => 'reader.font_size_small',
                'normal' => 'reader.font_size_normal',
                'large' => 'reader.font_size_large',
            ],
            [],
            'reader.font_size_title',
            'reader.font_size_description',
        ));
        $registry->add(new ReaderPreferenceDefinition(
            'appearance.content_width',
            'appearance',
            'choice',
            ['normal', 'wide'],
            'docara.content_width',
            'prepaint',
            'site',
            [
                'normal' => 'reader.content_width_normal',
                'wide' => 'reader.content_width_wide',
            ],
            [],
            'reader.content_width_title',
            'reader.content_width_description',
        ));
    }
}
