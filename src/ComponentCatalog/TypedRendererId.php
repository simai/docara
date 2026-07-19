<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

enum TypedRendererId: string
{
    case Card = 'docara.card.v1';
    case Steps = 'docara.steps.v1';
    case Cta = 'docara.cta.v1';
    case Features = 'docara.features.v1';

    public function componentId(): string
    {
        return substr($this->value, 0, strrpos($this->value, '.v'));
    }

    public function directiveName(): string
    {
        return substr($this->componentId(), strlen('docara.'));
    }
}
