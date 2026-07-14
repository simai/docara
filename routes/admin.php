<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larena\Docara\Http\Controllers\DocumentationPageAdminController;
use Larena\Docara\Http\Controllers\DocumentationMenuAdminController;
use Larena\Docara\Http\Controllers\DocaraSiteSettingsAdminController;
use Larena\Docara\Http\Controllers\DocumentationPageCompositionController;
use Larena\Setting\Http\Middleware\AuditDeniedSiteSettingUpdate;

Route::prefix((string) config('larena-docara.admin.prefix', 'admin/docara/pages'))
    ->middleware((array) config('larena-docara.admin.middleware', []))
    ->name('larena.docara.admin.pages.')
    ->group(static function (): void {
        Route::middleware((array) config('larena-docara.admin.read_middleware', []))->group(static function (): void {
            Route::get('/', [DocumentationPageAdminController::class, 'index'])->name('index');
            Route::get('/framework-contract/admin-collection', [DocumentationPageAdminController::class, 'frameworkContract'])->name('framework.contract');
            Route::get('/framework-contract/utilities', [DocumentationPageAdminController::class, 'frameworkUtilities'])->name('framework.utilities');
            Route::get('/{slug}/preview', [DocumentationPageAdminController::class, 'preview'])->name('preview');
            Route::get('/{slug}/blocks', [DocumentationPageCompositionController::class, 'edit'])->name('blocks.edit');
        });

        Route::middleware((array) config('larena-docara.admin.write_middleware', []))->group(static function (): void {
            Route::get('/create', [DocumentationPageAdminController::class, 'create'])->name('create');
            Route::post('/', [DocumentationPageAdminController::class, 'store'])->name('store');
            Route::get('/{slug}/edit', [DocumentationPageAdminController::class, 'edit'])->name('edit');
            Route::put('/{slug}', [DocumentationPageAdminController::class, 'update'])->name('update');
            Route::put('/{slug}/blocks', [DocumentationPageCompositionController::class, 'update'])->name('blocks.update');
        });

        Route::middleware((array) config('larena-docara.admin.publish_middleware', []))->group(static function (): void {
            Route::post('/{slug}/publish', [DocumentationPageAdminController::class, 'publish'])->name('publish');
            Route::post('/{slug}/unpublish', [DocumentationPageAdminController::class, 'unpublish'])->name('unpublish');
        });
    });

Route::prefix((string) config('larena-docara.admin.menu_prefix', 'admin/docara/menus'))
    ->middleware((array) config('larena-docara.admin.middleware', []))
    ->name('larena.docara.admin.menus.')
    ->group(static function (): void {
        Route::middleware((array) config('larena-docara.admin.navigation_read_middleware', []))->group(static function (): void {
            Route::get('/', [DocumentationMenuAdminController::class, 'index'])->name('index');
            Route::get('/{menu}/edit', [DocumentationMenuAdminController::class, 'edit'])->whereNumber('menu')->name('edit');
        });
        Route::middleware((array) config('larena-docara.admin.navigation_write_middleware', []))->group(static function (): void {
            Route::get('/create', [DocumentationMenuAdminController::class, 'create'])->name('create');
            Route::post('/', [DocumentationMenuAdminController::class, 'store'])->name('store');
            Route::put('/{menu}', [DocumentationMenuAdminController::class, 'update'])->whereNumber('menu')->name('update');
            Route::post('/{menu}/items', [DocumentationMenuAdminController::class, 'storeItem'])->whereNumber('menu')->name('items.store');
            Route::put('/{menu}/items/{item}', [DocumentationMenuAdminController::class, 'updateItem'])->whereNumber(['menu', 'item'])->name('items.update');
            Route::delete('/{menu}/items/{item}', [DocumentationMenuAdminController::class, 'destroyItem'])->whereNumber(['menu', 'item'])->name('items.destroy');
        });
        Route::delete('/{menu}', [DocumentationMenuAdminController::class, 'destroy'])
            ->whereNumber('menu')->middleware((array) config('larena-docara.admin.navigation_delete_middleware', []))->name('destroy');
    });

Route::prefix((string) config('larena-docara.admin.site_settings_prefix', 'admin/docara/site-settings'))
    ->middleware((array) config('larena-docara.admin.middleware', []))
    ->name('larena.docara.admin.site_settings.')
    ->group(static function (): void {
        Route::get('/', [DocaraSiteSettingsAdminController::class, 'edit'])
            ->middleware('access:setting.site.read')->name('edit');
        Route::put('/', [DocaraSiteSettingsAdminController::class, 'update'])
            ->middleware([AuditDeniedSiteSettingUpdate::class, 'access:setting.site.write'])->name('update');
    });
