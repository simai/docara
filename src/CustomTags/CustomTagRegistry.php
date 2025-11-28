<?php

namespace Simai\Docara\CustomTags;

final class CustomTagRegistry
{
    /** @var array<string,CustomTagSpec> */
    private array $byType = [];

    /** @return CustomTagSpec[] */
    public function getSpecs(): array
    {
        return array_values($this->byType);
    }

    public function get(string $type): ?CustomTagSpec
    {
        return $this->byType[$type] ?? null;
    }

    public function register(CustomTagSpec $s): void
    {
        $this->byType[$s->type] = $s;
    }
}
