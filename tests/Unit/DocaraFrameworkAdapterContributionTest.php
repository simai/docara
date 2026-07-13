<?php

declare(strict_types=1);

use Larena\Docara\Tests\Support\FrameworkContractFixture;
use Larena\Docara\Ui\DocaraFrameworkAdapterContribution;
use Larena\Ui\Registry\FrameworkAdapterRegistry;

(static function (): void {
    $upstream = FrameworkContractFixture::registry();
    $registry = new FrameworkAdapterRegistry($upstream);
    $contribution = new DocaraFrameworkAdapterContribution($upstream);
    $contribution->contribute($registry);

    $adapter = $registry->adapter(DocaraFrameworkAdapterContribution::ADAPTER_ID);
    assert($adapter['id'] === 'docara.pages.admin.collection');
    assert(!str_contains((string) $adapter['id'], '_'));
    assert(preg_match('/sf[0-9]+/', (string) $adapter['id']) !== 1);
    assert($adapter['upstream_recipe'] === 'recipe.admin.collection');
    assert($adapter['renderer'] === [
        'layout_recipe' => 'admin.collection',
        'backend' => 'ui.sf.element',
        'smart_component' => 'smart.table',
    ]);
    assert($adapter['permission'] === ['operation' => 'docara.page.read']);
    assert($adapter['data'] === ['source' => 'docara.pages', 'mode' => 'read-only']);
    assert($adapter['effects'] === ['allowed' => false]);
    assert($adapter['asset_delivery'] === [
        'compatibility_id' => $upstream->compatibilityId(),
        'profile' => $upstream->profile(),
        'activation_owner' => 'larena/core:core.assets',
    ]);
    assert($adapter['support'] === [
        'status' => 'developer-testable',
        'upstream_gap' => 'smart.table.read-only',
        'fallback' => 'larena.ui.sf-runtime-bridge',
    ]);

    $encoded = json_encode($adapter, JSON_THROW_ON_ERROR);
    foreach (['title', 'description', 'props', 'events', 'classes', 'raw_html', 'template', 'callback', 'handler', 'examples'] as $forbidden) {
        assert(!str_contains($encoded, '"' . $forbidden . '"'));
    }

    $plan = $registry->plan(DocaraFrameworkAdapterContribution::ADAPTER_ID);
    assert($plan['schema'] === 'larena.ui.framework_resolved_plan.v1');
    assert($plan['entry_ids'] === [
        'component.buttons',
        'recipe.admin.collection',
        'smart.table',
        'utility.display',
        'utility.flex-direction',
        'utility.gap',
        'utility.overflow',
        'utility.width',
    ]);
    assert($plan['kinds'] === ['component', 'recipe', 'smart-component', 'utility']);
    assert($plan['effects_allowed'] === false);
    assert($plan['production_ready'] === false);
    assert(preg_match('/^[a-f0-9]{64}$/', (string) $plan['registry_sha256']) === 1);
    assert(preg_match('/^[a-f0-9]{64}$/', (string) $plan['plan_sha256']) === 1);

    try {
        $contribution->contribute($registry);
        throw new RuntimeException('Expected duplicate Docara framework adapter contribution to fail.');
    } catch (\InvalidArgumentException $exception) {
        assert($exception->getMessage() === 'ui_framework_adapter_collision:docara.pages.admin.collection');
    }
})();
