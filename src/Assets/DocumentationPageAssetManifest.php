<?php

declare(strict_types=1);

namespace Larena\Docara\Assets;

use Larena\Core\Starter\CoreAssetActivationContract;

final class DocumentationPageAssetManifest
{
    public const ASSET_KEY = 'docara.public.page.css';

    /** @return list<array<string, mixed>> */
    public static function publicationAssets(): array
    {
        return [[
            'carrier_key' => 'larena/docara:public-page',
            'asset_key' => self::ASSET_KEY,
            'kind' => 'css',
            'critical' => true,
            'resource_path' => 'resources/css/public-page.css',
            'final_path_owned_by_core_assets' => true,
        ]];
    }

    /** @return array<string, mixed> */
    public static function activation(): array
    {
        return CoreAssetActivationContract::adminSmartResourcePackReadOnlyRoute(
            'larena.docara.public_page',
            self::publicationAssets(),
            '/larena/assets/docara',
        );
    }

    /** @return array<string, mixed>|null */
    public static function asset(string $assetKey): ?array
    {
        foreach (self::publicationAssets() as $asset) {
            if ($asset['asset_key'] === $assetKey) {
                return $asset;
            }
        }

        return null;
    }
}
