<?php

declare(strict_types=1);

namespace Larena\Docara\Dataview;

use Larena\Dataview\Contracts\DataviewFieldDescriptor;
use Larena\Dataview\Contracts\DataviewViewDescriptor;
use Larena\Dataview\Enums\DataviewViewType;

final class DocumentationMenusViewDescriptor
{
    public static function make(DocumentationMenusSourceProvider $source): DataviewViewDescriptor
    {
        return new DataviewViewDescriptor('docara.menus.table', $source->descriptor(), DataviewViewType::Table, [
            new DataviewFieldDescriptor('name', 'link', 'lang:docara.menus.name'),
            new DataviewFieldDescriptor('code', 'code', 'lang:docara.menus.code'),
            new DataviewFieldDescriptor('locale', 'text', 'lang:docara.menus.locale'),
            new DataviewFieldDescriptor('status', 'badge', 'lang:docara.menus.status'),
            new DataviewFieldDescriptor('action', 'link', 'lang:docara.menus.action'),
        ]);
    }
}
