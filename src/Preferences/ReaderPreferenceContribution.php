<?php

declare(strict_types=1);

namespace Simai\Docara\Preferences;

interface ReaderPreferenceContribution
{
    public function contribute(ReaderPreferenceRegistryBuilder $registry): void;
}
