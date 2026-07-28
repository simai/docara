<?php $item = $view->item; ?>
<li class="sf-menu-item docara-header-navigation-item flex-none">
<?php if ($item->url !== null) { ?>
    <a class="sf-menu-element docara-header-navigation-link box-border h-d0 w-auto p-inline-1 flex items-cross-center color-on-surface decoration-none radius-1<?= $item->active ? ' bg-surface-container-active' : '' ?><?= $view->weightClass ?>" data-docara-menu-link href="<?= $item->url ?>"<?php if ($item->active) { ?> aria-current="page"<?php } ?>><span class="sf-menu-element-text"><?= $item->title ?></span></a>
<?php } ?>
</li>
