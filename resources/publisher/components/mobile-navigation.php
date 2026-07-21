<?php if ($view->preset === 'docs' && $view->regions['sidebar'] !== '') { ?>
<dialog id="docara-mobile-navigation" data-docara-sheet data-docara-transient-dialog class="docara-mobile-sheet bg-surface-0 p-0 color-on-surface" aria-labelledby="docara-mobile-navigation-title">
    <div class="docara-mobile-sheet-header flex items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant"><h2 id="docara-mobile-navigation-title" class="m-0 weight-7"><?= $view->copy['navigation.title'] ?></h2><button type="button" data-docara-sheet-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['navigation.close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div>
    <div class="docara-mobile-sheet-content p-2"><?= $view->regions['sidebar_mobile'] ?></div>
</dialog>
<?php } else { ?>
<!-- docara:mobile-navigation disabled -->
<?php } ?>
