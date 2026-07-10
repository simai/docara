<?php

declare(strict_types=1);

namespace Larena\Docara\Navigation;

use Larena\Admin\Contracts\AdminNavigationContributor;
use Larena\Admin\Navigation\AdminNavigationDescriptor;

final class DocaraAdminNavigationContributor implements AdminNavigationContributor
{
    public function ownerPackage(): string
    {
        return 'larena/docara';
    }

    public function navigationDescriptors(): array
    {
        return [new AdminNavigationDescriptor(
            id: 'docara.pages',
            ownerPackage: $this->ownerPackage(),
            label: 'Pages',
            routeName: 'larena.docara.admin.pages.index',
            routeUri: '/admin/docara/pages',
            category: 'content',
            state: 'product_available',
            accessScope: 'docara.page.read',
            auditEvent: 'docara.page.index_viewed',
            statusCap: 'post_beta_content_site_assembly',
            order: 10,
            group: 'content',
            badge: null,
            knownLimitations: ['local_testing_only', 'not_production_ready'],
            surface: 'product',
            labelKey: 'larena-docara::admin.navigation.pages',
            activeRoutePattern: 'larena.docara.admin.pages.*',
        ), new AdminNavigationDescriptor(
            id: 'docara.menus', ownerPackage: $this->ownerPackage(), label: 'Menus',
            routeName: 'larena.docara.admin.menus.index', routeUri: '/admin/docara/menus',
            category: 'content', state: 'product_available', accessScope: 'docara.navigation.read',
            auditEvent: 'docara.navigation.index_viewed', statusCap: 'navigation_site_structure_developer_slice',
            order: 20, group: 'content', badge: null,
            knownLimitations: ['local_testing_only', 'not_production_ready', 'not_full_sf5_runtime'],
            surface: 'product', labelKey: 'larena-docara::admin.navigation.menus',
            activeRoutePattern: 'larena.docara.admin.menus.*',
        )];
    }
}
