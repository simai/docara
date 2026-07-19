<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\Framework\FrameworkAssetPlan;

final class PortableHtmlRenderer
{
    /** @return list<string> */
    public function reservedDocumentIds(): array
    {
        return [
            'docara-main',
            'docara-mobile-navigation',
            'docara-search-dialog',
            'docara-search-title',
            'docara-search-status',
            'docara-search-results',
            'docara-reader-settings-dialog',
            'docara-reader-settings-title',
            'docara-reader-settings-help',
            'docara-reader-settings-status',
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  list<array<string, mixed>>  $navigation
     */
    public function render(
        array $page,
        array $navigation,
        string $siteTitle,
        FrameworkAssetPlan $assets,
    ): string {
        $preset = (string) $page['preset'];
        $body = $preset === 'landing'
            ? $this->landing($page)
            : $this->documentation($page, $navigation);
        $branding = $page['branding'];
        $brandTitle = (string) ($branding['title'] ?? $siteTitle);
        $locale = $this->escape((string) $page['locale']);
        $title = $this->escape((string) $page['title'] . ' — ' . $brandTitle);
        $description = trim((string) ($page['description'] ?? ''));
        $descriptionTag = $description === ''
            ? ''
            : "\n    <meta name=\"description\" content=\"{$this->escape($description)}\">";
        $faviconTag = is_string($branding['favicon'] ?? null)
            ? "\n    <link rel=\"icon\" href=\"{$this->escape($branding['favicon'])}\" type=\"{$this->escape((string) $branding['favicon_type'])}\">"
            : '';
        $searchEnabled = ($page['search_enabled'] ?? false) === true
            && is_string($page['search_runtime_url'] ?? null)
            && is_string($page['search_index_url'] ?? null);
        $searchRuntimeTag = $searchEnabled
            ? "\n    <script defer src=\"{$this->escape($page['search_runtime_url'])}\" data-docara-search-runtime></script>"
            : '';
        $searchStyleTag = $searchEnabled
            ? "\n    <style data-docara-search-style>" . $this->searchCss() . '</style>'
            : '';
        $searchDialog = $searchEnabled ? "\n" . $this->searchDialog($page) : '';
        $readerSettingsDialog = "\n" . $this->readerSettingsDialog($page);

        return '<!doctype html>' . "\n"
            . '<html lang="' . $locale . '" class="theme-light">' . "\n"
            . '<head>' . "\n"
            . '    <meta charset="utf-8">' . "\n"
            . '    <meta name="viewport" content="width=device-width, initial-scale=1">' . $descriptionTag . $faviconTag . "\n"
            . '    <title>' . $title . '</title>' . "\n"
            . $this->indent($assets->headHtml(), 4) . "\n"
            . '    ' . $this->themeBootstrap((string) $page['theme']) . "\n"
            . '    <style>' . $this->shellCss() . '</style>' . $searchStyleTag . $searchRuntimeTag . "\n"
            . '</head>' . "\n"
            . '<body class="bg-surface">' . "\n"
            . '    <a class="docara-skip-link bg-surface-0 border radius-1 p-1" href="#docara-main">К содержанию</a>' . "\n"
            . $this->header($page, $navigation, $searchEnabled) . $searchDialog . $readerSettingsDialog . "\n"
            . $body . "\n"
            . '    ' . $this->shellController() . "\n"
            . '</body>' . "\n"
            . '</html>' . "\n";
    }

    /** @param array<string, mixed> $page @param list<array<string, mixed>> $navigation */
    private function documentation(array $page, array $navigation): string
    {
        $outline = is_array($page['outline'] ?? null) ? $page['outline'] : [];
        $breadcrumbs = is_array($page['breadcrumbs'] ?? null) ? $page['breadcrumbs'] : [];
        $hasOutline = $outline !== [];
        $breadcrumbHtml = $breadcrumbs === [] ? '' : $this->breadcrumbs($breadcrumbs) . "\n";
        $mobileOutline = $hasOutline ? $this->mobileOutline($outline) . "\n" : '';
        $desktopOutline = $hasOutline
            ? "\n" . '        <aside class="docara-outline-rail bg-surface-0 border radius-2 p-2">' . "\n"
                . $this->indent($this->outlineNavigation($outline, true), 12) . "\n"
                . '        </aside>'
            : '';
        $previousNext = $this->previousNext(
            is_array($page['previous'] ?? null) ? $page['previous'] : null,
            is_array($page['next'] ?? null) ? $page['next'] : null,
        );

        return '    <div class="docara-docs-layout gap-3 p-3" data-outline="'
            . ($hasOutline ? 'true' : 'false') . '">' . "\n"
            . '        <aside class="docara-sidebar bg-surface-0 border radius-2 p-2">' . "\n"
            . $this->indent($this->navigation($navigation, 'Разделы документации'), 12) . "\n"
            . '        </aside>' . "\n"
            . '        <div class="docara-reading-column flex flex-col gap-2">' . "\n"
            . ($breadcrumbHtml === '' ? '' : $this->indent($breadcrumbHtml, 12))
            . ($mobileOutline === '' ? '' : $this->indent($mobileOutline, 12))
            . '            <main id="docara-main" tabindex="-1" class="docara-content bg-surface-0 border radius-2 p-3" data-width="'
            . $this->escape((string) $page['max_width']) . '">' . "\n"
            . '                <article class="docara-prose">' . "\n"
            . $this->indentRenderedContent((string) $page['content_html'], 20) . "\n"
            . '                </article>'
            . ($previousNext === '' ? '' : "\n" . $this->indent($previousNext, 16)) . "\n"
            . '            </main>' . "\n"
            . '        </div>' . $desktopOutline . "\n"
            . '    </div>';
    }

    /** @param list<array{title: string, url: string|null}> $breadcrumbs */
    private function breadcrumbs(array $breadcrumbs): string
    {
        $items = [];
        $last = count($breadcrumbs) - 1;
        foreach ($breadcrumbs as $index => $breadcrumb) {
            if ($index > 0) {
                $items[] = '<span class="sf-breadcrumbs-item sf-breadcrumbs-item--default" data-sf-breadcrumb-separator="true" aria-hidden="true">'
                    . '<span class="sf-breadcrumbs-item-container flex items-cross-center">'
                    . '<sf-icon icon="chevron_right" aria-hidden="true"></sf-icon></span></span>';
            }
            $title = $this->escape((string) ($breadcrumb['title'] ?? ''));
            if ($index < $last && is_string($breadcrumb['url'] ?? null)) {
                $items[] = '<a class="sf-breadcrumbs-item sf-breadcrumbs-item--link flex items-cross-center decoration-none" href="'
                    . $this->escape($breadcrumb['url']) . '"><span class="sf-breadcrumbs-item-container flex items-cross-center">'
                    . $title . '</span></a>';
            } else {
                $current = $index === $last ? ' aria-current="page"' : '';
                $items[] = '<span class="sf-breadcrumbs-item sf-breadcrumbs-item--default"' . $current
                    . '><span class="sf-breadcrumbs-item-container flex items-cross-center">'
                    . $title . '</span></span>';
            }
        }

        return '<nav data-docara-breadcrumbs data-max-items="' . count($breadcrumbs)
            . '" class="sf-breadcrumbs flex" aria-label="Хлебные крошки">'
            . implode('', $items) . '</nav>';
    }

    /** @param list<array{id: string, level: int, text: string}> $outline */
    private function mobileOutline(array $outline): string
    {
        return '<details data-docara-outline-mobile class="docara-outline-mobile bg-surface-0 border radius-2">' . "\n"
            . '    <summary class="docara-outline-mobile-summary p-2 weight-6">На этой странице</summary>' . "\n"
            . '    <div class="p-2">' . "\n"
            . $this->indent($this->outlineNavigation($outline, false), 8) . "\n"
            . '    </div>' . "\n"
            . '</details>';
    }

    /** @param list<array{id: string, level: int, text: string}> $outline */
    private function outlineNavigation(array $outline, bool $withTitle): string
    {
        $items = [];
        foreach ($outline as $heading) {
            $level = max(2, min(6, (int) ($heading['level'] ?? 2)));
            $items[] = '<li class="docara-outline-item" data-docara-outline-level="' . $level . '">'
                . '<a class="docara-outline-link flex items-cross-center radius-1 p-1 color-on-surface-variant decoration-none" href="#'
                . $this->escape((string) ($heading['id'] ?? '')) . '">'
                . $this->escape((string) ($heading['text'] ?? '')) . '</a></li>';
        }
        $title = $withTitle ? '<p class="m-0 weight-7">На этой странице</p>' . "\n" : '';

        return '<nav data-docara-outline aria-label="На этой странице" class="flex flex-col gap-1">' . "\n"
            . $this->indent($title . '<ul class="docara-outline-list flex flex-col gap-1 m-0 p-0">'
                . implode('', $items) . '</ul>', 4) . "\n"
            . '</nav>';
    }

    /**
     * @param  array{title?: string, url?: string}|null  $previous
     * @param  array{title?: string, url?: string}|null  $next
     */
    private function previousNext(?array $previous, ?array $next): string
    {
        if ($previous === null && $next === null) {
            return '';
        }

        $links = [];
        if (is_string($previous['url'] ?? null)) {
            $links[] = '<a class="docara-document-link docara-document-link--previous flex flex-1 items-cross-center gap-1 bg-surface-container border border-outline-variant radius-2 p-2 color-on-surface decoration-none" rel="prev" href="'
                . $this->escape($previous['url']) . '"><sf-icon icon="arrow_back" aria-hidden="true"></sf-icon>'
                . '<span class="flex flex-col"><span class="color-on-surface-variant">Предыдущая</span>'
                . '<span class="weight-6">' . $this->escape((string) ($previous['title'] ?? '')) . '</span></span></a>';
        }
        if (is_string($next['url'] ?? null)) {
            $links[] = '<a class="docara-document-link docara-document-link--next flex flex-1 items-cross-center content-main-between gap-1 bg-surface-container border border-outline-variant radius-2 p-2 color-on-surface decoration-none" rel="next" href="'
                . $this->escape($next['url']) . '"><span class="flex flex-col"><span class="color-on-surface-variant">Следующая</span>'
                . '<span class="weight-6">' . $this->escape((string) ($next['title'] ?? '')) . '</span></span>'
                . '<sf-icon icon="arrow_forward" aria-hidden="true"></sf-icon></a>';
        }

        return '<nav data-docara-previous-next class="docara-previous-next flex gap-2" aria-label="Переходы между страницами">'
            . implode('', $links) . '</nav>';
    }

    /** @param array<string, mixed> $page */
    private function landing(array $page): string
    {
        return '    <main id="docara-main" tabindex="-1" class="docara-landing p-4">' . "\n"
            . '        <article class="docara-content docara-prose flex flex-col gap-2" data-width="'
            . $this->escape((string) $page['max_width']) . '">' . "\n"
            . $this->indentRenderedContent((string) $page['content_html'], 12) . "\n"
            . '        </article>' . "\n"
            . '    </main>';
    }

    /** @param array<string, mixed> $page @param list<array<string, mixed>> $navigation */
    private function header(array $page, array $navigation, bool $searchEnabled): string
    {
        $branding = $page['branding'];
        $brand = $this->brand($branding, (string) $page['home_url']);
        $mobile = '';
        if ($page['preset'] === 'docs') {
            $mobile = "\n" . '        <details id="docara-mobile-navigation" class="docara-mobile-navigation">' . "\n"
                . '            <summary class="docara-mobile-navigation-summary flex items-cross-center border radius-1 p-1">Разделы</summary>' . "\n"
                . '            <div class="docara-mobile-navigation-panel bg-surface-0 border radius-2 p-2">' . "\n"
                . $this->indent($this->navigation($navigation, 'Мобильная навигация по документации'), 16) . "\n"
                . '            </div>' . "\n"
                . '        </details>';
        }
        $searchTrigger = '';
        if ($searchEnabled) {
            $searchTrigger = '            <button type="button" data-docara-search-trigger aria-haspopup="dialog" aria-controls="docara-search-dialog" aria-label="Открыть поиск по документации" class="docara-search-trigger sf-button sf-button--on-surface sf-button--outline sf-button--size-2 flex items-center gap-1 radius-default">' . "\n"
                . '                <sf-icon icon="search" aria-hidden="true"></sf-icon>' . "\n"
                . '                <span class="docara-search-trigger-label sf-button-text-container">Поиск</span>' . "\n"
                . '                <kbd class="docara-search-shortcut color-on-surface-variant" data-docara-search-shortcut>⌘K</kbd>' . "\n"
                . '            </button>' . "\n";
        }

        return '    <header class="docara-header sticky top-0 z-2 bg-surface-0 border-bottom-1 border-outline-variant">' . "\n"
            . '        <div class="docara-header-row flex items-center content-main-between gap-2 p-2">' . "\n"
            . $this->indent($brand, 12) . "\n"
            . '            <div class="docara-header-actions flex items-center gap-1">' . "\n"
            . $searchTrigger
            . '                <button class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" data-docara-reader-settings-trigger type="button" aria-haspopup="dialog" aria-controls="docara-reader-settings-dialog" aria-label="Открыть настройки чтения">' . "\n"
            . '                    <sf-icon icon="tune" aria-hidden="true"></sf-icon>' . "\n"
            . '                </button>' . "\n"
            . '            </div>' . "\n"
            . '        </div>' . $mobile . "\n"
            . '    </header>';
    }

    /** @param array<string, mixed> $page */
    private function searchDialog(array $page): string
    {
        return '    <dialog id="docara-search-dialog" data-docara-search-dialog data-docara-search-index="'
            . $this->escape((string) $page['search_index_url'])
            . '" class="docara-search-dialog bg-surface-0 border border-outline-variant radius-3 p-0 color-on-surface" aria-labelledby="docara-search-title">' . "\n"
            . '        <div class="flex flex-col gap-2 p-3">' . "\n"
            . '            <div class="flex items-center content-main-between gap-2">' . "\n"
            . '                <h2 id="docara-search-title" class="m-0 weight-7">Поиск по документации</h2>' . "\n"
            . '                <button type="button" data-docara-search-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="Закрыть поиск">' . "\n"
            . '                    <sf-icon icon="close" aria-hidden="true"></sf-icon>' . "\n"
            . '                </button>' . "\n"
            . '            </div>' . "\n"
            . '            <label class="docara-search-input sf-input sf-input--size-1 sf-input--bordered flex flex-col">' . "\n"
            . '                <span class="sf-input-label flex"><span class="sf-input-text">Запрос</span></span>' . "\n"
            . '                <span class="sf-input-field items-cross-center transition flex">' . "\n"
            . '                    <span class="sf-input-left flex"><sf-icon icon="search" aria-hidden="true"></sf-icon></span>' . "\n"
            . '                    <input data-docara-search-input class="sf-input-text-container flex-1" type="search" autocomplete="off" spellcheck="false" placeholder="Например, наследование" aria-controls="docara-search-results" aria-describedby="docara-search-status">' . "\n"
            . '                </span>' . "\n"
            . '            </label>' . "\n"
            . '            <p id="docara-search-status" data-docara-search-status data-state="idle" class="docara-search-status color-on-surface-variant m-0" aria-live="polite">Введите минимум 2 символа</p>' . "\n"
            . '            <ul id="docara-search-results" data-docara-search-results class="docara-search-results flex flex-col gap-1 m-0 p-0"></ul>' . "\n"
            . '        </div>' . "\n"
            . '    </dialog>';
    }

    /** @param array<string, mixed> $page */
    private function readerSettingsDialog(array $page): string
    {
        $configuredTheme = in_array($page['theme'] ?? null, ['system', 'light', 'dark'], true)
            ? (string) $page['theme']
            : 'system';
        $options = [
            'system' => ['Как в системе', 'Автоматически следует настройке устройства.'],
            'light' => ['Светлая', 'Светлое оформление на всех страницах.'],
            'dark' => ['Тёмная', 'Тёмное оформление на всех страницах.'],
        ];
        $optionHtml = [];
        foreach ($options as $value => [$title, $description]) {
            $checked = $value === $configuredTheme ? ' checked' : '';
            $optionHtml[] = '                <label class="sf-radio-button sf-radio-button--size-1 flex items-cross-start gap-1 p-1 radius-1 cursor-pointer transition">' . "\n"
                . '                    <span class="sf-radio-button-box transition flex items-cross-center content-main-center">'
                . '<input data-docara-theme-option name="docara-reader-theme" type="radio" value="'
                . $value . '"' . $checked . '><span class="sf-radio-button-mark"></span></span>' . "\n"
                . '                    <span class="sf-radio-button-container flex flex-col">'
                . '<span class="sf-radio-button-top flex"><span class="sf-radio-button-text">'
                . $title . '</span></span><span class="sf-radio-button-description">'
                . $description . '</span></span>' . "\n"
                . '                </label>';
        }

        return '    <dialog id="docara-reader-settings-dialog" data-docara-reader-settings-dialog data-configured-theme="'
            . $this->escape($configuredTheme)
            . '" class="docara-reader-settings-dialog bg-surface-0 border border-outline-variant radius-3 p-0 color-on-surface" aria-labelledby="docara-reader-settings-title">' . "\n"
            . '        <form method="dialog" class="flex flex-col gap-2 p-3">' . "\n"
            . '            <div class="flex items-center content-main-between gap-2">' . "\n"
            . '                <h2 id="docara-reader-settings-title" class="m-0 weight-7">Настройки чтения</h2>' . "\n"
            . '                <button value="close" data-docara-reader-settings-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="Закрыть настройки чтения">' . "\n"
            . '                    <sf-icon icon="close" aria-hidden="true"></sf-icon>' . "\n"
            . '                </button>' . "\n"
            . '            </div>' . "\n"
            . '            <fieldset class="docara-reader-settings-group flex flex-col gap-1 m-0 p-0 border-none" aria-describedby="docara-reader-settings-help">' . "\n"
            . '                <legend class="weight-6 p-0">Оформление</legend>' . "\n"
            . '                <p id="docara-reader-settings-help" class="color-on-surface-variant m-0">Выберите тему для этого браузера.</p>' . "\n"
            . implode("\n", $optionHtml) . "\n"
            . '            </fieldset>' . "\n"
            . '            <div class="flex content-main-start">' . "\n"
            . '                <button type="button" hidden data-docara-reader-settings-reset class="sf-button sf-button--link sf-button--on-surface sf-button--size-1 radius-default">'
            . '<span class="sf-button-text-container">Вернуть настройку сайта</span></button>' . "\n"
            . '            </div>' . "\n"
            . '            <p id="docara-reader-settings-status" data-docara-reader-settings-status class="sr-only" aria-live="polite"></p>' . "\n"
            . '        </form>' . "\n"
            . '    </dialog>';
    }

    /** @param array<string, string|null> $branding */
    private function brand(array $branding, string $homeUrl): string
    {
        $title = $this->escape((string) $branding['title']);
        $label = is_string($branding['label'] ?? null)
            ? '<span class="docara-brand-label color-on-surface-variant">' . $this->escape($branding['label']) . '</span>'
            : '';
        $mark = '';
        if (is_string($branding['logo'] ?? null)) {
            $mark = '<span class="docara-brand-mark">'
                . '<img class="docara-brand-logo docara-brand-logo--light" src="' . $this->escape($branding['logo']) . '" alt="">';
            if (is_string($branding['logo_dark'] ?? null)) {
                $mark .= '<img class="docara-brand-logo docara-brand-logo--dark" src="'
                    . $this->escape($branding['logo_dark']) . '" alt="">';
            }
            $mark .= '</span>';
        }

        return '<a class="docara-brand flex items-center gap-1 color-on-surface decoration-none" href="'
            . $this->escape($homeUrl) . '">' . $mark
            . '<span class="docara-brand-copy flex flex-col"><span class="weight-7">' . $title . '</span>'
            . $label . '</span></a>';
    }

    /** @param list<array<string, mixed>> $navigation */
    private function navigation(array $navigation, string $label): string
    {
        return '<nav class="docara-navigation" aria-label="' . $this->escape($label) . '">' . "\n"
            . $this->indent($this->menu($navigation), 4) . "\n"
            . '</nav>';
    }

    /** @param list<array<string, mixed>> $nodes */
    private function menu(array $nodes, int $depth = 1): string
    {
        $items = [];
        foreach ($nodes as $node) {
            $children = $node['children'];
            $isBranch = $children !== [];
            $open = $isBranch && ($node['open'] ?? false) === true;
            $itemClasses = 'sf-menu-item flex flex-col' . ($open ? ' open' : '');
            $itemAttributes = $open ? ' expanded aria-expanded="true"' : ($isBranch ? ' aria-expanded="false"' : '');
            $activeRole = match (true) {
                ($node['active'] ?? false) === true => 'page',
                ($node['current_section'] ?? false) === true => 'section',
                ($node['active_ancestor'] ?? false) === true => 'ancestor',
                default => null,
            };
            if ($activeRole !== null) {
                $itemAttributes .= ' data-docara-active-role="' . $activeRole . '"';
            }
            $frameworkDepth = max(1, min(4, $depth));
            $elementClasses = 'sf-menu-element sf-menu-element--level-' . $frameworkDepth
                . ' flex items-cross-center transition' . ($open ? ' open' : '');
            $current = $activeRole === 'page' ? ' aria-current="page"' : '';
            $activeClass = match ($activeRole) {
                'page' => ' weight-7',
                'section' => ' weight-6',
                'ancestor' => ' weight-5',
                default => '',
            };

            if (is_string($node['url'])) {
                $primary = '<a class="sf-menu-element-wrap docara-navigation-link flex flex-1 items-cross-center radius-1'
                    . $activeClass . '" data-docara-menu-link href="' . $this->escape($node['url']) . '"' . $current . '>'
                    . '<span class="sf-menu-element-text">' . $this->escape((string) $node['title']) . '</span></a>';
            } else {
                $primary = '<span class="sf-menu-element-wrap docara-navigation-label flex flex-1 items-cross-center">'
                    . '<span class="sf-menu-element-text">' . $this->escape((string) $node['title']) . '</span></span>';
            }

            $disclosure = '';
            if ($isBranch) {
                $expanded = $open ? 'true' : 'false';
                $action = $open ? 'Свернуть' : 'Развернуть';
                $icon = $open ? 'expand_less' : 'expand_more';
                $containsCurrent = ($node['active_ancestor'] ?? false) === true;
                $currentAttribute = $containsCurrent ? ' data-docara-contains-current="true"' : '';
                $currentLabel = $containsCurrent ? ', содержит текущую страницу' : '';
                $disclosure = '<button type="button" data-docara-disclosure' . $currentAttribute
                    . ' aria-expanded="' . $expanded
                    . '" aria-label="' . $this->escape($action . ': ' . $node['title'] . $currentLabel)
                    . '" class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link radius-default sf-icon-button--size-1/3">'
                    . '<sf-icon icon="' . $icon . '" aria-hidden="true"></sf-icon></button>';
            }

            $nested = $isBranch ? "\n" . $this->indent($this->menu($children, $depth + 1), 4) : '';
            $items[] = '<li class="' . $itemClasses . '" data-docara-navigation-depth="' . $depth . '"' . $itemAttributes . '>' . "\n"
                . '    <div role="presentation" tabindex="-1" class="' . $elementClasses . '">' . $primary . $disclosure . '</div>'
                . $nested . "\n"
                . '</li>';
        }

        return '<ul class="sf-menu flex flex-col">' . ($items === [] ? '' : "\n" . $this->indent(implode("\n", $items), 4) . "\n") . '</ul>';
    }

    private function themeBootstrap(string $configuredTheme): string
    {
        $configured = in_array($configuredTheme, ['light', 'dark', 'system'], true) ? $configuredTheme : 'system';
        $json = json_encode($configured, JSON_THROW_ON_ERROR);

        return '<script data-docara-theme-bootstrap>(function(){var configured=' . $json
            . ",key='docara.reader.theme.v1',valid=/^(system|light|dark)$/,volatile='';"
            . "function frameworkMemory(){return document.documentElement.dataset.docaraFrameworkStorage==='memory'}"
            . "function stored(){if(frameworkMemory())return'';try{var value=window.localStorage.getItem(key)||'';return valid.test(value)?value:''}catch(error){return''}}"
            . "function legacy(){var value=(document.cookie.split('; ').find(function(item){return item.indexOf('sf-theme=')===0})||'').split('=')[1]||'';return /^(light|dark)$/.test(value)?value:''}"
            . "function clearLegacy(){document.cookie='sf-theme=; Path=/; Max-Age=0; SameSite=Lax'}"
            . "function projectLegacy(mode){if(/^(light|dark)$/.test(mode)){document.cookie='sf-theme='+mode+'; Path=/; Max-Age=31536000; SameSite=Lax';return legacy()===mode}clearLegacy();return legacy()===''}"
            . "function apply(mode,source){if(!valid.test(mode)){mode='system'}var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);var root=document.documentElement;root.classList.remove('theme-light','theme-dark');root.classList.add(dark?'theme-dark':'theme-light');root.dataset.docaraThemePreference=mode;root.dataset.docaraThemeSource=source;return mode}"
            . "function preference(){var reader=volatile||stored(),old=reader?'':legacy();return{mode:reader||old||configured,source:reader?'reader':(old?'legacy':'site')}}"
            . "function set(mode){if(!valid.test(mode)){return{applied:false,persisted:false}}var persisted=false;if(!frameworkMemory()){try{window.localStorage.setItem(key,mode);persisted=window.localStorage.getItem(key)===mode}catch(error){}}var cookiePersisted=projectLegacy(mode);if(/^(light|dark)$/.test(mode)){persisted=persisted||cookiePersisted}volatile=persisted?'':mode;apply(mode,'reader');return{applied:true,persisted:persisted}}"
            . "function reset(){volatile='';if(!frameworkMemory()){try{window.localStorage.removeItem(key)}catch(error){}}clearLegacy();apply(configured,'site')}"
            . "var initial=preference();apply(initial.mode,initial.source);window.DocaraReaderTheme={configured:configured,key:key,apply:apply,preference:preference,set:set,reset:reset,syncExternal:function(){volatile='';return preference()},hasOverride:function(){return volatile!==''||stored()!==''||legacy()!==''}};"
            . 'window.SF_BOOT_CONFIG=window.SF_BOOT_CONFIG||{};window.SF_BOOT_CONFIG.preloader={enabled:false};})();</script>';
    }

    private function shellController(): string
    {
        return <<<'HTML'
<script data-docara-shell-controller>(function(){
  function protectNativeLink(link){
    if(link.dataset.docaraLinkBound)return;
    link.dataset.docaraLinkBound='1';
    link.addEventListener('click',function(event){event.stopPropagation()});
    link.addEventListener('keydown',function(event){if(event.key==='Enter'){event.stopPropagation()}});
  }
  function syncDisclosure(item){
    var button=item.querySelector(':scope > .sf-menu-element > [data-docara-disclosure]');
    if(!button)return;
    var open=item.classList.contains('open')||item.hasAttribute('expanded')||item.getAttribute('aria-expanded')==='true';
    if(button.getAttribute('aria-expanded')!==String(open)){button.setAttribute('aria-expanded',String(open))}
    var title=(item.querySelector(':scope > .sf-menu-element .sf-menu-element-text')||{}).textContent||'';
    var containsCurrent=button.dataset.docaraContainsCurrent==='true';
    button.setAttribute('aria-label',(open?'Свернуть: ':'Развернуть: ')+title.trim()+(containsCurrent?', содержит текущую страницу':''));
  }
  function bindShell(){
    document.querySelectorAll('[data-docara-menu-link]').forEach(protectNativeLink);
    document.querySelectorAll('.sf-menu-item').forEach(syncDisclosure);
  }
  function revealActiveNavigation(){
    var rail=document.querySelector('.docara-sidebar');
    var active=rail&&rail.querySelector('[aria-current="page"]');
    if(!rail||!active||rail.dataset.docaraActiveRevealed)return;
    var railRect=rail.getBoundingClientRect();
    var activeRect=active.getBoundingClientRect();
    if(railRect.width<=0||railRect.height<=0||activeRect.width<=0||activeRect.height<=0)return;
    rail.dataset.docaraActiveRevealed='1';
    var inset=8;
    if(activeRect.bottom>railRect.bottom-inset){rail.scrollTop+=activeRect.bottom-(railRect.bottom-inset)}
    else if(activeRect.top<railRect.top+inset){rail.scrollTop+=activeRect.top-(railRect.top+inset)}
    window.removeEventListener('resize',scheduleActiveReveal);
  }
  var activeRevealFrame=0;
  function scheduleActiveReveal(){
    if(activeRevealFrame)return;
    activeRevealFrame=requestAnimationFrame(function(){activeRevealFrame=0;revealActiveNavigation()});
  }
  bindShell();
  function revealWhenReady(){
    var fonts=document.fonts&&document.fonts.ready?document.fonts.ready:Promise.resolve();
    var icon=window.customElements&&window.customElements.whenDefined
      ?Promise.race([window.customElements.whenDefined('sf-icon'),new Promise(function(resolve){setTimeout(resolve,800)})])
      :Promise.resolve();
    Promise.all([fonts,icon]).then(function(){
      requestAnimationFrame(function(){requestAnimationFrame(revealActiveNavigation)});
    });
  }
  window.addEventListener('resize',scheduleActiveReveal,{passive:true});
  if(document.readyState==='complete'){revealWhenReady()}
  else{window.addEventListener('load',revealWhenReady,{once:true})}
  new MutationObserver(bindShell).observe(document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['class','expanded','aria-expanded']});
  var mobile=document.getElementById('docara-mobile-navigation');
  if(mobile){
    var summary=mobile.querySelector('summary');
    mobile.addEventListener('keydown',function(event){if(event.key==='Escape'&&mobile.open){event.preventDefault();mobile.open=false;summary.focus()}});
  }
  var readerTheme=window.DocaraReaderTheme;
  var settingsTrigger=document.querySelector('[data-docara-reader-settings-trigger]');
  var settingsDialog=document.querySelector('[data-docara-reader-settings-dialog]');
  var settingsReset=document.querySelector('[data-docara-reader-settings-reset]');
  var settingsStatus=document.querySelector('[data-docara-reader-settings-status]');
  var themeOptions=Array.from(document.querySelectorAll('[data-docara-theme-option]'));
  var searchDialog=document.querySelector('[data-docara-search-dialog]');
  function announceSettings(message){if(settingsStatus){settingsStatus.textContent='';requestAnimationFrame(function(){settingsStatus.textContent=message})}}
  function syncReaderSettings(){
    if(!readerTheme)return;
    var preference=readerTheme.preference();
    themeOptions.forEach(function(option){option.checked=option.value===preference.mode});
    if(settingsReset){settingsReset.hidden=!readerTheme.hasOverride()}
  }
  if(settingsTrigger&&settingsDialog&&readerTheme){
    settingsTrigger.addEventListener('click',function(){
      if(searchDialog&&searchDialog.open){searchDialog.close()}
      if(!settingsDialog.open){settingsDialog.showModal()}
      syncReaderSettings();
      requestAnimationFrame(function(){var selected=themeOptions.find(function(option){return option.checked});if(selected){selected.focus()}});
    });
    settingsDialog.addEventListener('close',function(){settingsTrigger.focus()});
    themeOptions.forEach(function(option){
      option.addEventListener('change',function(){
        if(!option.checked)return;
        var result=readerTheme.set(option.value);
        if(!result.applied)return;
        syncReaderSettings();
        var label=option.closest('label').querySelector('.sf-radio-button-text').textContent;
        announceSettings(result.persisted?'Тема сохранена: '+label+'.':'Тема применена. Браузер не разрешил сохранить выбор.');
      });
    });
    if(settingsReset){
      settingsReset.addEventListener('click',function(){readerTheme.reset();syncReaderSettings();announceSettings('Восстановлена настройка темы сайта.')});
    }
    var systemTheme=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)');
    if(systemTheme){systemTheme.addEventListener('change',function(){if(document.documentElement.dataset.docaraThemePreference==='system'){readerTheme.apply('system',document.documentElement.dataset.docaraThemeSource||'site')}})}
    window.addEventListener('storage',function(event){if(event.key===readerTheme.key){var preference=readerTheme.syncExternal();readerTheme.apply(preference.mode,preference.source);syncReaderSettings()}});
    syncReaderSettings();
  }
})();</script>
HTML;
    }

    private function shellCss(): string
    {
        return <<<'CSS'
.docara-brand{min-block-size:44px}
.sf-breadcrumbs-item--link{min-inline-size:44px}
html{color-scheme:light dark}.theme-light{color-scheme:light}.theme-dark{color-scheme:dark}body{min-height:100vh;background:var(--sf-surface-1);color:var(--sf-on-surface)}.docara-skip-link{position:fixed;inset-block-start:var(--sf-space-1);inset-inline-start:var(--sf-space-1);z-index:100;transform:translateY(-200%);color:var(--sf-on-surface);text-decoration:none}.docara-skip-link:focus{transform:translateY(0)}.docara-header{min-height:4.5rem}.docara-header-row{max-width:104rem;margin-inline:auto}.docara-brand{min-width:0;text-decoration:none}.docara-brand-mark{display:grid;place-items:center;inline-size:2.25rem;block-size:2.25rem;flex:0 0 auto}.docara-brand-logo{display:block;max-inline-size:100%;max-block-size:100%;object-fit:contain}.docara-brand-logo--dark{display:none}.theme-dark .docara-brand-logo--light:has(+.docara-brand-logo--dark){display:none}.theme-dark .docara-brand-logo--dark{display:block}.docara-brand-copy{min-width:0;line-height:1.15}.docara-brand-label{font-size:.75rem;font-weight:500}[data-docara-reader-settings-trigger],[data-docara-disclosure]{min-inline-size:44px;min-block-size:44px}.docara-mobile-navigation,.docara-outline-mobile{display:none}.docara-navigation-link{min-width:0;min-block-size:44px;color:var(--sf-on-surface);text-decoration:none}.docara-navigation-label{min-width:0;color:var(--sf-on-surface)}.docara-navigation-link .sf-menu-element-text,.docara-navigation-label .sf-menu-element-text{overflow-wrap:anywhere}.docara-navigation [data-docara-active-role="ancestor"]>.sf-menu-element{--sf-menu-element--background-color:var(--sf-surface-container);--sf-menu-element--border-color:var(--sf-outline-variant)}.docara-navigation [data-docara-active-role="section"]>.sf-menu-element{--sf-menu-element--background-color:var(--sf-secondary-container);--sf-menu-element--border-color:var(--sf-outline);border-inline-start-width:2px}.docara-navigation [data-docara-active-role="page"]>.sf-menu-element{--sf-menu-element--background-color:var(--sf-primary-container);--sf-menu-element--border-color:var(--sf-primary);border-inline-start-width:4px}.docara-navigation [data-docara-active-role="section"]>.sf-menu-element>.docara-navigation-link,.docara-navigation [data-docara-active-role="section"]>.sf-menu-element>.docara-navigation-label{color:var(--sf-on-secondary-container)}.docara-navigation [data-docara-active-role="page"]>.sf-menu-element>.docara-navigation-link,.docara-navigation [data-docara-active-role="page"]>.sf-menu-element>.docara-navigation-label{color:var(--sf-on-primary-container)}.docara-skip-link:focus-visible,.docara-brand:focus-visible,.docara-navigation-link:focus-visible,.docara-mobile-navigation-summary:focus-visible,.docara-outline-mobile-summary:focus-visible,.docara-outline-link:focus-visible,.docara-document-link:focus-visible,.sf-breadcrumbs-item--link:focus-visible,[data-docara-reader-settings-trigger]:focus-visible,[data-docara-reader-settings-close]:focus-visible,[data-docara-reader-settings-reset]:focus-visible,[data-docara-disclosure]:focus-visible,sf-button>button:focus-visible{outline:3px solid var(--sf-primary,Highlight);outline-offset:3px}.docara-docs-layout{display:grid;grid-template-columns:minmax(14rem,18rem) minmax(0,1fr);max-width:104rem;margin-inline:auto}.docara-docs-layout[data-outline="true"]{grid-template-columns:minmax(14rem,18rem) minmax(0,1fr) minmax(12rem,15rem)}.docara-sidebar,.docara-outline-rail{align-self:start;position:sticky;inset-block-start:6rem;max-block-size:calc(100vh - 7.5rem);overflow:auto}.docara-reading-column,.docara-content{min-width:0}.docara-content{color:var(--sf-on-surface);scroll-margin-block-start:6rem}.docara-content[data-width="compact"]{max-width:45rem}.docara-content[data-width="normal"]{max-width:60rem}.docara-content[data-width="wide"]{max-width:80rem}.docara-content[data-width="full"]{max-width:none}.sf-breadcrumbs{min-width:0;overflow-x:auto;overflow-y:hidden;overscroll-behavior-inline:contain}.sf-breadcrumbs-item--link{min-block-size:44px}.docara-outline-list{list-style:none}.docara-outline-item[data-docara-outline-level="3"]{padding-inline-start:var(--sf-space-1)}.docara-outline-item[data-docara-outline-level="4"],.docara-outline-item[data-docara-outline-level="5"],.docara-outline-item[data-docara-outline-level="6"]{padding-inline-start:var(--sf-space-2)}.docara-outline-link,.docara-document-link{min-block-size:44px;overflow-wrap:anywhere}.docara-outline-mobile-summary{cursor:pointer}.docara-previous-next{margin-block-start:var(--sf-space-4)}.docara-document-link--next{text-align:end}.docara-prose{line-height:1.65}.docara-prose>*+*{margin-block-start:var(--sf-space-2)}.docara-prose h1{font-size:clamp(2rem,5vw,4rem);line-height:1.08;font-weight:750;letter-spacing:-.035em}.docara-prose h2{font-size:clamp(1.45rem,3vw,2.25rem);line-height:1.2;font-weight:700;margin-block-start:var(--sf-space-4)}.docara-prose h3{font-size:1.25rem;line-height:1.3;font-weight:650;margin-block-start:var(--sf-space-3)}.docara-prose h1[id],.docara-prose h2[id],.docara-prose h3[id],.docara-prose h4[id],.docara-prose h5[id],.docara-prose h6[id]{scroll-margin-block-start:6rem}.docara-prose p,.docara-prose li{max-width:72ch}.docara-landing{align-items:center;justify-content:center;min-height:calc(100vh - 5rem)}.docara-landing .docara-content{width:min(100%,80rem)}.docara-reader-settings-dialog{inline-size:min(calc(100% - 2rem),32rem);max-block-size:min(82vh,40rem);margin:auto;color:var(--sf-on-surface);background:var(--sf-surface-0)}.docara-reader-settings-dialog:not([open]){display:none}.docara-reader-settings-dialog::backdrop{background:color-mix(in srgb,var(--sf-on-surface) 34%,transparent);backdrop-filter:blur(2px)}.docara-reader-settings-group{min-inline-size:0}.docara-reader-settings-group>.sf-radio-button{min-block-size:44px}.docara-reader-settings-group>.sf-radio-button:hover{background:var(--sf-surface-transparent-hover)}.docara-reader-settings-dialog [value="close"]{min-inline-size:44px;min-block-size:44px}[data-docara-reader-settings-reset]{min-block-size:44px}sf-alert,sf-button{display:block}.docara-prose sf-alert,.docara-prose sf-button{margin-block:var(--sf-space-2)}@media(max-width:1152px){.docara-docs-layout[data-outline="true"]{grid-template-columns:minmax(14rem,18rem) minmax(0,1fr)}.docara-outline-rail{display:none}.docara-outline-mobile{display:block}}@media(max-width:800px){.docara-header{min-height:auto}.docara-mobile-navigation{display:block;margin:0 var(--sf-space-1) var(--sf-space-1)}.docara-mobile-navigation-summary{min-block-size:44px;cursor:pointer;color:var(--sf-on-surface);font-weight:650}.docara-mobile-navigation-summary::marker,.docara-outline-mobile-summary::marker{color:var(--sf-primary)}.docara-mobile-navigation-panel{margin-block-start:var(--sf-space-1);max-block-size:min(70vh,36rem);overflow:auto}.docara-docs-layout,.docara-docs-layout[data-outline="true"]{grid-template-columns:minmax(0,1fr);padding:var(--sf-space-1)}.docara-sidebar{display:none}.docara-content{padding:var(--sf-space-2);scroll-margin-block-start:7rem}.docara-prose h1[id],.docara-prose h2[id],.docara-prose h3[id],.docara-prose h4[id],.docara-prose h5[id],.docara-prose h6[id]{scroll-margin-block-start:8rem}.docara-landing{padding:var(--sf-space-1);min-height:auto}}@media(max-width:600px){.docara-previous-next{flex-direction:column}.docara-document-link--next{text-align:start}.docara-reader-settings-dialog{inline-size:calc(100% - 1rem);max-block-size:calc(100vh - 1rem)}}@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important}}
.docara-landing{display:block;max-width:104rem;margin-inline:auto;min-height:auto}
.docara-landing .docara-content{width:100%;margin-inline:auto}
.docara-landing .docara-content>h1:first-child,.docara-landing .docara-content>p:first-of-type{max-width:65ch}
.docara-cta-link{min-block-size:44px}
.docara-cta-link:focus-visible{outline:3px solid var(--sf-primary,Highlight);outline-offset:3px}
.docara-feature-grid>li{min-width:0;max-width:none}
.docara-landing pre code{white-space:pre-wrap;overflow-wrap:anywhere}
CSS;
    }

    private function searchCss(): string
    {
        return <<<'CSS'
.docara-search-dialog{inline-size:min(calc(100% - 2rem),46rem);max-block-size:min(82vh,48rem);margin:auto;color:var(--sf-on-surface);background:var(--sf-surface-0)}
.docara-search-dialog:not([open]){display:none}
.docara-search-dialog::backdrop{background:color-mix(in srgb,var(--sf-on-surface) 34%,transparent);backdrop-filter:blur(2px)}
.docara-search-results{list-style:none;max-block-size:min(52vh,32rem);overflow:auto}
.docara-search-result-item{min-width:0}
.docara-search-result{min-width:0;overflow-wrap:anywhere}
.docara-search-result:hover,.docara-search-result:focus-visible{background:var(--sf-primary-container);color:var(--sf-on-primary-container);outline:3px solid var(--sf-primary);outline-offset:1px}
.docara-search-result-context,.docara-search-result-summary{font-size:.875rem;line-height:1.45}
.docara-search-status[data-state="error"]{color:var(--sf-error)}
.docara-search-trigger{min-block-size:44px}
.docara-search-trigger:focus-visible,[data-docara-search-close]:focus-visible,[data-docara-search-input]:focus-visible{outline:3px solid var(--sf-primary,Highlight);outline-offset:3px}
[data-docara-search-close]{min-inline-size:44px;min-block-size:44px}
.docara-search-shortcut{font:inherit;font-size:.75rem;border:1px solid var(--sf-outline-variant);border-radius:var(--sf-radius-1);padding-inline:calc(var(--sf-space-1)/2)}
@media(max-width:800px){.docara-search-trigger{inline-size:44px;block-size:44px;padding:0}.docara-search-trigger-label,.docara-search-shortcut{display:none}.docara-search-dialog{inline-size:calc(100% - 1rem);max-block-size:calc(100vh - 1rem)}}
CSS;
    }

    private function indent(string $value, int $spaces): string
    {
        $padding = str_repeat(' ', $spaces);

        return $padding . str_replace("\n", "\n" . $padding, rtrim($value));
    }

    private function indentRenderedContent(string $value, int $spaces): string
    {
        $padding = str_repeat(' ', $spaces);
        $indented = $this->indent($value, $spaces);

        return preg_replace_callback(
            '/(<pre\b[^>]*><code\b[^>]*>)(.*?)(<\/code><\/pre>)/is',
            static fn (array $matches): string => $matches[1]
                . str_replace("\n" . $padding, "\n", $matches[2])
                . $matches[3],
            $indented,
        ) ?? $indented;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
