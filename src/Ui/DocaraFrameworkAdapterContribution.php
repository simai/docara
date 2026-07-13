<?php

declare(strict_types=1);

namespace Larena\Docara\Ui;

use Larena\Ui\Frontend\FrameworkContractRegistry;
use Larena\Ui\Registry\FrameworkAdapterRegistry;

/**
 * Registers only Docara-specific integration metadata.
 *
 * Framework titles, props, examples and other upstream contract fields remain
 * in the immutable FrameworkContractRegistry and are referenced by dotted IDs.
 */
final readonly class DocaraFrameworkAdapterContribution
{
    public const ADAPTER_ID = 'docara.pages.admin.collection';

    public function __construct(private FrameworkContractRegistry $upstream)
    {
    }

    public function contribute(FrameworkAdapterRegistry $registry): void
    {
        $registry->register([
            'id' => self::ADAPTER_ID,
            'upstream_recipe' => 'recipe.admin.collection',
            'renderer' => [
                'layout_recipe' => 'admin.collection',
                'backend' => 'ui.sf.element',
                'smart_component' => 'smart.table',
            ],
            'permission' => [
                'operation' => 'docara.page.read',
            ],
            'data' => [
                'source' => 'docara.pages',
                'mode' => 'read-only',
            ],
            'effects' => [
                'allowed' => false,
            ],
            'asset_delivery' => [
                'compatibility_id' => $this->upstream->compatibilityId(),
                'profile' => $this->upstream->profile(),
                'activation_owner' => 'larena/core:core.assets',
            ],
            'support' => [
                'status' => 'developer-testable',
                'upstream_gap' => 'smart.table.read-only',
                'fallback' => 'larena.ui.sf-runtime-bridge',
            ],
        ]);
    }
}
