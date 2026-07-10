<?php

declare(strict_types=1);

namespace Larena\Docara\Dataview;

use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Enums\DataviewViewType;

final class DocumentationPagesViewDescriptor
{
    public static function make(DocumentationPagesSourceProvider $source): DataviewViewDescriptor
    {
        return new DataviewViewDescriptor('docara.pages.table', $source->descriptor(), DataviewViewType::Table, [
            new DataviewFieldDescriptor('title', 'link', 'lang:docara.pages.title'),
            new DataviewFieldDescriptor('slug', 'code', 'lang:docara.pages.slug'),
            new DataviewFieldDescriptor('status', 'badge', 'lang:docara.pages.status'),
            new DataviewFieldDescriptor('action', 'link', 'lang:docara.pages.action'),
        ]);
    }
}
