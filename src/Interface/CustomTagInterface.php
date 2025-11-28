<?php

namespace Simai\Docara\Interface;

interface CustomTagInterface
{
    public function type(): string;

    public function openRegex(): string;

    public function closeRegex(): string;

    public function htmlTag(): string;

    public function baseAttrs(): array;

    public function allowNestingSame(): bool;

    public function attrsFilter(): ?callable;

    public function renderer(): ?callable;
}
