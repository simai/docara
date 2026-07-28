<a class="docara-brand docara-brand--compact docara-brand--size-<?= $view->size ?> flex min-w-0 max-w-full items-center gap-1 color-on-surface decoration-none" href="<?= $view->homeUrl ?>" data-docara-smart="docara.brand" data-docara-view="compact">
<?php if ($view->logo !== null) { ?>
    <span class="docara-brand-mark grid items-center flex-none <?= $view->markSizeClasses() ?>"><img src="<?= $view->logo ?>"<?php if ($view->logoDark !== null) { ?> class="docara-brand-logo docara-brand-logo--light block max-w-full max-h-full object-contain"<?php } else { ?> class="docara-brand-logo block max-w-full max-h-full object-contain"<?php } ?> alt=""><?php if ($view->logoDark !== null) { ?><img src="<?= $view->logoDark ?>" class="docara-brand-logo docara-brand-logo--dark block max-w-full max-h-full object-contain" alt=""><?php } ?></span>
<?php } ?>
    <span class="docara-brand-title weight-7<?php if ($view->titleSizeClass() !== '') { ?> <?= $view->titleSizeClass() ?><?php } ?>"><?= $view->title ?></span>
</a>
