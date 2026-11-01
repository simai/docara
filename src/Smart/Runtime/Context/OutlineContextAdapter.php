<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

use Simai\Docara\Declarative\Rendering\View\OutlineItemViewModel;
use Simai\Docara\Declarative\Rendering\View\OutlineViewModel;
use Simai\Docara\Smart\Runtime\SmartInvocation;

final class OutlineContextAdapter implements SmartContextAdapter
{
    public function id(): string
    {
        return 'docara.outline';
    }

    public function prepare(SmartInvocation $invocation): object
    {
        $items = [];
        $settings = [];
        $source = $invocation->props['items'] ?? [];
        // The Framework sf-contents indents by depth below the page's top
        // outline level, which is not always 2.
        $top = $source === [] ? 2 : min(array_map(static fn (array $item): int => (int) $item['level'], $source));
        foreach ($source as $item) {
            $level = (int) $item['level'];
            $settings[] = ['id' => (string) $item['id'], 'text' => (string) $item['text'], 'level' => $level];
            $items[] = new OutlineItemViewModel(
                $this->escape((string) $item['id']),
                $level,
                $this->escape((string) $item['text']),
                match ($level) {
                    2 => '',
                    3 => 'pl-2',
                    4 => 'pl-4',
                    5 => 'pl-6',
                    default => 'pl-8',
                },
                $level - $top,
            );
        }

        return new OutlineViewModel(
            $items,
            $this->escape((string) ($invocation->props['label'] ?? '')),
            json_encode(['items' => $settings], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
