<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

/**
 * The page header as the SIMAI Framework sf-site-header: one bar for every
 * SIMAI site, with Docara's brand, header navigation, tools and the
 * navigation sheet in its slots.
 *
 * The element is printed in its first-paint form (standard
 * simai.smart-component §5.4, as the Framework's own documentation example):
 * a static picture of the bar marked data-sf-static, then the slotted
 * originals marked data-sf-held, which the Framework core hides until the
 * element draws and places them. The picture holds copies of the brand, the
 * navigation and the tools without ids, aria-controls and data-docara-*
 * hooks, so labels, scripts and `for` references reach the originals only.
 * The sheet is shown only in the open panel, which needs the script, so the
 * picture has no copy of it.
 */
final readonly class PortableSiteHeader
{
    private const SECTION = '~<section\b[^>]*\bdata-docara-section="docara\.header"[^>]*>(.*?)</section>~s';

    private const BRAND = '~<a\b[^>]*\bdata-docara-smart="docara\.brand"[^>]*>.*?</a>~s';

    /** Attributes a copy in the picture leaves out: they name or hook the original. */
    private const COPY_ATTRIBUTES = '/\s(?:id|for|aria-controls|slot|data-sf-held|data-docara-[a-z0-9-]+)(?:="[^"]*")?(?=[\s>\/])/';

    /**
     * @param  string  $headerRegion  the rendered header region: the docara.header section and any section the site adds
     * @param  array{url:string,label:string}|null  $portalHome  escaped
     * @param  string  $tools  the search, language, reader settings and repository controls in one element
     * @param  string  $sheet  the navigation sheet content, or ''
     * @param  array<string, string>  $copy  escaped UI copy
     * @param  string  $collapse  the Framework breakpoint below which the bar collapses: xl, lg or none
     * @param  string  $attributes  further host attributes, escaped, each with a leading space
     */
    public function render(
        string $headerRegion,
        ?array $portalHome,
        string $tools,
        string $sheet,
        array $copy,
        string $collapse,
        string $attributes = '',
    ): string {
        [$brand, $content, $actions] = $this->split($headerRegion);
        if ($portalHome !== null) {
            $brand = '<a class="docara-portal-home" href="' . $portalHome['url'] . '" data-docara-portal-home>' . $portalHome['label'] . '</a>' . $brand;
        }
        $brand = trim($brand) === '' ? '' : '<div class="docara-header-brand flex items-cross-center gap-1">' . $brand . '</div>';
        $content = trim($content) === '' ? '' : '<div class="docara-header-content flex items-cross-center gap-2 min-w-0">' . $content . '</div>';
        $actions = trim($actions) === '' ? '' : '<div class="docara-header-extra flex items-cross-center gap-1">' . $actions . '</div>';
        $tools = trim($tools);
        $sheet = trim($sheet);

        $picture = '<header class="sf-site-header sf-site-header--collapse-' . $collapse . ' sf-site-header--size-1 sf-site-header--sticky" data-sf-static>'
            . '<div class="sf-site-header-row container w-full m-inline-auto">'
            . $this->iconButton('sf-site-header-menu-button', 'menu', $copy['navigation.open'], ' aria-haspopup="dialog" aria-controls="docara-site-header-panel" aria-expanded="false"')
            . ($brand === '' ? '' : '<div class="sf-site-header-brand">' . $this->copyOf($brand) . '</div>')
            . '<div class="sf-site-header-scrim" aria-hidden="true"></div>'
            . '<div class="sf-site-header-panel" id="docara-site-header-panel">'
            . '<div class="sf-site-header-panel-head"><span class="sf-site-header-panel-heading">' . $copy['navigation.mobile_title'] . '</span>'
            . $this->iconButton('sf-site-header-close-button', 'close', $copy['navigation.close'], '') . '</div>'
            . ($content === '' ? '' : '<div class="sf-site-header-nav">' . $this->copyOf($content) . '</div>')
            . ($actions === '' ? '' : '<div class="sf-site-header-actions">' . $this->copyOf($actions) . '</div>')
            . '</div>'
            . ($tools === '' ? '' : '<div class="sf-site-header-tools">' . $this->copyOf($tools) . '</div>')
            . '</div></header>';

        return '<sf-site-header class="docara-header" collapse="' . $collapse . '" sticky label="' . $copy['navigation.mobile_title'] . '" menu-label="' . $copy['navigation.open'] . '" close-label="' . $copy['navigation.close'] . '" data-docara-region="header"' . $attributes . ' style="display: contents;">'
            . $picture
            . $this->held($brand, 'brand')
            . $this->held($content, null)
            . $this->held($actions, 'actions')
            . $this->held($tools, 'tools')
            . $this->held($sheet, 'sheet')
            . '</sf-site-header>';
    }

    /**
     * The brand, the header navigation and the sections a site adds. The
     * docara.header section's wrapper is left out: the bar's slots are the
     * element's own children. Sections a site adds stay whole and go to the
     * bar's actions slot: wide, they stand before the tools at the end of the
     * row; collapsed, they follow the navigation in the panel.
     *
     * @return array{0:string,1:string,2:string}
     */
    private function split(string $region): array
    {
        if (preg_match(self::SECTION, $region, $section, PREG_OFFSET_CAPTURE) !== 1) {
            return ['', $region, ''];
        }
        $inner = $section[1][0];
        $brand = '';
        if (preg_match(self::BRAND, $inner, $match) === 1) {
            $brand = $match[0];
            $inner = str_replace($brand, '', $inner);
        }
        $added = substr($region, 0, $section[0][1]) . substr($region, $section[0][1] + strlen($section[0][0]));

        return [$brand, trim($inner), trim($added)];
    }

    /** A slotted original, held out of view while the picture stands. */
    private function held(string $html, ?string $slot): string
    {
        if ($html === '') {
            return '';
        }

        return (string) preg_replace('/^<([a-z][a-z0-9-]*)\b/', '<$1' . ($slot === null ? '' : ' slot="' . $slot . '"') . ' data-sf-held', $html, 1);
    }

    private function copyOf(string $html): string
    {
        return (string) preg_replace_callback(
            '/<[a-z][a-z0-9-]*\b[^>]*>/',
            fn (array $tag): string => (string) preg_replace(self::COPY_ATTRIBUTES, '', $tag[0]),
            $html,
        );
    }

    /** A Framework sf-icon-button in its printed form, like the element draws it. */
    private function iconButton(string $class, string $icon, string $label, string $attributes): string
    {
        return '<sf-icon-button class="' . $class . '" icon="' . $icon . '" appearance="link" scheme="on-surface" size="1" aria-label="' . $label . '"' . $attributes . ' style="display: contents;">'
            . '<button type="button" class="sf-icon-button sf-icon-button--size-1 sf-icon-button--icon sf-icon-button--link flex items-cross-center sf-icon-button--on-surface" aria-label="' . $label . '"' . $attributes . ' data-sf-static>'
            . '<sf-icon root-class="pointer-event-none" weight="400" aria-hidden="true" filled="false" icon="' . $icon . '" style="display: contents;"><i class="sf-icon sf-icon--weight-regular pointer-event-none" style="--sf-icon--grade:0;--sf-icon--optical-size:24" aria-hidden="true" data-sf-static>' . $icon . '</i></sf-icon>'
            . '</button></sf-icon-button>';
    }
}
