<?php

declare(strict_types=1);

namespace Larena\Docara\Dataview;

use Closure;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewSourceProvider;

final class DocumentationMenusSourceProvider implements DataviewSourceProvider
{
    public function __construct(private readonly array $menus, private readonly Closure $url, private readonly Closure $statusLabel, private readonly string $actionLabel) {}

    public function descriptor(): DataviewSourceDescriptor
    {
        return new DataviewSourceDescriptor('docara.menus', 'larena/docara', true);
    }

    public function rows(): array
    {
        return array_map(fn ($menu): array => [
            'name' => ['text' => (string) $menu->name, 'href' => ($this->url)($menu)],
            'code' => ['type' => 'code', 'text' => (string) $menu->code],
            'locale' => ['text' => strtoupper((string) $menu->locale)],
            'status' => ['type' => 'badge', 'tone' => $menu->is_active ? 'published' : 'archived', 'text' => ($this->statusLabel)((bool) $menu->is_active)],
            'action' => ['text' => $this->actionLabel, 'href' => ($this->url)($menu)],
        ], $this->menus);
    }
}
