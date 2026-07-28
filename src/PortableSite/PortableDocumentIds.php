<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

final class PortableDocumentIds
{
    /** @return list<string> */
    public static function reserved(): array
    {
        return [
            'docara-main',
            'docara-mobile-navigation',
            'docara-mobile-navigation-title',
            'docara-outline-dialog',
            'docara-outline-title',
            'docara-search-dialog',
            'docara-search-title',
            'docara-search-status',
            'docara-search-results',
            'docara-reader-settings-dialog',
            'docara-reader-settings-title',
            'docara-reader-settings-help',
            'docara-reader-settings-status',
        ];
    }
}
