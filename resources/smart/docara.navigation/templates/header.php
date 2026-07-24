<?php if ($view->hasItems) { ?>
<nav class="docara-navigation docara-header-navigation" aria-label="<?= $view->label ?>" data-docara-smart="docara.navigation" data-docara-view="header" data-docara-maximum-depth="<?= $view->maximumDepth ?>">
    <ul class="sf-menu docara-header-navigation-list flex items-cross-center gap-1"><?= $view->itemsHtml ?></ul>
</nav>
<?php } else { ?>
<!-- docara:header-navigation disabled -->
<?php } ?>
