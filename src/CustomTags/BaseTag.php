<?php

namespace Simai\Docara\CustomTags;

use Simai\Docara\Interface\CustomTagInterface;

abstract class BaseTag implements CustomTagInterface
{
    abstract public function type(): string;

    public function openRegex(): string
    {
        return '/^\s*!' . preg_quote($this->type(), '/') . '(?:\s+(?<attrs>.+))?$/u';
    }

    public function closeRegex(): string
    {
        return '/^\s*!end' . preg_quote($this->type(), '/') . '\s*$/u';
    }

    public function htmlTag(): string
    {
        return 'div';
    }

    public function baseAttrs(): array
    {
        return [];
    }

    public function allowNestingSame(): bool
    {
        return true;
    }

    public function attrsFilter(): ?callable
    {
        return null;
    }

    public function renderer(): ?callable
    {
        return null;
    }
}
