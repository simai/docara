<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\Rule;
use Larena\Docara\Authoring\DocumentationPageAuthoringService;
use Larena\Access\Runtime\AccessOperationAuthorizer;
use RuntimeException;
use Larena\Filesystem\Persistence\DatabaseLogicalFileRepository;
use Larena\Docara\Composition\DocaraPageCompositionService;
use Larena\Docara\Composition\DocaraPageBlockPresenter;
use Illuminate\Database\ConnectionInterface;

final class DocumentationPageAdminController extends Controller
{
    public function __construct(
        private readonly DocumentationPageAuthoringService $authoring,
        private readonly ViewFactory $views,
        private readonly Redirector $redirector,
        private readonly Translator $translator,
        private readonly AccessOperationAuthorizer $access,
        private readonly DatabaseLogicalFileRepository $files,
        private readonly DocaraPageCompositionService $compositions,
        private readonly DocaraPageBlockPresenter $blockPresenter,
        private readonly ConnectionInterface $connection,
    ) {
    }

    public function index(Request $request): View
    {
        return $this->views->make('larena-docara::admin.index', [
            'pages' => $this->authoring->list(),
            'canWrite' => $this->access->authorize($request, 'docara.page.write')->isAllowed(),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->views->make('larena-docara::admin.form', [
            'page' => null,
            'editing' => false,
            'canPublish' => $this->access->authorize($request, 'docara.page.publish')->isAllowed(),
            'availableImages' => $this->availableImages(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = $this->authoring->create($this->validated($request), $this->actor($request));

        return $this->redirector
            ->route('larena.docara.admin.pages.edit', $this->pageRouteParams($page))
            ->with('status', $this->translator->get('larena-docara::admin.messages.created'));
    }

    public function edit(Request $request, string $slug): View
    {
        $page = $this->authoring->find($slug, $this->locale($request));
        abort_if($page === null, 404);

        return $this->views->make('larena-docara::admin.form', [
            'page' => $page,
            'editing' => true,
            'canPublish' => $this->access->authorize($request, 'docara.page.publish')->isAllowed(),
            'availableImages' => $this->availableImages(),
        ]);
    }

    public function preview(Request $request, string $slug): View
    {
        $page = $this->authoring->find($slug, $this->locale($request));
        abort_if($page === null, 404);

        return $this->views->make('larena-docara::admin.preview', [
            'page' => $page,
            'canWrite' => $this->access->authorize($request, 'docara.page.write')->isAllowed(),
            'compositionBlocks' => $this->blockPresenter->present($this->compositions->draft($page->pageRef)),
            'compositionMode' => 'draft',
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $current = $this->authoring->find($slug, $this->locale($request));
        abort_if($current === null, 404);

        try {
            $page = $this->authoring->update(
                $slug,
                $this->validated($request, $slug, $current->publication->status->value === 'published'),
                $this->actor($request),
            );
        } catch (RuntimeException) {
            abort(404);
        }

        return $this->redirector
            ->route('larena.docara.admin.pages.edit', $this->pageRouteParams($page))
            ->with('status', $this->translator->get('larena-docara::admin.messages.updated'));
    }

    public function publish(Request $request, string $slug): RedirectResponse
    {
        try {
            $page = $this->connection->transaction(function () use ($slug, $request) {
                $page = $this->authoring->publish($slug, $this->actor($request), $this->locale($request));
                $this->compositions->publish($page->pageRef, $this->actor($request));
                return $page;
            });
        } catch (RuntimeException) {
            abort(404);
        }

        return $this->redirector
            ->route('larena.docara.admin.pages.edit', $this->pageRouteParams($page))
            ->with('status', $this->translator->get('larena-docara::admin.messages.published'));
    }

    public function unpublish(Request $request, string $slug): RedirectResponse
    {
        try {
            $page = $this->authoring->unpublish($slug, $this->actor($request), $this->locale($request));
        } catch (RuntimeException) {
            abort(404);
        }

        return $this->redirector
            ->route('larena.docara.admin.pages.edit', $this->pageRouteParams($page))
            ->with('status', $this->translator->get('larena-docara::admin.messages.unpublished'));
    }

    /** @return array{title:string, slug:string, body:string, status:string, locale:string, hero_file_ref?:string|null} */
    private function validated(Request $request, ?string $currentSlug = null, bool $allowPublished = false): array
    {
        $request->mergeIfMissing(['locale' => 'en']);
        $locale = (string) $request->input('locale', 'en');
        $uniqueSlug = Rule::unique('docara_pages', 'slug')
            ->where(static fn ($query) => $query->where('locale', $locale));
        if ($currentSlug !== null) {
            $uniqueSlug->ignore($currentSlug, 'slug');
        }

        /** @var array{title:string, slug:string, body:string, status:string, locale:string, hero_file_ref?:string|null} $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueSlug],
            'body' => ['required', 'string'],
            'status' => ['required', 'in:'.($allowPublished ? 'draft,review,published,archived' : 'draft,review,archived')],
            'locale' => ['required', 'in:en,ru'],
            'hero_file_ref' => ['nullable', 'string', function (string $attribute, mixed $value, callable $fail): void {
                if ($value === null || $value === '') { return; }
                $file = $this->files->find((string) $value);
                if ($file === null || $file->getAttribute('visibility') !== 'public' || !str_starts_with((string) $file->getAttribute('mime_type'), 'image/')) {
                    $fail($this->translator->get('larena-docara::admin.validation.hero_file_invalid'));
                }
            }],
        ], [
            'title.required' => $this->translator->get('larena-docara::admin.validation.title_required'),
            'title.string' => $this->translator->get('larena-docara::admin.validation.title_string'),
            'title.max' => $this->translator->get('larena-docara::admin.validation.title_max'),
            'slug.required' => $this->translator->get('larena-docara::admin.validation.slug_required'),
            'slug.string' => $this->translator->get('larena-docara::admin.validation.slug_string'),
            'slug.max' => $this->translator->get('larena-docara::admin.validation.slug_max'),
            'slug.regex' => $this->translator->get('larena-docara::admin.validation.slug_format'),
            'slug.unique' => $this->translator->get('larena-docara::admin.validation.slug_unique'),
            'body.required' => $this->translator->get('larena-docara::admin.validation.body_required'),
            'body.string' => $this->translator->get('larena-docara::admin.validation.body_string'),
            'status.required' => $this->translator->get('larena-docara::admin.validation.status_required'),
            'status.in' => $this->translator->get('larena-docara::admin.validation.status_invalid'),
        ]);

        return $validated;
    }

    private function availableImages(): iterable
    {
        return $this->files->all()->filter(static fn ($file): bool => $file->getAttribute('visibility') === 'public' && str_starts_with((string) $file->getAttribute('mime_type'), 'image/'));
    }

    private function actor(Request $request): string
    {
        return (string) $request->attributes->get('larena_access_actor');
    }

    private function locale(Request $request): string
    {
        $locale = (string) $request->input('locale', $request->query('locale', 'en'));
        return in_array($locale, ['en', 'ru'], true) ? $locale : 'en';
    }

    /** @return array{slug:string,locale?:string} */
    private function pageRouteParams(\Larena\Docara\Contracts\DocumentationPage $page): array
    {
        return $page->locale === 'en' ? ['slug' => $page->slug] : ['slug' => $page->slug, 'locale' => $page->locale];
    }
}
