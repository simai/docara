<nav aria-label="<?= $view->label ?>" data-docara-smart="docara.toc" data-docara-view="compact" data-docara-outline class="docara-toc--compact sf-outline flex flex-col gap-1">
    <ul class="docara-outline-list sf-outline__list list-none flex flex-col gap-0 m-0 p-0">
<?php foreach ($view->items as $item) { ?>
        <li class="docara-outline-item relative <?= $item->indentationClass ?>" data-docara-outline-level="<?= $item->level ?>"><a class="docara-outline-link sf-outline__link flex items-center color-on-surface decoration-none radius-1 p-1/3 text-1/3" href="#<?= $item->id ?>"><?= $item->text ?></a></li>
<?php } ?>
    </ul>
</nav>
