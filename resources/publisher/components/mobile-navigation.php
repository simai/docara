<?php if ($view->mobileNavigationEnabled) { ?>
<sf-modal id="docara-mobile-navigation" modal-id="docara-mobile-navigation" data-docara-sheet data-docara-transient-dialog position="<?= $view->direction === 'rtl' ? 'right' : 'left' ?>" overlay="true" overlay-preset="default" show-header="false" show-close="false" show-footer="false" close-on-esc="true" close-on-overlay="true" preserve-scroll-gap="true" width="min(90vw, var(--sf-g5))" height="100dvh" panel-class="w-full max-w-full h-full" surface-class="h-full max-h-full bg-surface-0 radius-0" surface-padding="0" body-class="min-h-0 h-full" content-class="min-h-0 h-full" aria-labelledby="docara-mobile-navigation-title"><div slot="content" class="flex h-full min-w-0 flex-col color-on-surface">
    <div class="flex items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant"><h2 id="docara-mobile-navigation-title" class="m-0 weight-7"><?= $view->copy['navigation.mobile_title'] ?></h2><button type="button" data-docara-sheet-close data-sf-modal-close="docara-mobile-navigation" class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" aria-label="<?= $view->copy['navigation.close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div>
    <div class="p-2 flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
<?php if ($view->primaryNavigationEnabled) { ?>
        <section data-docara-primary-navigation class="flex flex-col gap-1"><h3 class="m-0 weight-7"><?= $view->copy['navigation.primary'] ?></h3><?= $view->regions['header_navigation_mobile'] ?></section>
<?php } ?>
<?php if ($view->documentationNavigationEnabled) { ?>
        <section class="flex flex-col gap-1"><h3 class="m-0 weight-7"><?= $view->copy['navigation.sections'] ?></h3><?= $view->regions['sidebar_mobile'] ?></section>
<?php } ?>
    </div>
</div></sf-modal>
<?php } else { ?>
<!-- docara:mobile-navigation disabled -->
<?php } ?>
