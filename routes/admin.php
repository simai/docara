<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larena\Docara\Http\Controllers\DocumentationPageAdminController;

Route::prefix((string) config('larena-docara.admin.prefix', 'admin/docara/pages'))
    ->middleware((array) config('larena-docara.admin.middleware', []))
    ->name('larena.docara.admin.pages.')
    ->group(static function (): void {
        Route::middleware((array) config('larena-docara.admin.read_middleware', []))->group(static function (): void {
            Route::get('/', [DocumentationPageAdminController::class, 'index'])->name('index');
            Route::get('/{slug}/preview', [DocumentationPageAdminController::class, 'preview'])->name('preview');
        });

        Route::middleware((array) config('larena-docara.admin.write_middleware', []))->group(static function (): void {
            Route::get('/create', [DocumentationPageAdminController::class, 'create'])->name('create');
            Route::post('/', [DocumentationPageAdminController::class, 'store'])->name('store');
            Route::get('/{slug}/edit', [DocumentationPageAdminController::class, 'edit'])->name('edit');
            Route::put('/{slug}', [DocumentationPageAdminController::class, 'update'])->name('update');
        });

        Route::middleware((array) config('larena-docara.admin.publish_middleware', []))->group(static function (): void {
            Route::post('/{slug}/publish', [DocumentationPageAdminController::class, 'publish'])->name('publish');
            Route::post('/{slug}/unpublish', [DocumentationPageAdminController::class, 'unpublish'])->name('unpublish');
        });
    });
