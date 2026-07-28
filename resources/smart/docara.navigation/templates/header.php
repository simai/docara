<?php if ($view->hasItems) { ?>
<nav class="docara-navigation docara-header-navigation hidden lg:flex min-w-0 flex-1" aria-label="<?= $view->label ?>" data-docara-smart="docara.navigation" data-docara-view="header" data-docara-maximum-depth="<?= $view->maximumDepth ?>">
    <ul class="sf-menu docara-header-navigation-list flex items-cross-center content-main-center whitespace-nowrap gap-1"><?= $view->itemsHtml ?></ul>
</nav>
<?php } else { ?>
<!-- docara:header-navigation disabled -->
<?php } ?>
