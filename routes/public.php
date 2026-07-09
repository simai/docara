<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larena\Docara\Http\Controllers\DocumentationPagePublicController;

Route::prefix((string) config('larena-docara.public.prefix', 'docs'))
    ->middleware((array) config('larena-docara.public.middleware', []))
    ->group(static function (): void {
        Route::get('/{slug}', [DocumentationPagePublicController::class, 'show'])
            ->name('larena.docara.public.show');
    });
