<sf-modal
    modal-id="docara-reader-settings-dialog"
    data-docara-reader-settings-dialog
    data-docara-transient-dialog
    position="<?= $view->position ?>"
    overlay="true"
    overlay-preset="default"
    overlay-class="backdrop-blur-none"
    show-header="false"
    show-close="false"
    show-footer="false"
    close-on-esc="true"
    close-on-overlay="true"
    preserve-scroll-gap="true"
    width="min(100vw, var(--sf-g6))"
    height="100dvh"
    panel-class="docara-preferences-panel h-full"
    surface-class="docara-preferences-surface h-full bg-surface-0 radius-0"
    surface-padding="0"
    body-class="docara-preferences-body h-full"
    content-class="docara-preferences-content h-full"
><section slot="content" data-docara-smart="docara.preferences" data-docara-view="side-panel" class="flex h-full min-w-0 flex-col color-on-surface"><header class="sticky top-0 z-1 bg-surface-1 border-bottom-1 border-outline-variant p-2 flex items-cross-center content-main-between gap-2"><h2 id="docara-reader-settings-title" class="title-3 m-0"><?= $view->title ?></h2><div class="flex flex-none items-cross-center gap-1/3"><button type="button" data-docara-reader-settings-reset class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" aria-label="<?= $view->resetLabel ?>" title="<?= $view->resetLabel ?>"><sf-icon icon="restart_alt" aria-hidden="true"></sf-icon></button><button type="button" data-sf-modal-close="docara-reader-settings-dialog" data-docara-reader-settings-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" aria-label="<?= $view->closeLabel ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div></header><div class="docara-preferences-groups flex flex-1 min-h-0 flex-col gap-2 overflow-y-auto p-2 bg-surface-0">
<?php foreach ($view->groups as $group) { ?><div class="flex flex-col" data-docara-preference-group="<?= $group['id'] ?>">
<?php foreach ($group['fields'] as $field) { ?><div class="docara-preferences-field" data-docara-preference-field="<?= $field['id'] ?>"><span id="docara-preference-label-<?= $field['id'] ?>" class="docara-preferences-label"><?= $field['title'] ?></span><div class="docara-preferences-options sf-button-group" role="radiogroup" aria-labelledby="docara-preference-label-<?= $field['id'] ?>" data-docara-preference-layout="segmented"><?php foreach ($field['options'] as $option) { ?><label class="sf-button sf-button--size-1 <?= $option['selected'] ? 'sf-button--default sf-button--primary active' : 'sf-button--outline sf-button--on-surface' ?> cursor-pointer" data-docara-preference-choice><input data-docara-preference-option name="docara-preference-<?= $field['id'] ?>" type="radio" value="<?= $option['value'] ?>" data-preference-id="<?= $field['id'] ?>"<?php if ($option['selected']) { ?> checked<?php } ?>><span class="sf-button-text-container"><?= $option['title'] ?></span></label><?php } ?></div></div><?php } ?></div><?php } ?>
</div><p id="docara-reader-settings-status" data-docara-reader-settings-status class="sr-only" aria-live="polite"></p></section></sf-modal>
