<?php if ($view->previous !== null || $view->next !== null) { ?>
<sf-pager data-docara-previous-next appearance="cards" label="<?= $view->label ?>" prev-label="<?= $view->previousLabel ?>" next-label="<?= $view->nextLabel ?>"<?php if ($view->previous !== null) { ?> prev-href="<?= $view->previous['url'] ?>" prev-title="<?= $view->previous['title'] ?>"<?php } ?><?php if ($view->next !== null) { ?> next-href="<?= $view->next['url'] ?>" next-title="<?= $view->next['title'] ?>"<?php } ?> style="display: contents;"><nav class="sf-pager sf-pager--cards sf-pager--size-1" aria-label="<?= $view->label ?>" data-sf-static>
<?php if ($view->previous !== null) { ?><a class="sf-pager-side sf-pager-side--prev" href="<?= $view->previous['url'] ?>" rel="prev"><span class="sf-pager-direction"><i aria-hidden="true" class="sf-icon sf-pager-icon">chevron_left</i><span><?= $view->previousLabel ?></span></span><span class="sf-pager-title"><?= $view->previous['title'] ?></span></a><?php } ?>
<?php if ($view->next !== null) { ?><a class="sf-pager-side sf-pager-side--next" href="<?= $view->next['url'] ?>" rel="next"><span class="sf-pager-direction"><span><?= $view->nextLabel ?></span><i aria-hidden="true" class="sf-icon sf-pager-icon">chevron_right</i></span><span class="sf-pager-title"><?= $view->next['title'] ?></span></a><?php } ?>
</nav></sf-pager>
<?php } else { ?>
<!-- docara:pager empty -->
<?php } ?>
