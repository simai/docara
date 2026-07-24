<?php $item = $view->item; ?>
<li class="sf-menu-item docara-header-navigation-item">
<?php if ($item->url !== null) { ?>
    <a class="sf-menu-element docara-header-navigation-link h-d0 flex items-cross-center radius-1<?= $view->weightClass ?>" data-docara-menu-link href="<?= $item->url ?>"<?php if ($item->active) { ?> aria-current="page"<?php } ?>><span class="sf-menu-element-text"><?= $item->title ?></span></a>
<?php } ?>
</li>
