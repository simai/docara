<?php

declare(strict_types=1);

use Larena\Docara\Http\Middleware\AuditDeniedPageUpdate;

return [
    'public' => [
        'enabled' => filter_var(getenv('LARENA_DOCARA_PUBLIC_ROUTES') ?: false, FILTER_VALIDATE_BOOL),
        'allowed_environments' => ['local', 'testing'],
        'prefix' => 'docs',
        'middleware' => [],
    ],
    'admin' => [
        'enabled' => filter_var(getenv('LARENA_DOCARA_ADMIN_ROUTES') ?: false, FILTER_VALIDATE_BOOL),
        'allowed_environments' => ['local', 'testing'],
        'prefix' => 'admin/docara/pages',
        'menu_prefix' => 'admin/docara/menus',
        'middleware' => [
            'web',
            'larena-auth.entry',
            'larena-auth.admin-required',
            'larena-admin.locale',
        ],
        'read_middleware' => ['access:docara.page.read'],
        'write_middleware' => [AuditDeniedPageUpdate::class, 'access:docara.page.write'],
        'publish_middleware' => ['access:docara.page.publish'],
        'navigation_read_middleware' => ['access:docara.navigation.read'],
        'navigation_write_middleware' => ['access:docara.navigation.write'],
        'navigation_delete_middleware' => ['access:docara.navigation.delete'],
    ],
];
