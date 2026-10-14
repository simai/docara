<?php if ($view->previous !== null || $view->next !== null) { ?>
<nav data-docara-previous-next class="docara-previous-next flex gap-2" aria-label="<?= $view->label ?>">
<?php if ($view->previous !== null) { ?><a class="docara-document-link docara-document-link--previous flex flex-1 items-cross-center gap-1 radius-1 p-1 color-on-surface decoration-none" rel="prev" href="<?= $view->previous['url'] ?>"><sf-icon icon="arrow_back" aria-hidden="true"></sf-icon><span class="docara-document-link__text flex flex-col min-w-0"><span class="color-on-surface-variant"><?= $view->previousLabel ?></span><span class="weight-6"><?= $view->previous['title'] ?></span></span></a><?php } ?>
<?php if ($view->next !== null) { ?><a class="docara-document-link docara-document-link--next flex flex-1 items-cross-center gap-1 radius-1 p-1 color-on-surface decoration-none" rel="next" href="<?= $view->next['url'] ?>"><span class="docara-document-link__text flex flex-col min-w-0"><span class="color-on-surface-variant"><?= $view->nextLabel ?></span><span class="weight-6"><?= $view->next['title'] ?></span></span><sf-icon icon="arrow_forward" aria-hidden="true"></sf-icon></a><?php } ?>
</nav>
<?php } else { ?>
<!-- docara:pager empty -->
<?php } ?>
