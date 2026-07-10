<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Controller;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Assets\DocumentationPageAssetManifest;
use Larena\Filesystem\Services\SafeFileService;

final class DocumentationPagePublicController extends Controller
{
    public function __construct(
        private readonly DocumentationPageRepository $pages,
        private readonly ViewFactory $views,
        private readonly Application $app,
        private readonly SafeFileService $files,
    ) {
    }

    public function show(string $slug): View
    {
        $page = $this->pages->findPublishedByLocaleAndSlug('en', $slug);
        abort_if($page === null, 404);

        if (in_array($page->locale, ['en', 'ru'], true)) {
            $this->app->setLocale($page->locale);
        }

        $hero = null;
        if (($page->assets[0] ?? null)?->purpose === 'hero') {
            try {
                $record = $this->files->require($page->assets[0]->logicalFileRef);
                if ($record->getAttribute('visibility') === 'public' && str_starts_with((string) $record->getAttribute('mime_type'), 'image/')) {
                    $hero = ['url'=>$this->files->publicUrl($record),'alt'=>$page->assets[0]->altText ?: $record->getAttribute('alt_text') ?: $record->getAttribute('display_name')];
                }
            } catch (\Throwable) { $hero = null; }
        }
        return $this->views->make('larena-docara::public.page', [
            'page' => $page,
            'hero' => $hero,
            'docaraPublicAssets' => DocumentationPageAssetManifest::activation(),
        ]);
    }
}
