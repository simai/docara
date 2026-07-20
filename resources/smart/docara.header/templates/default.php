<a class="docara-brand flex items-center gap-1 color-on-surface decoration-none" href="<?= $view->homeUrl ?>" data-docara-smart="docara.header">
<?php if ($view->logo !== null) { ?>
    <span class="docara-brand-mark">
        <img class="docara-brand-logo docara-brand-logo--light" src="<?= $view->logo ?>" alt="">
<?php if ($view->logoDark !== null) { ?>
        <img class="docara-brand-logo docara-brand-logo--dark" src="<?= $view->logoDark ?>" alt="">
<?php } ?>
    </span>
<?php } ?>
    <span class="docara-brand-copy flex flex-col">
        <span class="weight-7"><?= $view->title ?></span>
<?php if ($view->label !== null) { ?>
        <span class="docara-brand-label color-on-surface-variant"><?= $view->label ?></span>
<?php } ?>
    </span>
</a>
