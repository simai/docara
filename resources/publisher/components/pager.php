<?php if ($view->previous !== null || $view->next !== null) { ?>
<nav data-docara-previous-next class="docara-previous-next flex gap-2" aria-label="<?= $view->copy['navigation.previous_next'] ?>">
<?php if ($view->previous !== null) { ?><a class="docara-document-link flex flex-1 items-cross-center gap-1 bg-surface-container border border-outline-variant radius-2 p-2 color-on-surface decoration-none" rel="prev" href="<?= $view->previous['url'] ?>"><sf-icon icon="arrow_back" aria-hidden="true"></sf-icon><span class="flex flex-col"><span class="color-on-surface-variant"><?= $view->copy['navigation.previous'] ?></span><span class="weight-6"><?= $view->previous['title'] ?></span></span></a><?php } ?>
<?php if ($view->next !== null) { ?><a class="docara-document-link docara-document-link--next flex flex-1 items-cross-center content-main-between gap-1 bg-surface-container border border-outline-variant radius-2 p-2 color-on-surface decoration-none" rel="next" href="<?= $view->next['url'] ?>"><span class="flex flex-col"><span class="color-on-surface-variant"><?= $view->copy['navigation.next'] ?></span><span class="weight-6"><?= $view->next['title'] ?></span></span><sf-icon icon="arrow_forward" aria-hidden="true"></sf-icon></a><?php } ?>
</nav>
<?php } else { ?>
<!-- docara:pager empty -->
<?php } ?>
