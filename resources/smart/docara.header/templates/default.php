<div class="flex items-center gap-2 surface-0 border-bottom p-2" data-docara-smart="docara.header">
    <a class="flex items-center gap-2 text-decoration-none" href="<?= $view->homeUrl ?>">
<?php if ($view->logo !== null): ?>
        <picture class="flex items-center">
<?php if ($view->logoDark !== null): ?>
            <source media="(prefers-color-scheme: dark)" srcset="<?= $view->logoDark ?>">
<?php endif; ?>
            <img src="<?= $view->logo ?>" alt="" loading="eager">
        </picture>
<?php endif; ?>
        <span class="font-weight-600"><?= $view->title ?></span>
<?php if ($view->label !== null): ?>
        <span class="text-muted"><?= $view->label ?></span>
<?php endif; ?>
    </a>
</div>
