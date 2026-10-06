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
<?php foreach ($view->groups as $group) { ?><div class="flex flex-col gap-1" data-docara-preference-group="<?= $group['id'] ?>">
<?php foreach ($group['fields'] as $field) { ?><fieldset class="docara-preferences-field flex flex-col gap-1/3 m-0 p-0 border-none" data-docara-preference-field="<?= $field['id'] ?>"><legend class="title-1 m-0 p-0"><?= $field['title'] ?></legend><?php if ($field['description'] !== '') { ?><p class="m-0 color-on-surface-variant"><?= $field['description'] ?></p><?php } ?>
<div class="docara-preferences-options flex <?= $field['layout'] === 'inline' ? 'flex-row flex-wrap gap-1/3' : 'flex-col gap-0' ?>" data-docara-preference-layout="<?= $field['layout'] ?>"><?php foreach ($field['options'] as $option) { ?><label class="sf-radio-button sf-radio-button--size-1 flex items-cross-start gap-1 p-inline-1 p-block-1/3 radius-1 cursor-pointer transition"><span class="sf-radio-button-box transition flex items-cross-center content-main-center"><input data-docara-preference-option name="docara-preference-<?= $field['id'] ?>" type="radio" value="<?= $option['value'] ?>" data-preference-id="<?= $field['id'] ?>"<?php if ($option['value'] === $field['configured']) { ?> checked<?php } ?>><span class="sf-radio-button-mark"></span></span><span class="sf-radio-button-container flex flex-col"><span class="sf-radio-button-top flex"><span class="sf-radio-button-text"><?= $option['title'] ?></span></span><?php if ($option['description'] !== '') { ?><span class="sf-radio-button-description"><?= $option['description'] ?></span><?php } ?></span></label><?php } ?></div>
</fieldset><?php } ?></div><?php } ?>
</div><p id="docara-reader-settings-status" data-docara-reader-settings-status class="sr-only" aria-live="polite"></p></section></sf-modal>
