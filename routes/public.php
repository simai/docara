<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larena\Docara\Http\Controllers\DocumentationPageAssetController;
use Larena\Docara\Http\Controllers\DocumentationPagePublicController;

Route::get('/larena/assets/docara/{assetKey}', DocumentationPageAssetController::class)
    ->where('assetKey', '[a-z0-9][a-z0-9._-]*')
    ->name('larena.docara.assets.show');

Route::get('/', [DocumentationPagePublicController::class, 'home'])
    ->middleware((array) config('larena-docara.public.middleware', []))
    ->name('larena.docara.public.home');

Route::prefix((string) config('larena-docara.public.prefix', 'docs'))
    ->middleware((array) config('larena-docara.public.middleware', []))
    ->group(static function (): void {
        Route::get('/{slug}', [DocumentationPagePublicController::class, 'show'])
            ->name('larena.docara.public.show');
    });
