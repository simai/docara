<a class="docara-brand docara-brand--text docara-brand--size-<?= $view->size ?> inline-flex items-cross-center color-on-surface decoration-none" href="<?= $view->homeUrl ?>" data-docara-smart="docara.brand" data-docara-view="text">
    <span class="docara-brand-copy flex flex-col">
        <span class="docara-brand-title weight-bold"><?= $view->title ?></span>
<?php if ($view->label !== null) { ?>
        <span class="docara-brand-label label-small weight-medium color-on-surface-variant"><?= $view->label ?></span>
<?php } ?>
    </span>
</a>
