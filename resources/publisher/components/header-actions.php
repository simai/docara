<div class="flex flex-none items-center gap-1">
<?php if ($view->preset === 'docs' && $view->regions['sidebar'] !== '') { ?>
    <button type="button" data-docara-sheet-trigger aria-haspopup="dialog" aria-controls="docara-mobile-navigation" aria-expanded="false" class="docara-mobile-navigation-trigger sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['navigation.open'] ?>"><sf-icon icon="menu" aria-hidden="true"></sf-icon></button>
<?php } ?>
<?php if ($view->searchEnabled) { ?>
    <button type="button" data-docara-search-trigger aria-haspopup="dialog" aria-controls="docara-search-dialog" aria-expanded="false" aria-label="<?= $view->copy['search.open'] ?>" class="docara-search-trigger sf-button sf-button--on-surface sf-button--outline sf-button--size-1 flex items-center gap-1 radius-default"><sf-icon icon="search" aria-hidden="true"></sf-icon><span class="docara-search-trigger-label sf-button-text-container"><?= $view->copy['search.label'] ?></span><kbd class="docara-search-shortcut label-small color-on-surface-variant border border-outline-variant radius-1 p-x-1/2" data-docara-search-shortcut>⌘K</kbd></button>
<?php } ?>
<?php if (count($view->languageOptions) > 1) { ?>
    <label class="sf-select sf-select--size-1"><span class="sr-only"><?= $view->copy['language.label'] ?></span><select data-docara-language-switcher aria-label="<?= $view->copy['language.label'] ?>">
<?php foreach ($view->languageOptions as $option) { ?>
        <option value="<?= $option['url'] ?>" lang="<?= $option['locale'] ?>"<?php if ($option['current']) { ?> selected<?php } ?>><?= $option['label'] ?></option>
<?php } ?>
    </select></label>
<?php } ?>
    <button class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" data-docara-reader-settings-trigger type="button" aria-haspopup="dialog" aria-controls="docara-reader-settings-dialog" aria-expanded="false" aria-label="<?= $view->copy['reader.open'] ?>"><sf-icon icon="tune" aria-hidden="true"></sf-icon></button>
</div>
