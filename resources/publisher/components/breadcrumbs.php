<?php if ($view->breadcrumbs !== []) { ?>
<nav data-docara-breadcrumbs data-max-items="<?= count($view->breadcrumbs) ?>" class="sf-breadcrumbs flex" aria-label="<?= $view->copy['navigation.breadcrumbs'] ?>">
<?php foreach ($view->breadcrumbs as $index => $breadcrumb) { ?>
<?php if ($index > 0) { ?><span class="sf-breadcrumbs-item sf-breadcrumbs-item--default" data-sf-breadcrumb-separator="true" aria-hidden="true"><span class="sf-breadcrumbs-item-container flex items-cross-center"><sf-icon icon="chevron_right" aria-hidden="true"></sf-icon></span></span><?php } ?>
<?php if ($breadcrumb['url'] !== null && ! $breadcrumb['current']) { ?><a class="sf-breadcrumbs-item sf-breadcrumbs-item--link flex items-cross-center decoration-none" href="<?= $breadcrumb['url'] ?>"><span class="sf-breadcrumbs-item-container flex items-cross-center"><?= $breadcrumb['title'] ?></span></a><?php } else { ?><span class="sf-breadcrumbs-item sf-breadcrumbs-item--default"<?php if ($breadcrumb['current']) { ?> aria-current="page"<?php } ?>><span class="sf-breadcrumbs-item-container flex items-cross-center"><?= $breadcrumb['title'] ?></span></span><?php } ?>
<?php } ?>
</nav>
<?php } else { ?>
<!-- docara:breadcrumbs empty -->
<?php } ?>
