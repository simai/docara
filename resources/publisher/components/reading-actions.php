<div class="docara-reading-actions flex flex-none items-cross-center gap-1/3" data-docara-reading-actions>
    <button type="button" data-docara-focus-toggle aria-pressed="false" aria-label="<?= $view->copy['reader.focus_mode'] ?>" title="<?= $view->copy['reader.focus_mode'] ?>" class="docara-focus-toggle sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default"><sf-icon icon="article" aria-hidden="true"></sf-icon></button>
<?php if ($view->regions['outline'] !== '') { ?>
    <button type="button" data-docara-outline-toggle aria-pressed="false" aria-controls="docara-outline" aria-label="<?= $view->copy['reader.outline_hide'] ?>" title="<?= $view->copy['reader.outline_hide'] ?>" class="docara-outline-toggle sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default"><sf-icon icon="dock_to_left" aria-hidden="true"></sf-icon></button>
<?php } ?>
    <button type="button" data-docara-focus-exit aria-label="<?= $view->copy['reader.focus_mode_exit'] ?>" title="<?= $view->copy['reader.focus_mode_exit'] ?>" class="docara-focus-exit sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--tonal sf-icon-button--size-1 radius-rounded"><sf-icon icon="close" aria-hidden="true"></sf-icon></button>
</div>
