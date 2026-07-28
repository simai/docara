<a class="docara-brand docara-brand--size-<?= $view->size ?> flex min-w-0 max-w-full items-center gap-1 color-on-surface decoration-none" href="<?= $view->homeUrl ?>" data-docara-smart="docara.brand" data-docara-view="default">
<?php if ($view->logo !== null) { ?>
    <span class="docara-brand-mark grid items-center flex-none <?= $view->markSizeClasses() ?>">
        <img class="docara-brand-logo docara-brand-logo--light block max-w-full max-h-full object-contain" src="<?= $view->logo ?>" alt="">
<?php if ($view->logoDark !== null) { ?>
        <img class="docara-brand-logo docara-brand-logo--dark block max-w-full max-h-full object-contain" src="<?= $view->logoDark ?>" alt="">
<?php } ?>
    </span>
<?php } ?>
    <span class="docara-brand-copy flex min-w-0 max-w-f8 flex-col">
        <span class="docara-brand-title weight-7<?php if ($view->titleSizeClass() !== '') { ?> <?= $view->titleSizeClass() ?><?php } ?>"><?= $view->title ?></span>
<?php if ($view->label !== null) { ?>
        <span class="docara-brand-label label-small weight-5 color-on-surface-variant"><?= $view->label ?></span>
<?php } ?>
    </span>
</a>
