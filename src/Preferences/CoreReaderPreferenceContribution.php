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
            [
                'system' => 'reader.theme_system_description',
                'light' => 'reader.theme_light_description',
                'dark' => 'reader.theme_dark_description',
            ],
            'reader.theme_title',
            'reader.theme_description',
        ));
        $registry->add(new ReaderPreferenceDefinition(
            'appearance.modal_blur',
            'appearance',
            'choice',
            ['none', 'small', 'medium', 'large'],
            'docara.modal_blur',
            'prepaint',
            'site',
            [
                'none' => 'reader.modal_blur_none',
                'small' => 'reader.modal_blur_small',
                'medium' => 'reader.modal_blur_medium',
                'large' => 'reader.modal_blur_large',
            ],
            [
                'none' => 'reader.modal_blur_none_description',
                'small' => 'reader.modal_blur_small_description',
                'medium' => 'reader.modal_blur_medium_description',
                'large' => 'reader.modal_blur_large_description',
            ],
            'reader.modal_blur_title',
            'reader.modal_blur_description',
        ));
    }
}
