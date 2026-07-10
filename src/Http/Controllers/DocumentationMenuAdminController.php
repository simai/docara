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
use InvalidArgumentException;
use Larena\Access\Runtime\AccessOperationAuthorizer;
use Larena\Docara\Models\DocumentationMenuItemRecord;
use Larena\Docara\Navigation\DocumentationNavigationService;

final class DocumentationMenuAdminController extends Controller
{
    public function __construct(
        private readonly DocumentationNavigationService $navigation,
        private readonly ViewFactory $views,
        private readonly Redirector $redirector,
        private readonly Translator $translator,
        private readonly AccessOperationAuthorizer $access,
    ) {
    }

    public function index(Request $request): View
    {
        return $this->views->make('larena-docara::admin.menus.index', [
            'menus' => $this->navigation->menus(),
            'canWrite' => $this->allowed($request, 'docara.navigation.write'),
            'canDelete' => $this->allowed($request, 'docara.navigation.delete'),
        ]);
    }

    public function create(): View
    {
        return $this->views->make('larena-docara::admin.menus.create');
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
        return $this->views->make('larena-docara::admin.menus.edit', [
            'menu' => $record, 'items' => $this->navigation->items($menu),
            'pages' => $this->navigation->availablePages((string) $record->locale),
            'canWrite' => $this->allowed($request, 'docara.navigation.write'),
            'canDelete' => $this->allowed($request, 'docara.navigation.delete'),
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
}
