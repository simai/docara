<?php

declare(strict_types=1);

namespace Larena\Docara\Dataview;

use Closure;
use Larena\Dataview\Contracts\DataviewSourceDescriptor;
use Larena\Dataview\Contracts\DataviewSourceProvider;

final class DocumentationPagesSourceProvider implements DataviewSourceProvider
{
    /** @param list<array<string, mixed>> $pages */
    public function __construct(private readonly array $pages, private readonly bool $canWrite, private readonly Closure $url, private readonly Closure $statusLabel, private readonly string $actionLabel) {}

    public function descriptor(): DataviewSourceDescriptor
    {
        return new DataviewSourceDescriptor('docara.pages', 'larena/docara', true);
    }

    public function rows(): array
    {
        return array_map(function (array $page): array {
            $edit = ($this->url)($this->canWrite ? 'edit' : 'preview', $page);
            return [
                'title' => ['text' => (string) $page['title'], 'href' => $this->canWrite ? $edit : null, 'strong' => !$this->canWrite],
                'slug' => ['type' => 'code', 'text' => '/' . (string) $page['slug']],
                'status' => ['type' => 'badge', 'tone' => (string) $page['status'], 'text' => ($this->statusLabel)((string) $page['status'])],
                'action' => ['text' => $this->actionLabel, 'href' => $edit],
            ];
        }, $this->pages);
    }
}
