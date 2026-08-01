<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

/**
 * One public-product policy for the generated catalogue and its pages.
 *
 * The retired renderers remain readable while projects are being migrated,
 * but they are not advertised as independent building blocks. Their jobs are
 * covered by button, grid + card + icon, hero and banner.
 */
final class PublicComponentPolicy
{
    /** @var list<string> */
    private const RETIRED_COMPOSITION_IDS = [
        'docara.columns',
        'docara.cta',
        'docara.features',
        'docara.promo',
        'docara.showcase',
        // Build-owned derived view used only by the authored component index.
        // It shares the typed renderer registry but is not a public authoring component.
        'docara.component_index',
    ];

    public function exposes(string $id): bool
    {
        return ! in_array($id, self::RETIRED_COMPOSITION_IDS, true);
    }
}
