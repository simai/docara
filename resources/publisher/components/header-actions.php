<div class="flex flex-none items-center gap-1">
<?php if ($view->mobileNavigationEnabled) { ?>
    <button type="button" data-docara-sheet-trigger aria-haspopup="dialog" aria-controls="docara-mobile-navigation" aria-expanded="false" class="docara-mobile-navigation-trigger<?= $view->primaryNavigationEnabled ? ' docara-mobile-navigation-trigger--primary' : '' ?> sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" aria-label="<?= $view->copy['navigation.open'] ?>"><sf-icon icon="menu" aria-hidden="true"></sf-icon></button>
<?php } ?>
<?php if ($view->searchEnabled) { ?>
    <sf-button
        data-docara-search-trigger
        aria-haspopup="dialog"
        aria-controls="docara-search-dialog"
        aria-expanded="false"
        aria-label="<?= $view->copy['search.open'] ?>"
        root-class="docara-search-trigger h-d0"
        size="1"
        type="outline"
        scheme="on-surface"
        text="<?= $view->copy['search.label'] ?>"
        icon-left="search"
    ><kbd slot="icon-right" class="docara-search-shortcut text-1 color-on-surface-variant m-inline-start-1/2" data-docara-search-shortcut>⌘K</kbd></sf-button>
<?php } ?>
<?php if (count($view->languageOptions) > 1) { ?>
    <details data-docara-language-menu class="docara-language-menu relative">
        <summary data-docara-language-trigger aria-label="<?= $view->copy['language.label'] ?>" aria-expanded="false" class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default cursor-pointer"><sf-icon icon="language" aria-hidden="true"></sf-icon></summary>
        <nav class="docara-language-menu__popup" aria-label="<?= $view->copy['language.label'] ?>">
            <ul class="list-none m-0 p-1 flex flex-col gap-1/3">
<?php foreach ($view->languageOptions as $option) { ?>
                <li><a data-docara-language-option href="<?= $option['url'] ?>" lang="<?= $option['locale'] ?>" hreflang="<?= $option['locale'] ?>"<?php if ($option['current']) { ?> aria-current="page"<?php } ?> class="docara-language-menu__option flex items-center content-main-between gap-2 radius-default p-inline-1 p-block-1/2 decoration-none color-on-surface"><span><?= $option['label'] ?></span><?php if ($option['current']) { ?><sf-icon icon="check" aria-hidden="true"></sf-icon><?php } ?></a></li>
<?php } ?>
            </ul>
        </nav>
    </details>
<?php } ?>
<?php if ($view->readerPreferencesEnabled) { ?>
    <button class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" data-docara-reader-settings-trigger type="button" aria-haspopup="dialog" aria-controls="docara-reader-settings-dialog" aria-expanded="false" aria-label="<?= $view->copy['reader.open'] ?>"><sf-icon icon="tune" aria-hidden="true"></sf-icon></button>
<?php } ?>
</div>
