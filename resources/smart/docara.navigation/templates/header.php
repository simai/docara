<?php if ($view->hasItems) { ?>
<sf-menu class="docara-navigation docara-header-navigation" orientation="horizontal" appearance="navigation" overflow="more" collapse="none" data-docara-smart="docara.navigation" data-docara-view="header" data-docara-maximum-depth="<?= $view->maximumDepth ?>">
    <nav aria-label="<?= $view->label ?>"><ul class="sf-menu sf-menu--horizontal sf-menu--navigation docara-header-navigation-list flex items-cross-center"><?= $view->itemsHtml ?></ul></nav>
</sf-menu>
<?php } else { ?>
<!-- docara:header-navigation disabled -->
<?php } ?>
