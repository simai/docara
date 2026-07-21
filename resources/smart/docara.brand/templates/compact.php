<a class="docara-brand docara-brand--compact flex items-center gap-1 color-on-surface decoration-none" href="<?= $view->homeUrl ?>" data-docara-smart="docara.brand" data-docara-view="compact">
<?php if ($view->logo !== null) { ?>
    <span class="docara-brand-mark"><img src="<?= $view->logo ?>"<?php if ($view->logoDark !== null) { ?> class="docara-brand-logo docara-brand-logo--light"<?php } else { ?> class="docara-brand-logo"<?php } ?> alt=""><?php if ($view->logoDark !== null) { ?><img src="<?= $view->logoDark ?>" class="docara-brand-logo docara-brand-logo--dark" alt=""><?php } ?></span>
<?php } ?>
    <span class="weight-7"><?= $view->title ?></span>
</a>
