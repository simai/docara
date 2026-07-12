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
            knownLimitations: ['local_testing_only', 'not_production_ready', 'not_full_simai_framework_runtime'],
            surface: 'product', labelKey: 'larena-docara::admin.navigation.menus',
            activeRoutePattern: 'larena.docara.admin.menus.*',
        ), new AdminNavigationDescriptor(
            id: 'docara.site_settings', ownerPackage: $this->ownerPackage(), label: 'Site Settings',
            routeName: 'larena.docara.admin.site_settings.edit', routeUri: '/admin/docara/site-settings',
            category: 'settings', state: 'product_available', accessScope: 'setting.site.read',
            auditEvent: 'site_settings.viewed', statusCap: 'site_settings_homepage_developer_slice',
            order: 10, group: 'settings', badge: null,
            knownLimitations: ['local_testing_only', 'not_production_ready', 'not_theme_builder', 'not_full_simai_framework_runtime'],
            surface: 'product', labelKey: 'larena-docara::admin.navigation.site_settings',
            activeRoutePattern: 'larena.docara.admin.site_settings.*',
        )];
    }
}
