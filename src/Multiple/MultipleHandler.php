<?php

namespace Simai\Docara\Multiple;

class MultipleHandler
{
    public array $items = [];

    public array $flattenByCategory = [];

    public array $realFlattenByCategory = [];

    public array $menuByCategory = [];

    public function setFlatten($locale, $key, $items): void
    {
        $filtered = array_filter($items['flat'], function ($item) {
            return $item['path'] !== null;
        });
        $this->flattenByCategory[$locale][$key] = array_values($filtered);
        $this->realFlattenByCategory[$locale][$key] = $items['flat'];
    }

    public function getMenuByCategory(string $locale, string $key): array
    {
        return $this->menuByCategory[$locale][$key] ?? [];
    }

    public function setMenu($locale, $key, $menu): void
    {
        $this->menuByCategory[$locale][$key] = $menu;
    }
}
