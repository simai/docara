<nav aria-label="On this page" data-docara-smart="docara.outline">
    <ol class="flex flex-col gap-1 list-none p-0 m-0">
<?php foreach ($view->items as $item): ?>
        <li class="<?= $item->indentationClass ?>" data-docara-outline-level="<?= $item->level ?>">
            <a class="block p-1 radius-1" href="#<?= $item->id ?>"><?= $item->text ?></a>
        </li>
<?php endforeach; ?>
    </ol>
</nav>
