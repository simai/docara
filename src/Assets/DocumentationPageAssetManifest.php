<?php

declare(strict_types=1);

namespace Larena\Docara\Assets;

use Larena\Core\Starter\CoreAssetActivationContract;

final class DocumentationPageAssetManifest
{
    public const ASSET_KEY = 'docara.public.page.css';
    public const EDITOR_CSS_KEY = 'docara.admin.blocks.css';
    public const EDITOR_JS_KEY = 'docara.admin.blocks.js';
    public const MENU_JS_KEY = 'docara.admin.menus.js';
    public const FRAMEWORK_CATALOG_CSS_KEY = 'docara.admin.framework-catalog.css';
    public const FRAMEWORK_CATALOG_JS_KEY = 'docara.admin.framework-catalog.js';
    public const ASSET_VERSION = 'page-blocks-v5';

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

    /** @return list<array<string, mixed>> */
    public static function editorAssets(): array
    {
        return [
            ['carrier_key' => 'larena/docara:page-block-editor', 'asset_key' => self::EDITOR_CSS_KEY, 'kind' => 'css', 'critical' => false, 'resource_path' => 'resources/css/page-block-editor.css', 'final_path_owned_by_core_assets' => true],
            ['carrier_key' => 'larena/docara:page-block-editor', 'asset_key' => self::EDITOR_JS_KEY, 'kind' => 'js', 'critical' => false, 'resource_path' => 'resources/js/page-block-editor.js', 'final_path_owned_by_core_assets' => true],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function menuAssets(): array
    {
        return [[
            'carrier_key' => 'larena/docara:menu-editor',
            'asset_key' => self::MENU_JS_KEY,
            'kind' => 'js',
            'critical' => false,
            'resource_path' => 'resources/js/menu-editor.js',
            'final_path_owned_by_core_assets' => true,
        ]];
    }

    /** @return list<array<string, mixed>> */
    public static function frameworkCatalogAssets(): array
    {
        return [
            ['carrier_key' => 'larena/docara:framework-catalog', 'asset_key' => self::FRAMEWORK_CATALOG_CSS_KEY, 'kind' => 'css', 'critical' => false, 'resource_path' => 'resources/css/framework-catalog.css', 'final_path_owned_by_core_assets' => true],
            ['carrier_key' => 'larena/docara:framework-catalog', 'asset_key' => self::FRAMEWORK_CATALOG_JS_KEY, 'kind' => 'js', 'critical' => false, 'resource_path' => 'resources/js/framework-catalog.js', 'final_path_owned_by_core_assets' => true],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function asset(string $assetKey): ?array
    {
        foreach (array_merge(self::publicationAssets(), self::editorAssets(), self::menuAssets(), self::frameworkCatalogAssets()) as $asset) {
            if ($asset['asset_key'] === $assetKey) {
                return $asset;
            }
        }

        return null;
    }
}
