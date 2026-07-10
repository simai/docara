<?php

declare(strict_types=1);

namespace Larena\Docara\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $item_ref
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $page_ref
 * @property string $label
 * @property int $sort_order
 * @property bool $is_active
 */
final class DocumentationMenuItemRecord extends Model
{
    use SoftDeletes;

    protected $table = 'docara_menu_items';

    protected $fillable = ['item_ref', 'menu_id', 'parent_id', 'page_ref', 'label', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
