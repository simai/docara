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
        ));
    }
}
