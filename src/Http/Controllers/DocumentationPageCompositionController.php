<?php

declare(strict_types=1);

namespace Larena\Docara\Http\Controllers;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Larena\Access\Runtime\AccessOperationAuthorizer;
use Larena\Docara\Authoring\DocumentationPageAuthoringService;
use Larena\Docara\Composition\DocaraPageCompositionService;
use Larena\Filesystem\Persistence\DatabaseLogicalFileRepository;
use RuntimeException;

final class DocumentationPageCompositionController extends Controller
{
    public function __construct(
        private readonly DocumentationPageAuthoringService $pages,
        private readonly DocaraPageCompositionService $compositions,
        private readonly DatabaseLogicalFileRepository $files,
        private readonly AccessOperationAuthorizer $access,
        private readonly ViewFactory $views,
        private readonly Redirector $redirector,
        private readonly Translator $translator,
    ) {
    }

    public function edit(Request $request, string $slug): View
    {
        $page = $this->pages->find($slug, $this->locale($request));
        abort_if($page === null, 404);
        $schema = $this->compositions->editorSchema();
        $rawBlocks = old('blocks', $this->compositions->draft($page->pageRef)->toArray()['blocks']);

        return $this->views->make('larena-docara::admin.blocks', [
            'page' => $page,
            'definitions' => $schema,
            'editorBlocks' => $this->editorBlocks(is_array($rawBlocks) ? $rawBlocks : [], $schema),
            'availableImages' => $this->files->all()->filter(static fn ($file): bool => $file->getAttribute('visibility') === 'public' && str_starts_with((string) $file->getAttribute('mime_type'), 'image/')),
            'canWrite' => $this->access->authorize($request, 'docara.page.write')->isAllowed(),
            'canPublish' => $this->access->authorize($request, 'docara.page.publish')->isAllowed(),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $page = $this->pages->find($slug, $this->locale($request));
        abort_if($page === null, 404);
        $blocks = $request->input('blocks', []);
        if (!is_array($blocks)) {
            throw ValidationException::withMessages(['blocks' => $this->translator->get('larena-docara::admin.blocks.validation.invalid')]);
        }
        try {
            $this->compositions->saveDraft($page->pageRef, $blocks, $this->actor($request));
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw ValidationException::withMessages(['blocks' => $this->validationMessage($exception)]);
        }

        return $this->redirector->route('larena.docara.admin.pages.blocks.edit', $this->routeParams($slug, $page->locale))
            ->with('status', $this->translator->get('larena-docara::admin.blocks.saved'));
    }

    /**
     * @param array<int,mixed> $raw
     * @param list<array<string,mixed>> $schema
     * @return list<array{index:int,position:int,value:array<string,mixed>,definition:array<string,mixed>}>
     */
    private function editorBlocks(array $raw, array $schema): array
    {
        $definitions = [];
        foreach ($schema as $definition) {
            $definitions[(string) $definition['key']] = $definition;
        }
        $result = [];
        foreach ($raw as $index => $value) {
            if (!is_array($value)) {
                continue;
            }
            $type = (string) ($value['type'] ?? '');
            if (!isset($definitions[$type])) {
                continue;
            }
            $result[] = ['index' => (int) $index, 'position' => count($result) + 1, 'value' => $value, 'definition' => $definitions[$type]];
        }
        return $result;
    }

    private function validationMessage(\Throwable $exception): string
    {
        return str_contains($exception->getMessage(), 'image_invalid')
            ? $this->translator->get('larena-docara::admin.blocks.validation.image')
            : $this->translator->get('larena-docara::admin.blocks.validation.invalid');
    }

    private function actor(Request $request): string { return (string) $request->attributes->get('larena_access_actor'); }
    private function locale(Request $request): string
    {
        $locale = (string) $request->input('locale', $request->query('locale', 'en'));
        return in_array($locale, ['en', 'ru'], true) ? $locale : 'en';
    }
    /** @return array{slug:string,locale?:string} */
    private function routeParams(string $slug, string $locale): array { return $locale === 'en' ? ['slug' => $slug] : ['slug' => $slug, 'locale' => $locale]; }
}
