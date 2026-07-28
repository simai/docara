<?php if ($view->breadcrumbs !== []) { ?>
<nav data-docara-breadcrumbs data-max-items="3" data-docara-breadcrumbs-ellipsis-label="<?= $view->copy['navigation.breadcrumbs_expand'] ?>" class="sf-breadcrumbs flex flex-nowrap items-cross-center min-w-0 overflow-x-auto overflow-y-hidden" aria-label="<?= $view->copy['navigation.breadcrumbs'] ?>">
<?php foreach ($view->breadcrumbs as $index => $breadcrumb) { ?>
<?php if ($index > 0) { ?><span class="sf-breadcrumbs-item sf-breadcrumbs-item--default flex-none" data-sf-breadcrumb-separator="true" aria-hidden="true"><span class="sf-breadcrumbs-item-container flex items-cross-center"><sf-icon icon="chevron_right" aria-hidden="true"></sf-icon></span></span><?php } ?>
<?php if ($breadcrumb['url'] !== null && ! $breadcrumb['current']) { ?><a class="sf-breadcrumbs-item sf-breadcrumbs-item--link flex flex-none items-cross-center decoration-none" href="<?= $breadcrumb['url'] ?>"><span class="sf-breadcrumbs-item-container flex items-cross-center"><?= $breadcrumb['title'] ?></span></a><?php } else { ?><span class="sf-breadcrumbs-item sf-breadcrumbs-item--default flex-none"<?php if ($breadcrumb['current']) { ?> aria-current="page"<?php } ?>><span class="sf-breadcrumbs-item-container flex items-cross-center"><?= $breadcrumb['title'] ?></span></span><?php } ?>
<?php } ?>
</nav>
<?php } else { ?>
<!-- docara:breadcrumbs empty -->
<?php } ?>
