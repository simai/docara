<?php if ($view->regions['outline'] !== '') { ?>
<div data-docara-outline-mobile class="docara-outline-mobile">
    <button type="button" data-docara-sheet-trigger aria-haspopup="dialog" aria-controls="docara-outline-dialog" aria-expanded="false" class="docara-outline-trigger sf-button sf-button--on-surface sf-button--outline sf-button--size-1 flex items-center gap-1 radius-default"><sf-icon icon="toc" aria-hidden="true"></sf-icon><span class="sf-button-text-container"><?= $view->copy['navigation.outline'] ?></span></button>
    <dialog id="docara-outline-dialog" data-docara-sheet data-docara-transient-dialog class="docara-mobile-sheet docara-outline-dialog bg-surface-0 p-0 color-on-surface" aria-labelledby="docara-outline-title"><div class="docara-mobile-sheet-header flex items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant"><h2 id="docara-outline-title" class="m-0 weight-7"><?= $view->copy['navigation.outline'] ?></h2><button type="button" data-docara-sheet-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['navigation.outline_close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div><div class="docara-mobile-sheet-content p-2"><?= $view->regions['outline_mobile'] ?></div></dialog>
</div>
<?php } else { ?>
<!-- docara:mobile-toc empty -->
<?php } ?>
