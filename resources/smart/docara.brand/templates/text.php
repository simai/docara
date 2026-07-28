<a class="docara-brand docara-brand--text docara-brand--size-<?= $view->size ?> inline-flex min-w-0 max-w-full items-center color-on-surface decoration-none" href="<?= $view->homeUrl ?>" data-docara-smart="docara.brand" data-docara-view="text">
    <span class="docara-brand-copy flex min-w-0 max-w-f8 flex-col">
        <span class="docara-brand-title weight-7<?php if ($view->titleSizeClass() !== '') { ?> <?= $view->titleSizeClass() ?><?php } ?>"><?= $view->title ?></span>
<?php if ($view->label !== null) { ?>
        <span class="docara-brand-label label-small weight-5 color-on-surface-variant"><?= $view->label ?></span>
<?php } ?>
    </span>
</a>
