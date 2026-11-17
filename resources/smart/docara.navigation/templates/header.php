<?php if ($view->hasItems) { ?>
<nav class="docara-navigation docara-header-navigation" aria-label="<?= $view->label ?>" data-docara-smart="docara.navigation" data-docara-view="header" data-docara-maximum-depth="<?= $view->maximumDepth ?>">
    <ul class="sf-menu sf-menu--horizontal sf-menu--navigation docara-header-navigation-list flex items-cross-center"><?= $view->itemsHtml ?></ul>
</nav>
<?php } else { ?>
<!-- docara:header-navigation disabled -->
<?php } ?>
