<?php

declare(strict_types=1);

namespace Larena\Docara\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $menu_ref
 * @property string $code
 * @property string $name
 * @property string $locale
 * @property bool $is_active
 */
final class DocumentationMenuRecord extends Model
{
    use SoftDeletes;

    protected $table = 'docara_menus';

    protected $fillable = ['menu_ref', 'code', 'name', 'locale', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
