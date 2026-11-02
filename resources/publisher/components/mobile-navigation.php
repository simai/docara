<?php if ($view->mobileNavigationEnabled) { ?>
<div class="docara-site-header-sheet flex flex-col gap-3" data-docara-sheet-content>
<?php if ($view->primaryNavigationEnabled) { ?>
    <section data-docara-primary-navigation class="flex flex-col gap-1"><h3 class="m-0 weight-7"><?= $view->copy['navigation.primary'] ?></h3><?= $view->regions['header_navigation_mobile'] ?></section>
<?php } ?>
<?php if ($view->documentationNavigationEnabled) { ?>
    <div data-docara-sheet-lazy-content></div>
    <template data-docara-sheet-template data-docara-sheet-source="sidebar"><section class="flex flex-col gap-1"><h3 class="m-0 weight-7"><?= $view->copy['navigation.sections'] ?></h3></section></template>
<?php } ?>
</div>
<?php } else { ?>
<!-- docara:mobile-navigation disabled -->
<?php } ?>
