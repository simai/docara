<div data-docara-demonstrator-index class="flex flex-col gap-3">
    <header class="flex flex-col gap-1">
        <h1><?= $view->title ?></h1>
        <p class="color-on-surface-variant m-0"><?= $view->intro ?></p>
    </header>
    <div class="docara-example-grid grid gap-2">
<?php foreach ($view->items as $item) { ?>
        <article class="surface-0 border border-outline-variant radius-2 p-2 flex flex-col gap-1">
            <span class="color-primary weight-7"><?= $item->category ?></span>
            <h2 class="m-0"><?= $item->title ?></h2>
            <p class="color-on-surface-variant m-0"><?= $item->description ?></p>
            <a class="sf-button sf-button--outline sf-button--primary sf-button--size-1 radius-default decoration-none inline-flex items-center content-main-center" href="<?= $item->url ?>"><span class="sf-button-text-container">Открыть пример</span></a>
        </article>
<?php } ?>
    </div>
</div>
