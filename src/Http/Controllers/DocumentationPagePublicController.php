<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Larena\Docara\Contracts\DocumentationPageRepository;

final class DocumentationPagePublicController extends Controller
{
    public function __construct(
        private readonly DocumentationPageRepository $pages,
        private readonly ViewFactory $views,
    ) {
    }

    public function show(string $slug): View
    {
        $page = $this->pages->findPublishedByLocaleAndSlug('en', $slug);
        abort_if($page === null, 404);

        return $this->views->make('larena-docara::public.page', ['page' => $page]);
    }
}
