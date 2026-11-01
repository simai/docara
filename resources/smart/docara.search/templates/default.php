<?php if ($view->enabled) { ?>
<sf-search data-docara-search-dialog data-docara-search-index="<?= $view->indexUrl ?>" for="docara-search-trigger" label="<?= $view->title ?>" placeholder="<?= $view->placeholder ?>" recent-key="docara" style="display: contents;"></sf-search>
<?php } else { ?>
<!-- docara:search disabled -->
<?php } ?>
