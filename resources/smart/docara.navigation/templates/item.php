<?php $item = $view->item; ?>
<li class="sf-menu-item flex flex-col<?= $item->open ? ' open' : '' ?>" data-docara-navigation-key="<?= $item->key ?>" data-docara-navigation-depth="<?= $item->depth ?>"<?php if ($item->open) { ?> expanded aria-expanded="true"<?php } elseif ($item->hasChildren) { ?> aria-expanded="false"<?php } ?><?php if ($view->activeRole !== null) { ?> data-docara-active-role="<?= $view->activeRole ?>"<?php } ?>>
    <div role="presentation" tabindex="-1" class="sf-menu-element sf-menu-element--level-<?= $view->frameworkLevel ?> flex items-cross-center transition<?= $item->open ? ' open' : '' ?>">
<?php if ($item->url !== null) { ?>
        <a class="sf-menu-element-wrap docara-navigation-link flex flex-1 items-cross-center radius-1<?= $view->weightClass ?>" data-docara-menu-link href="<?= $item->url ?>"<?php if ($item->active) { ?> aria-current="page"<?php } ?>><span class="sf-menu-element-text"><?= $item->title ?></span></a>
<?php } else { ?>
        <span class="sf-menu-element-wrap docara-navigation-label flex flex-1 items-cross-center<?= $view->weightClass ?>"><span class="sf-menu-element-text"><?= $item->title ?></span></span>
<?php } ?>
<?php if ($item->hasChildren) { ?>
        <button type="button" data-docara-disclosure<?= $item->activeAncestor ? ' data-docara-contains-current="true"' : '' ?> aria-expanded="<?= $item->open ? 'true' : 'false' ?>" aria-label="<?= $item->open ? $view->collapseLabel : $view->expandLabel ?><?= $item->title ?><?= $item->activeAncestor ? $view->containsCurrentLabel : '' ?>" class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link radius-default sf-icon-button--size-1/3"><sf-icon icon="<?= $item->open ? 'expand_less' : 'expand_more' ?>" aria-hidden="true"></sf-icon></button>
<?php } ?>
    </div>
<?php if ($item->hasChildren) { ?>
    <ul class="sf-menu flex flex-col"><?= $view->childrenHtml ?></ul>
<?php } ?>
</li>
