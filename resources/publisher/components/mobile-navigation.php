<?php if ($view->mobileNavigationEnabled) { ?>
<dialog id="docara-mobile-navigation" data-docara-sheet data-docara-transient-dialog class="docara-mobile-sheet bg-surface-0 p-0 color-on-surface" aria-labelledby="docara-mobile-navigation-title">
    <div class="docara-mobile-sheet-header flex items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant"><h2 id="docara-mobile-navigation-title" class="m-0 weight-7"><?= $view->copy['navigation.mobile_title'] ?></h2><button type="button" data-docara-sheet-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-2 radius-default" aria-label="<?= $view->copy['navigation.close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div>
    <div class="docara-mobile-sheet-content p-2 flex flex-col gap-3">
<?php if ($view->primaryNavigationEnabled) { ?>
        <section data-docara-primary-navigation class="flex flex-col gap-1"><h3 class="m-0 weight-7"><?= $view->copy['navigation.primary'] ?></h3><?= $view->regions['header_navigation_mobile'] ?></section>
<?php } ?>
<?php if ($view->documentationNavigationEnabled) { ?>
        <section class="flex flex-col gap-1"><h3 class="m-0 weight-7"><?= $view->copy['navigation.sections'] ?></h3><?= $view->regions['sidebar_mobile'] ?></section>
<?php } ?>
    </div>
</dialog>
<?php } else { ?>
<!-- docara:mobile-navigation disabled -->
<?php } ?>
