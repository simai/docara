<nav aria-label="<?= $view->label ?>" data-docara-smart="docara.toc" data-docara-view="compact" data-docara-outline class="docara-toc--compact flex flex-col gap-1">
    <ul class="docara-outline-list flex flex-col gap-1 m-0 p-0">
<?php foreach ($view->items as $item) { ?>
        <li class="docara-outline-item <?= $item->indentationClass ?>" data-docara-outline-level="<?= $item->level ?>"><a class="docara-outline-link flex items-center color-on-surface decoration-none radius-1 px-1" href="#<?= $item->id ?>"><?= $item->text ?></a></li>
<?php } ?>
    </ul>
</nav>
