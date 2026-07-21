<!doctype html>
<html lang="<?= $view->locale ?>" dir="<?= $view->direction ?>" class="theme-light" data-docara-documentation-version="<?= $view->documentationVersion ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="docara:documentation-version" content="<?= $view->documentationVersion ?>">
<?php if ($view->description !== null) { ?>
    <meta name="description" content="<?= $view->description ?>">
<?php } ?>
<?php if ($view->favicon !== null) { ?>
    <link rel="icon" href="<?= $view->favicon ?>"<?php if ($view->faviconType !== null) { ?> type="<?= $view->faviconType ?>"<?php } ?>>
<?php } ?>
<?php foreach ($view->alternates as $alternate) { ?>
    <link rel="alternate" hreflang="<?= $alternate['locale'] ?>" href="<?= $alternate['url'] ?>">
<?php } ?>
    <title><?= $view->documentTitle ?></title>
<?= $view->headHtml ?>
    <link rel="stylesheet" href="<?= $view->shellCssUrl ?>" data-docara-declarative-shell-style>
<?= $view->themeBootstrap ?>
<?php if ($view->searchEnabled) { ?>
    <script defer src="<?= $view->searchRuntimeUrl ?>" data-docara-search-runtime></script>
<?php } ?>
</head>
<body class="bg-surface">
    <a class="docara-skip-link bg-surface-0 border radius-1 p-1" href="#docara-main"><?= $view->copy['shell.skip_to_content'] ?></a>
    <header class="docara-header sticky top-0 z-2 bg-surface-0 border-bottom-1 border-outline-variant" data-docara-region="header">
        <div class="docara-header-row flex items-center content-main-between gap-2 p-1">
            <?= $view->regions['header'] ?>
            <div class="docara-header-actions flex items-center gap-1">
<?php if ($view->preset === 'docs' && $view->regions['sidebar'] !== '') { ?>
                <button type="button" data-docara-sheet-trigger aria-haspopup="dialog" aria-controls="docara-mobile-navigation" aria-expanded="false" class="docara-mobile-navigation-trigger sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['navigation.open'] ?>">
                    <sf-icon icon="menu" aria-hidden="true"></sf-icon>
                </button>
<?php } ?>
<?php if ($view->searchEnabled) { ?>
                <button type="button" data-docara-search-trigger aria-haspopup="dialog" aria-controls="docara-search-dialog" aria-expanded="false" aria-label="<?= $view->copy['search.open'] ?>" class="docara-search-trigger sf-button sf-button--on-surface sf-button--outline sf-button--size-2 flex items-center gap-1 radius-default">
                    <sf-icon icon="search" aria-hidden="true"></sf-icon>
                    <span class="docara-search-trigger-label sf-button-text-container"><?= $view->copy['search.label'] ?></span>
                    <kbd class="docara-search-shortcut color-on-surface-variant" data-docara-search-shortcut>⌘K</kbd>
                </button>
<?php } ?>
<?php if (count($view->languageOptions) > 1) { ?>
                <label class="sf-select sf-select--size-1"><span class="sr-only"><?= $view->copy['language.label'] ?></span><select data-docara-language-switcher aria-label="<?= $view->copy['language.label'] ?>">
<?php foreach ($view->languageOptions as $option) { ?>
                    <option value="<?= $option['url'] ?>" lang="<?= $option['locale'] ?>"<?php if ($option['current']) { ?> selected<?php } ?>><?= $option['label'] ?></option>
<?php } ?>
                </select></label>
<?php } ?>
                <button class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" data-docara-reader-settings-trigger type="button" aria-haspopup="dialog" aria-controls="docara-reader-settings-dialog" aria-expanded="false" aria-label="<?= $view->copy['reader.open'] ?>">
                    <sf-icon icon="tune" aria-hidden="true"></sf-icon>
                </button>
            </div>
        </div>
<?php if ($view->preset === 'docs' && $view->regions['sidebar'] !== '') { ?>
        <dialog id="docara-mobile-navigation" data-docara-sheet data-docara-transient-dialog class="docara-mobile-sheet docara-mobile-navigation bg-surface-0 p-0 color-on-surface" aria-labelledby="docara-mobile-navigation-title">
            <div class="docara-mobile-sheet-header flex items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant">
                <h2 id="docara-mobile-navigation-title" class="m-0 weight-7"><?= $view->copy['navigation.title'] ?></h2>
                <button type="button" data-docara-sheet-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['navigation.close'] ?>">
                    <sf-icon icon="close" aria-hidden="true"></sf-icon>
                </button>
            </div>
            <div class="docara-mobile-sheet-content p-2"><?= $view->regions['sidebar_mobile'] ?></div>
        </dialog>
<?php } ?>
    </header>
<?php if ($view->preset === 'landing') { ?>
    <main id="docara-main" tabindex="-1" class="docara-landing p-4" data-docara-region="main">
        <article class="docara-content docara-prose flex flex-col gap-2" data-width="<?= $view->maxWidth ?>"><?= $view->regions['main'] ?></article>
    </main>
<?php } else { ?>
    <div class="docara-docs-layout gap-0" data-sidebar="<?= $view->regions['sidebar'] === '' ? 'false' : 'true' ?>" data-outline="<?= $view->regions['outline'] === '' ? 'false' : 'true' ?>">
<?php if ($view->regions['sidebar'] !== '') { ?>
        <aside class="docara-sidebar p-2" data-docara-region="sidebar"><?= $view->regions['sidebar'] ?></aside>
<?php } ?>
        <div class="docara-reading-column flex flex-col gap-2 p-3">
<?php if ($view->breadcrumbs !== []) { ?>
            <nav data-docara-breadcrumbs data-max-items="<?= count($view->breadcrumbs) ?>" class="sf-breadcrumbs flex" aria-label="<?= $view->copy['navigation.breadcrumbs'] ?>">
<?php foreach ($view->breadcrumbs as $index => $breadcrumb) { ?>
<?php if ($index > 0) { ?>
                <span class="sf-breadcrumbs-item sf-breadcrumbs-item--default" data-sf-breadcrumb-separator="true" aria-hidden="true"><span class="sf-breadcrumbs-item-container flex items-cross-center"><sf-icon icon="chevron_right" aria-hidden="true"></sf-icon></span></span>
<?php } ?>
<?php if ($breadcrumb['url'] !== null && ! $breadcrumb['current']) { ?>
                <a class="sf-breadcrumbs-item sf-breadcrumbs-item--link flex items-cross-center decoration-none" href="<?= $breadcrumb['url'] ?>"><span class="sf-breadcrumbs-item-container flex items-cross-center"><?= $breadcrumb['title'] ?></span></a>
<?php } else { ?>
                <span class="sf-breadcrumbs-item sf-breadcrumbs-item--default"<?php if ($breadcrumb['current']) { ?> aria-current="page"<?php } ?>><span class="sf-breadcrumbs-item-container flex items-cross-center"><?= $breadcrumb['title'] ?></span></span>
<?php } ?>
<?php } ?>
            </nav>
<?php } ?>
<?php if ($view->regions['outline'] !== '') { ?>
            <div data-docara-outline-mobile class="docara-outline-mobile">
                <button type="button" data-docara-sheet-trigger aria-haspopup="dialog" aria-controls="docara-outline-dialog" aria-expanded="false" class="docara-outline-trigger sf-button sf-button--on-surface sf-button--outline sf-button--size-1 flex items-center gap-1 radius-default">
                    <sf-icon icon="toc" aria-hidden="true"></sf-icon>
                    <span class="sf-button-text-container"><?= $view->copy['navigation.outline'] ?></span>
                </button>
                <dialog id="docara-outline-dialog" data-docara-sheet data-docara-transient-dialog class="docara-mobile-sheet docara-outline-dialog bg-surface-0 p-0 color-on-surface" aria-labelledby="docara-outline-title">
                    <div class="docara-mobile-sheet-header flex items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant">
                        <h2 id="docara-outline-title" class="m-0 weight-7"><?= $view->copy['navigation.outline'] ?></h2>
                        <button type="button" data-docara-sheet-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['navigation.outline_close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button>
                    </div>
                    <div class="docara-mobile-sheet-content p-2"><?= $view->regions['outline_mobile'] ?></div>
                </dialog>
            </div>
<?php } ?>
            <main id="docara-main" tabindex="-1" class="docara-content" data-width="<?= $view->maxWidth ?>" data-docara-region="main">
                <article class="docara-prose"><?= $view->regions['main'] ?></article>
<?php if ($view->previous !== null || $view->next !== null) { ?>
                <nav data-docara-previous-next class="docara-previous-next flex gap-2" aria-label="<?= $view->copy['navigation.previous_next'] ?>">
<?php if ($view->previous !== null) { ?>
                    <a class="docara-document-link docara-document-link--previous flex flex-1 items-cross-center gap-1 bg-surface-container border border-outline-variant radius-2 p-2 color-on-surface decoration-none" rel="prev" href="<?= $view->previous['url'] ?>"><sf-icon icon="arrow_back" aria-hidden="true"></sf-icon><span class="flex flex-col"><span class="color-on-surface-variant"><?= $view->copy['navigation.previous'] ?></span><span class="weight-6"><?= $view->previous['title'] ?></span></span></a>
<?php } ?>
<?php if ($view->next !== null) { ?>
                    <a class="docara-document-link docara-document-link--next flex flex-1 items-cross-center content-main-between gap-1 bg-surface-container border border-outline-variant radius-2 p-2 color-on-surface decoration-none" rel="next" href="<?= $view->next['url'] ?>"><span class="flex flex-col"><span class="color-on-surface-variant"><?= $view->copy['navigation.next'] ?></span><span class="weight-6"><?= $view->next['title'] ?></span></span><sf-icon icon="arrow_forward" aria-hidden="true"></sf-icon></a>
<?php } ?>
                </nav>
<?php } ?>
            </main>
        </div>
<?php if ($view->regions['outline'] !== '') { ?>
        <aside class="docara-outline-rail p-2" data-docara-region="outline"><?= $view->regions['outline'] ?></aside>
<?php } ?>
    </div>
<?php } ?>
<?php if ($view->regions['footer'] !== '') { ?>
    <footer data-docara-region="footer"><?= $view->regions['footer'] ?></footer>
<?php } ?>
<?php if ($view->searchEnabled) { ?>
    <dialog id="docara-search-dialog" data-docara-search-dialog data-docara-transient-dialog data-docara-search-index="<?= $view->searchIndexUrl ?>" class="docara-search-dialog bg-surface-0 border border-outline-variant radius-3 p-0 color-on-surface" aria-labelledby="docara-search-title">
        <div class="flex flex-col gap-2 p-3">
            <div class="flex items-center content-main-between gap-2"><h2 id="docara-search-title" class="m-0 weight-7"><?= $view->copy['search.title'] ?></h2><button type="button" data-docara-search-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['search.close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div>
            <label class="docara-search-input sf-input sf-input--size-1 sf-input--bordered flex flex-col"><span class="sf-input-label flex"><span class="sf-input-text"><?= $view->copy['search.query'] ?></span></span><span class="sf-input-field items-cross-center transition flex"><span class="sf-input-left flex"><sf-icon icon="search" aria-hidden="true"></sf-icon></span><input data-docara-search-input class="sf-input-text-container flex-1" type="search" autocomplete="off" spellcheck="false" placeholder="<?= $view->copy['search.placeholder'] ?>" aria-controls="docara-search-results" aria-describedby="docara-search-status"></span></label>
            <p id="docara-search-status" data-docara-search-status data-state="idle" class="docara-search-status color-on-surface-variant m-0" aria-live="polite"><?= $view->copy['search.idle'] ?></p>
            <ul id="docara-search-results" data-docara-search-results class="docara-search-results flex flex-col gap-1 m-0 p-0"></ul>
        </div>
    </dialog>
<?php } ?>
    <dialog id="docara-reader-settings-dialog" data-docara-reader-settings-dialog data-docara-transient-dialog data-configured-theme="<?= $view->configuredTheme ?>" class="docara-reader-settings-dialog bg-surface-0 border border-outline-variant radius-3 p-0 color-on-surface" aria-labelledby="docara-reader-settings-title">
        <form method="dialog" class="flex flex-col gap-2 p-3">
            <div class="flex items-center content-main-between gap-2"><h2 id="docara-reader-settings-title" class="m-0 weight-7"><?= $view->copy['reader.title'] ?></h2><button value="close" data-docara-reader-settings-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['reader.close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div>
            <fieldset class="docara-reader-settings-group flex flex-col gap-1 m-0 p-0 border-none" aria-describedby="docara-reader-settings-help">
                <legend class="weight-6 p-0"><?= $view->copy['reader.appearance'] ?></legend>
                <p id="docara-reader-settings-help" class="color-on-surface-variant m-0"><?= $view->copy['reader.help'] ?></p>
<?php foreach ($view->themeOptions as $option) { ?>
                <label class="sf-radio-button sf-radio-button--size-1 flex items-cross-start gap-1 p-1 radius-1 cursor-pointer transition"><span class="sf-radio-button-box transition flex items-cross-center content-main-center"><input data-docara-theme-option name="docara-reader-theme" type="radio" value="<?= $option['value'] ?>"<?php if ($option['checked']) { ?> checked<?php } ?>><span class="sf-radio-button-mark"></span></span><span class="sf-radio-button-container flex flex-col"><span class="sf-radio-button-top flex"><span class="sf-radio-button-text"><?= $option['title'] ?></span></span><span class="sf-radio-button-description"><?= $option['description'] ?></span></span></label>
<?php } ?>
            </fieldset>
            <div class="flex content-main-start"><button type="button" hidden data-docara-reader-settings-reset class="sf-button sf-button--link sf-button--on-surface sf-button--size-1 radius-default"><span class="sf-button-text-container"><?= $view->copy['reader.reset'] ?></span></button></div>
            <p id="docara-reader-settings-status" data-docara-reader-settings-status class="sr-only" aria-live="polite"></p>
        </form>
    </dialog>
    <script type="application/json" id="docara-runtime-copy"><?= $view->runtimeCopyJson ?></script>
    <script src="<?= $view->shellRuntimeUrl ?>" data-docara-shell-controller></script>
</body>
</html>
