<nav aria-label="На этой странице" data-docara-smart="docara.outline" data-docara-outline class="flex flex-col gap-1">
    <p class="m-0 weight-7">На этой странице</p>
    <ul class="docara-outline-list flex flex-col gap-1 m-0 p-0">
<?php foreach ($view->items as $item) { ?>
        <li class="docara-outline-item" data-docara-outline-level="<?= $item->level ?>">
            <a class="docara-outline-link flex items-cross-center radius-1 p-1 color-on-surface-variant decoration-none" href="#<?= $item->id ?>"><?= $item->text ?></a>
        </li>
<?php } ?>
    </ul>
</nav>
