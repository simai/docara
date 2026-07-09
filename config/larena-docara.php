<?php

declare(strict_types=1);

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
        'middleware' => [
            'web',
            'larena-auth.entry',
            'larena-auth.admin-required',
            'access:docara.page.write',
        ],
    ],
];
