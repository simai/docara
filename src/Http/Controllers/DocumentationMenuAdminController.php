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
use Illuminate\Validation\Rule;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use Larena\Access\Runtime\AccessOperationAuthorizer;
use Larena\Docara\Models\DocumentationMenuItemRecord;
use Larena\Docara\Navigation\DocumentationNavigationService;
use Larena\Admin\Runtime\AdminCollectionDataviewPresenter;
use Larena\Docara\Admin\DocumentationMenuFormPresenter;
use Larena\Docara\Dataview\DocumentationMenusSourceProvider;
use Larena\Docara\Dataview\DocumentationMenusViewDescriptor;

final class DocumentationMenuAdminController extends Controller
{
    public function __construct(
        private readonly DocumentationNavigationService $navigation,
        private readonly ViewFactory $views,
        private readonly Redirector $redirector,
        private readonly Translator $translator,
        private readonly AccessOperationAuthorizer $access,
        private readonly AdminCollectionDataviewPresenter $dataview,
        private readonly DocumentationMenuFormPresenter $formPresenter,
    ) {
    }

    public function index(Request $request): View
    {
        $canWrite = $this->allowed($request, 'docara.navigation.write');
        $source = new DocumentationMenusSourceProvider(
            $this->navigation->menus()->all(),
            fn ($menu): string => route('larena.docara.admin.menus.edit', ['menu' => $menu->id]),
            fn (bool $active): string => $this->translator->get('larena-docara::admin.menus.' . ($active ? 'active' : 'inactive')),
            $this->translator->get('larena-docara::admin.menus.actions.' . ($canWrite ? 'edit' : 'view')),
        );
        $labels = [];
        foreach (['menu' => 'name', 'code' => 'code', 'locale' => 'locale', 'status' => 'status', 'action' => 'action'] as $translation => $field) {
            $labels[$field] = $this->translator->get('larena-docara::admin.menus.columns.' . $translation);
        }
        $labels['_pagination'] = $this->translator->get('larena-docara::admin.menus.aria_label');
        $dataview = $this->dataview->present($source, DocumentationMenusViewDescriptor::make($source), $labels, [
            'title' => $this->translator->get('larena-docara::admin.menus.empty_title'),
            'text' => $this->translator->get('larena-docara::admin.menus.empty_text'),
        ], $this->translator->get('larena-docara::admin.menus.aria_label'), $request->url(), (int) $request->query('page', '1'));

        return $this->views->make('larena-docara::admin.menus.index', [
            'dataview' => $dataview,
            'canWrite' => $canWrite,
            'canDelete' => $this->allowed($request, 'docara.navigation.delete'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->views->make('larena-docara::admin.menus.create', ['components' => $this->formPresenter->create([
            'name' => (string) $request->old('name', ''), 'code' => (string) $request->old('code', 'main'),
            'locale' => (string) $request->old('locale', 'en'), 'is_active' => (string) $request->old('is_active', '1'),
        ], $this->errors($request))]);
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('docara_menus', 'code')->where(fn ($query) => $query->where('locale', $request->input('locale'))) ],
            'name' => ['required', 'string', 'max:255'], 'locale' => ['required', 'in:en,ru'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $menu = $this->navigation->createMenu($input, $this->actor($request));
        return $this->redirector->route('larena.docara.admin.menus.edit', ['menu' => $menu->id])->with('status', $this->message('created'));
    }

    public function edit(Request $request, int $menu): View
    {
        $record = $this->navigation->menu($menu);
        $items = $this->navigation->items($menu);
        $parentOptions = [['text' => $this->translator->get('larena-docara::admin.menus.root'), 'value' => '']];
        foreach ($items as $item) { $parentOptions[] = ['text' => $item->label, 'value' => (string) $item->id]; }
        $pages = $this->navigation->availablePages((string) $record->locale);
        $pageOptions = array_map(static fn ($page): array => ['text' => $page->title . ' · /' . $page->slug, 'value' => $page->page_ref], $pages);
        return $this->views->make('larena-docara::admin.menus.edit', [
            'menu' => $record, 'items' => $items, 'pages' => $pages,
            'canWrite' => $this->allowed($request, 'docara.navigation.write'),
            'canDelete' => $this->allowed($request, 'docara.navigation.delete'),
            'validation' => $this->formPresenter->create(['name' => '', 'code' => '', 'locale' => 'en', 'is_active' => '1'], $this->errors($request))['validation'],
            'settingsComponents' => $this->formPresenter->settings((string) $request->old('name', $record->name), (string) $request->old('is_active', $record->is_active ? '1' : '0') === '1', $this->errors($request)),
            'itemComponents' => $items->mapWithKeys(function ($item) use ($parentOptions): array {
                $options = array_values(array_filter($parentOptions, static fn (array $option): bool => $option['value'] === '' || $option['value'] !== (string) $item->id));
                return [$item->id => $this->formPresenter->item($item->id, $item->label, $item->parent_id, $item->sort_order, $item->is_active, $options)];
            }),
            'addComponents' => $this->formPresenter->add($pageOptions, $parentOptions, $request->old()),
            'noItemsAlert' => $this->formPresenter->alert('no_items'),
            'noPagesAlert' => $this->formPresenter->alert('no_published_pages'),
        ]);
    }

    public function update(Request $request, int $menu): RedirectResponse
    {
        $record = $this->navigation->menu($menu);
        $input = $request->validate(['name' => ['required', 'string', 'max:255'], 'is_active' => ['nullable', 'boolean']]);
        $this->navigation->updateMenu($record, $input, $this->actor($request));
        return $this->back($record->id, 'updated');
    }

    public function storeItem(Request $request, int $menu): RedirectResponse
    {
        $record = $this->navigation->menu($menu);
        $input = $request->validate($this->itemRules(true));
        try { $this->navigation->addItem($record, $input, $this->actor($request)); }
        catch (InvalidArgumentException $e) { return back()->withErrors(['page_ref' => $this->message($e->getMessage())])->withInput(); }
        return $this->back($record->id, 'item_created');
    }

    public function updateItem(Request $request, int $menu, int $item): RedirectResponse
    {
        $record = $this->navigation->menu($menu);
        $itemRecord = DocumentationMenuItemRecord::query()->findOrFail($item);
        $input = $request->validate($this->itemRules(false));
        try { $this->navigation->updateItem($record, $itemRecord, $input, $this->actor($request)); }
        catch (InvalidArgumentException $e) { return back()->withErrors(["items.{$item}" => $this->message($e->getMessage())])->withInput(); }
        return $this->back($record->id, 'item_updated');
    }

    public function destroyItem(Request $request, int $menu, int $item): RedirectResponse
    {
        $record = $this->navigation->menu($menu);
        $itemRecord = DocumentationMenuItemRecord::query()->findOrFail($item);
        $this->navigation->removeItem($record, $itemRecord, $this->actor($request));
        return $this->back($record->id, 'item_removed');
    }

    public function destroy(Request $request, int $menu): RedirectResponse
    {
        $record = $this->navigation->menu($menu);
        $this->navigation->deleteMenu($record, $this->actor($request));
        return $this->redirector->route('larena.docara.admin.menus.index')->with('status', $this->message('deleted'));
    }

    /** @return array<string,array<int,string>> */
    private function itemRules(bool $withPage): array
    {
        $rules = ['label' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'integer'], 'sort_order' => ['required', 'integer', 'min:0', 'max:100000'], 'is_active' => ['nullable', 'boolean']];
        if ($withPage) { $rules['page_ref'] = ['required', 'string', 'max:255']; }
        return $rules;
    }

    private function allowed(Request $request, string $operation): bool { return $this->access->authorize($request, $operation)->isAllowed(); }
    private function actor(Request $request): string { return (string) $request->attributes->get('larena_access_actor'); }
    private function message(string $key): string { return $this->translator->get("larena-docara::admin.menus.messages.{$key}"); }
    private function back(int $menu, string $message): RedirectResponse { return $this->redirector->route('larena.docara.admin.menus.edit', ['menu' => $menu])->with('status', $this->message($message)); }
    /** @return array<string,string> */
    private function errors(Request $request): array { $bag = $request->session()->get('errors'); return $bag instanceof ViewErrorBag ? array_map(static fn (array $messages): string => (string) ($messages[0] ?? ''), $bag->getBag('default')->getMessages()) : []; }
}
