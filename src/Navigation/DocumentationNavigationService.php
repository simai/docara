<?php

declare(strict_types=1);

namespace Larena\Docara\Navigation;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Larena\Audit\Contracts\AuditEvent;
use Larena\Audit\Runtime\AuditEventPipeline;
use Larena\Docara\Audit\DocaraNavigationAuditEventDescriptor;
use Larena\Docara\Models\DocumentationMenuItemRecord;
use Larena\Docara\Models\DocumentationMenuRecord;
use RuntimeException;

final readonly class DocumentationNavigationService
{
    public function __construct(
        private DatabaseManager $database,
        private AuditEventPipeline $audit,
    ) {
    }

    /** @return Collection<int, DocumentationMenuRecord> */
    public function menus(): Collection
    {
        return DocumentationMenuRecord::query()->orderBy('locale')->orderBy('name')->get();
    }

    public function menu(int $id): DocumentationMenuRecord
    {
        return DocumentationMenuRecord::query()->findOrFail($id);
    }

    /** @return Collection<int, DocumentationMenuItemRecord> */
    public function items(int $menuId): Collection
    {
        return DocumentationMenuItemRecord::query()->where('menu_id', $menuId)->orderBy('sort_order')->orderBy('id')->get();
    }

    /** @return list<object> */
    public function availablePages(string $locale): array
    {
        return $this->database->table('docara_pages')
            ->where('locale', $locale)
            ->where('visibility', 'public')
            ->where('publication_status', 'published')
            ->whereNotNull('published_at')
            ->orderBy('title')
            ->get(['page_ref', 'slug', 'title', 'locale'])
            ->all();
    }

    /** @param array{code:string,name:string,locale:string,is_active?:bool} $input */
    public function createMenu(array $input, string $actor): DocumentationMenuRecord
    {
        return $this->database->connection()->transaction(function () use ($input, $actor): DocumentationMenuRecord {
            $menu = DocumentationMenuRecord::query()->withoutGlobalScope(SoftDeletingScope::class)->where('code', $input['code'])->where('locale', $input['locale'])->first();
            if ($menu instanceof DocumentationMenuRecord) {
                if (!$menu->trashed()) { throw new InvalidArgumentException('navigation_menu_exists'); }
                $menu->restore();
                $menu->fill(['name' => $input['name'], 'is_active' => (bool) ($input['is_active'] ?? false)])->save();
            } else {
                $menu = DocumentationMenuRecord::query()->create([
                    'menu_ref' => 'docara:menu:' . Str::uuid()->toString(),
                    'code' => $input['code'], 'name' => $input['name'], 'locale' => $input['locale'],
                    'is_active' => (bool) ($input['is_active'] ?? false),
                ]);
            }
            $this->record('menu_created', $actor, (string) $menu->menu_ref, ['code' => $menu->code, 'locale' => $menu->locale]);
            return $menu;
        });
    }

    /** @param array{name:string,is_active?:bool} $input */
    public function updateMenu(DocumentationMenuRecord $menu, array $input, string $actor): DocumentationMenuRecord
    {
        $menu->fill(['name' => $input['name'], 'is_active' => (bool) ($input['is_active'] ?? false)])->save();
        $this->record('menu_updated', $actor, (string) $menu->menu_ref, ['code' => $menu->code, 'locale' => $menu->locale]);
        return $menu->refresh();
    }

    /** @param array{page_ref:string,label:string,parent_id?:int|null,sort_order:int,is_active?:bool} $input */
    public function addItem(DocumentationMenuRecord $menu, array $input, string $actor): DocumentationMenuItemRecord
    {
        $this->assertPageMatchesMenu($menu, $input['page_ref']);
        $parentId = $this->validatedParent($menu, $input['parent_id'] ?? null, null);
        $item = DocumentationMenuItemRecord::query()->create([
            'item_ref' => 'docara:menu-item:' . Str::uuid()->toString(), 'menu_id' => $menu->id,
            'parent_id' => $parentId, 'page_ref' => $input['page_ref'], 'label' => $input['label'],
            'sort_order' => $input['sort_order'], 'is_active' => (bool) ($input['is_active'] ?? false),
        ]);
        $this->record('item_created', $actor, (string) $item->item_ref, $this->itemPayload($menu, $item));
        return $item;
    }

    /** @param array{label:string,parent_id?:int|null,sort_order:int,is_active?:bool} $input */
    public function updateItem(DocumentationMenuRecord $menu, DocumentationMenuItemRecord $item, array $input, string $actor): DocumentationMenuItemRecord
    {
        if ((int) $item->menu_id !== (int) $menu->id) {
            throw new RuntimeException('menu_item_not_found');
        }
        $parentId = $this->validatedParent($menu, $input['parent_id'] ?? null, (int) $item->id);
        $this->assertNoCycle((int) $item->id, $parentId);
        $item->fill(['label' => $input['label'], 'parent_id' => $parentId, 'sort_order' => $input['sort_order'], 'is_active' => (bool) ($input['is_active'] ?? false)])->save();
        $this->record('item_updated', $actor, (string) $item->item_ref, $this->itemPayload($menu, $item));
        return $item->refresh();
    }

    public function removeItem(DocumentationMenuRecord $menu, DocumentationMenuItemRecord $item, string $actor): void
    {
        if ((int) $item->menu_id !== (int) $menu->id) {
            throw new RuntimeException('menu_item_not_found');
        }
        DocumentationMenuItemRecord::query()->where('menu_id', $menu->id)->where('parent_id', $item->id)->update(['parent_id' => $item->parent_id]);
        $payload = $this->itemPayload($menu, $item);
        $item->delete();
        $this->record('item_removed', $actor, (string) $item->item_ref, $payload);
    }

    public function deleteMenu(DocumentationMenuRecord $menu, string $actor): void
    {
        $this->database->connection()->transaction(function () use ($menu, $actor): void {
            DocumentationMenuItemRecord::query()->where('menu_id', $menu->id)->delete();
            $menu->delete();
            $this->record('menu_deleted', $actor, (string) $menu->menu_ref, ['code' => $menu->code, 'locale' => $menu->locale]);
        });
    }

    /** @return list<array{id:int,label:string,url:string,children:array}> */
    public function publicTree(string $code, string $locale): array
    {
        $menu = DocumentationMenuRecord::query()->where('code', $code)->where('locale', $locale)->where('is_active', true)->first();
        if (!$menu instanceof DocumentationMenuRecord) {
            return [];
        }
        $rows = $this->database->table('docara_menu_items as items')
            ->join('docara_pages as pages', 'pages.page_ref', '=', 'items.page_ref')
            ->where('items.menu_id', $menu->id)->whereNull('items.deleted_at')->where('items.is_active', true)
            ->where('pages.locale', $locale)->where('pages.visibility', 'public')
            ->where('pages.publication_status', 'published')->whereNotNull('pages.published_at')
            ->orderBy('items.sort_order')->orderBy('items.id')
            ->get(['items.id', 'items.parent_id', 'items.label', 'pages.slug']);
        $byParent = [];
        foreach ($rows as $row) { $byParent[(int) ($row->parent_id ?? 0)][] = $row; }
        $build = function (int $parent) use (&$build, $byParent): array {
            return array_map(static fn ($row): array => [
                'id' => (int) $row->id, 'label' => (string) $row->label,
                'url' => route('larena.docara.public.show', ['slug' => $row->slug, 'locale' => app()->getLocale()]),
                'children' => $build((int) $row->id),
            ], $byParent[$parent] ?? []);
        };
        return $build(0);
    }

    private function assertPageMatchesMenu(DocumentationMenuRecord $menu, string $pageRef): void
    {
        $exists = $this->database->table('docara_pages')->where('page_ref', $pageRef)->where('locale', $menu->locale)
            ->where('visibility', 'public')->where('publication_status', 'published')->whereNotNull('published_at')->exists();
        if (!$exists) { throw new InvalidArgumentException('navigation_page_unavailable'); }
    }

    private function validatedParent(DocumentationMenuRecord $menu, mixed $parentId, ?int $itemId): ?int
    {
        if ($parentId === null || $parentId === '' || (int) $parentId === 0) { return null; }
        $parent = DocumentationMenuItemRecord::query()->where('menu_id', $menu->id)->find((int) $parentId);
        if (!$parent instanceof DocumentationMenuItemRecord || (int) $parent->id === $itemId) { throw new InvalidArgumentException('navigation_parent_invalid'); }
        return (int) $parent->id;
    }

    private function assertNoCycle(int $itemId, ?int $parentId): void
    {
        $seen = [$itemId => true];
        while ($parentId !== null) {
            if (isset($seen[$parentId])) { throw new InvalidArgumentException('navigation_cycle'); }
            $seen[$parentId] = true;
            $parentId = DocumentationMenuItemRecord::query()->whereKey($parentId)->value('parent_id');
            $parentId = $parentId === null ? null : (int) $parentId;
        }
    }

    /** @return array{menu_ref:string,item_ref:string,page_ref:string,parent_id:int|null,sort_order:int,active:bool} */
    private function itemPayload(DocumentationMenuRecord $menu, DocumentationMenuItemRecord $item): array
    {
        return ['menu_ref' => (string) $menu->menu_ref, 'item_ref' => (string) $item->item_ref, 'page_ref' => (string) $item->page_ref, 'parent_id' => $item->parent_id === null ? null : (int) $item->parent_id, 'sort_order' => (int) $item->sort_order, 'active' => (bool) $item->is_active];
    }

    /** @param array<string,mixed> $payload */
    private function record(string $operation, string $actor, string $subject, array $payload): void
    {
        $descriptor = new DocaraNavigationAuditEventDescriptor($operation);
        $this->audit->route($descriptor, AuditEvent::create(
            sourcePackage: $descriptor->sourcePackage(), category: $descriptor->category(), type: $descriptor->type(),
            actor: $actor, subject: $subject, severity: $descriptor->severity(), retentionClass: $descriptor->retentionClass(),
            correlationId: Str::uuid()->toString(), payload: ['operation' => $operation] + $payload,
        ));
    }
}
