<?php

declare(strict_types=1);

namespace Simai\Docara\Design\Artifact;

enum DesignArtifactKind: string
{
    case Layout = 'layout';
    case View = 'view';
    case Section = 'section';
    case Block = 'block';

    public function directory(): string
    {
        return match ($this) {
            self::Layout => 'layouts',
            self::View => 'views',
            self::Section => 'sections',
            self::Block => 'blocks',
        };
    }

    public function schema(): string
    {
        return match ($this) {
            self::Layout => 'declarative-layout.schema.json',
            self::View => 'declarative-view-tree.schema.json',
            self::Section => 'declarative-section.schema.json',
            self::Block => 'declarative-block.schema.json',
        };
    }
}
