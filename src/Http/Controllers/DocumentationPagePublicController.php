<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Controller;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Assets\DocumentationPageAssetManifest;

final class DocumentationPagePublicController extends Controller
{
    public function __construct(
        private readonly DocumentationPageRepository $pages,
        private readonly ViewFactory $views,
        private readonly Application $app,
    ) {
    }

    public function show(string $slug): View
    {
        $page = $this->pages->findPublishedByLocaleAndSlug('en', $slug);
        abort_if($page === null, 404);

        if (in_array($page->locale, ['en', 'ru'], true)) {
            $this->app->setLocale($page->locale);
        }

        return $this->views->make('larena-docara::public.page', [
            'page' => $page,
            'docaraPublicAssets' => DocumentationPageAssetManifest::activation(),
        ]);
    }
}
