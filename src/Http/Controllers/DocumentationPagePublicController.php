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
use Illuminate\Http\Request;
use Larena\Docara\Navigation\DocumentationNavigationService;
use Larena\Docara\Settings\DocaraSiteSettingsService;

final class DocumentationPagePublicController extends Controller
{
    public function __construct(
        private readonly DocumentationPageRepository $pages,
        private readonly ViewFactory $views,
        private readonly Application $app,
        private readonly SafeFileService $files,
        private readonly DocumentationNavigationService $navigation,
        private readonly DocaraSiteSettingsService $siteSettings,
    ) {
    }

    public function show(Request $request, string $slug): View
    {
        $locale = in_array($request->query('locale'), ['en', 'ru'], true) ? (string) $request->query('locale') : 'en';
        $page = $this->pages->findPublishedByLocaleAndSlug($locale, $slug);
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
            'publicNavigation' => $this->navigation->publicTree('main', $page->locale),
            'siteIdentity' => $this->siteSettings->identityFor($page->locale),
            'isHomepage' => false,
        ]);
    }

    public function home(Request $request): View
    {
        $homepage = $this->siteSettings->homepage(is_string($request->query('locale')) ? $request->query('locale') : null);
        abort_if($homepage === null, 404);
        $page = $homepage['page'];
        $this->app->setLocale($homepage['locale']);

        $hero = null;
        if (($page->assets[0] ?? null)?->purpose === 'hero') {
            try {
                $record = $this->files->require($page->assets[0]->logicalFileRef);
                if ($record->getAttribute('visibility') === 'public' && str_starts_with((string) $record->getAttribute('mime_type'), 'image/')) {
                    $hero = ['url' => $this->files->publicUrl($record), 'alt' => $page->assets[0]->altText ?: $record->getAttribute('alt_text') ?: $record->getAttribute('display_name')];
                }
            } catch (\Throwable) {
                $hero = null;
            }
        }

        return $this->views->make('larena-docara::public.page', [
            'page' => $page,
            'hero' => $hero,
            'docaraPublicAssets' => DocumentationPageAssetManifest::activation(),
            'publicNavigation' => $this->navigation->publicTree('main', $homepage['locale']),
            'siteIdentity' => [
                'name' => $homepage['name'], 'description' => $homepage['description'],
                'logo_url' => $homepage['logo_url'], 'favicon_url' => $homepage['favicon_url'],
            ],
            'isHomepage' => true,
        ]);
    }
}
