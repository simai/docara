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
        foreach ($invocation->props['items'] ?? [] as $item) {
            $level = (int) $item['level'];
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
            );
        }

        return new OutlineViewModel($items, $this->escape((string) ($invocation->props['label'] ?? '')));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
