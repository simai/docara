<?php

declare(strict_types=1);

namespace Larena\Docara\Models;

use Illuminate\Database\Eloquent\Model;
use Larena\Docara\Enums\DocumentationVisibility;
use Larena\Docara\Enums\PublicationStatus;

final class DocumentationPageRecord extends Model
{
    protected $table = 'docara_pages';

    /** @var list<string> */
    protected $fillable = [
        'page_ref',
        'slug',
        'title',
        'body',
        'locale',
        'visibility',
        'publication_status',
        'version',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'visibility' => DocumentationVisibility::class,
            'publication_status' => PublicationStatus::class,
            'published_at' => 'immutable_datetime',
        ];
    }
}
