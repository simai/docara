<?php

declare(strict_types=1);

namespace Simai\Docara\ComponentCatalog;

enum TypedRendererId: string
{
    case Card = 'docara.card.v1';
    case Columns = 'docara.columns.v1';
    case Steps = 'docara.steps.v1';
    case Cta = 'docara.cta.v1';
    case Features = 'docara.features.v1';
    case Hero = 'docara.hero.v1';
    case Logos = 'docara.logos.v1';
    case Promo = 'docara.promo.v1';
    case Showcase = 'docara.showcase.v1';
    case Details = 'docara.details.v1';
    case Download = 'docara.download.v1';
    case Embed = 'docara.embed.v1';
    case Example = 'docara.example.v1';
    case Figure = 'docara.figure.v1';
    case Grid = 'docara.grid.v1';
    case Media = 'docara.media.v1';
    case Tree = 'docara.tree.v1';
    case Alert = 'docara.alert.v1';
    case Tabs = 'docara.tabs.v1';
    case Banner = 'docara.banner.v1';
    case Diagram = 'docara.diagram.v1';
    case Math = 'docara.math.v1';
    case Html = 'docara.html.v1';
    case Code = 'docara.code.v1';
    case Backlinks = 'docara.backlinks.v1';
    case ComponentIndex = 'docara.component_index.v1';

    public function componentId(): string
    {
        return substr($this->value, 0, strrpos($this->value, '.v'));
    }

    public function directiveName(): string
    {
        return substr($this->componentId(), strlen('docara.'));
    }
}
