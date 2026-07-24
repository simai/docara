<sf-modal
    id="docara-reader-settings-dialog"
    modal-id="docara-reader-settings-dialog"
    data-docara-reader-settings-dialog
    data-docara-transient-dialog
    position="<?= $view->position ?>"
    overlay="true"
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
><section slot="content" data-docara-smart="docara.preferences" data-docara-view="side-panel" class="flex h-full min-w-0 flex-col color-on-surface"><header class="sticky top-0 z-1 bg-surface-0 border-bottom-1 border-outline-variant p-2 flex items-center content-main-between gap-2"><h2 id="docara-reader-settings-title" class="title-3 m-0"><?= $view->title ?></h2><button type="button" data-sf-modal-close="docara-reader-settings-dialog" data-docara-reader-settings-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" aria-label="<?= $view->closeLabel ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></header><div class="docara-preferences-groups flex flex-1 min-h-0 flex-col gap-3 overflow-y-auto p-2">
<?php foreach ($view->groups as $group) { ?><section class="flex flex-col gap-1" data-docara-preference-group="<?= $group['id'] ?>"><div class="flex flex-col gap-1/4"><h3 class="title-1 m-0"><?= $group['title'] ?></h3><?php if ($group['description'] !== '') { ?><p class="m-0 color-on-surface-variant"><?= $group['description'] ?></p><?php } ?></div>
<?php foreach ($group['fields'] as $field) { ?><fieldset class="docara-preferences-field flex flex-col gap-1 m-0 p-0 border-none" data-docara-preference-field="<?= $field['id'] ?>">
<?php foreach ($field['options'] as $option) { ?><label class="sf-radio-button sf-radio-button--size-1 flex items-cross-start gap-1 p-1 radius-1 cursor-pointer transition"><span class="sf-radio-button-box transition flex items-cross-center content-main-center"><input data-docara-preference-option name="docara-preference-<?= $field['id'] ?>" type="radio" value="<?= $option['value'] ?>" data-preference-id="<?= $field['id'] ?>"<?php if ($option['value'] === $field['configured']) { ?> checked<?php } ?>><span class="sf-radio-button-mark"></span></span><span class="sf-radio-button-container flex flex-col"><span class="sf-radio-button-top flex"><span class="sf-radio-button-text"><?= $option['title'] ?></span></span><span class="sf-radio-button-description"><?= $option['description'] ?></span></span></label><?php } ?>
</fieldset><?php } ?></section><?php } ?>
</div><footer class="sticky bottom-0 bg-surface-0 border-top-1 border-outline-variant p-2 flex content-main-start"><button type="button" hidden data-docara-reader-settings-reset class="sf-button sf-button--link sf-button--on-surface sf-button--size-1 radius-default"><span class="sf-button-text-container"><?= $view->resetLabel ?></span></button></footer><p id="docara-reader-settings-status" data-docara-reader-settings-status class="sr-only" aria-live="polite"></p></section></sf-modal>
