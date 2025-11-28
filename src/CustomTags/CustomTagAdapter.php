<?php

namespace Simai\Docara\CustomTags;


use Simai\Docara\Interface\CustomTagInterface;

final class CustomTagAdapter
{
    public static function toSpec(CustomTagInterface $tag): CustomTagSpec
    {
        $type = $tag->type();
        $open = $tag->openRegex();
        if (! $open) {
            throw new \InvalidArgumentException("CustomTag '{$type}' must define openRegex().");
        }

        $close = $tag->closeRegex() ?: null;

        return new CustomTagSpec(
            type: $type,
            openRegex: $open,
            closeRegex: $close,
            htmlTag: $tag->htmlTag(),
            baseAttrs: $tag->baseAttrs(),
            allowNestingSame: $tag->allowNestingSame(),
            attrsFilter: $tag->attrsFilter(),
            renderer: $tag->renderer()
        );
    }
}
