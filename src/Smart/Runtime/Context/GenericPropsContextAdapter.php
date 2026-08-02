<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

use Simai\Docara\Smart\Runtime\SmartInvocation;

final class GenericPropsContextAdapter implements SmartContextAdapter
{
    public function id(): string
    {
        return 'smart.props';
    }

    public function prepare(SmartInvocation $invocation): object
    {
        $props = [];
        foreach ($invocation->props as $name => $value) {
            $props[$this->camel((string) $name)] = is_string($value) ? $this->escape($value) : $value;
        }
        $props['icon'] ??= null;
        $props['radius'] ??= null;
        $props['runtimePair'] = $this->escape((string) ($invocation->provenance['runtime_pair'] ?? ''));

        return (object) $props;
    }

    private function camel(string $name): string
    {
        return preg_replace_callback('/[-_]([a-z])/', static fn (array $match): string => strtoupper($match[1]), $name) ?? $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
