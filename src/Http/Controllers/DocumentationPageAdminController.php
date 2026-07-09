<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Larena\Docara\Authoring\DocumentationPageAuthoringService;
use RuntimeException;

final class DocumentationPageAdminController extends Controller
{
    public function __construct(
        private readonly DocumentationPageAuthoringService $authoring,
        private readonly ViewFactory $views,
        private readonly Redirector $redirector,
    ) {
    }

    public function index(): View
    {
        return $this->views->make('larena-docara::admin.index', ['pages' => $this->authoring->list()]);
    }

    public function create(): View
    {
        return $this->views->make('larena-docara::admin.form', ['page' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = $this->authoring->create($this->validated($request), $this->actor($request));

        return $this->redirector->route('larena.docara.admin.pages.edit', ['slug' => $page->slug]);
    }

    public function edit(string $slug): View
    {
        $page = $this->authoring->find($slug);
        abort_if($page === null, 404);

        return $this->views->make('larena-docara::admin.form', ['page' => $page]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        try {
            $page = $this->authoring->update($slug, $this->validated($request), $this->actor($request));
        } catch (RuntimeException) {
            abort(404);
        }

        return $this->redirector->route('larena.docara.admin.pages.edit', ['slug' => $page->slug]);
    }

    public function publish(Request $request, string $slug): RedirectResponse
    {
        try {
            $page = $this->authoring->publish($slug, $this->actor($request));
        } catch (RuntimeException) {
            abort(404);
        }

        return $this->redirector->route('larena.docara.admin.pages.edit', ['slug' => $page->slug]);
    }

    /** @return array{title:string, slug:string, body:string, status:string} */
    private function validated(Request $request): array
    {
        /** @var array{title:string, slug:string, body:string, status:string} $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:draft,review,archived'],
        ]);

        return $validated;
    }

    private function actor(Request $request): string
    {
        return (string) $request->attributes->get('larena_access_actor');
    }
}
