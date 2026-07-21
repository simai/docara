<?php if ($view->searchEnabled) { ?>
<script defer src="<?= $view->searchRuntimeUrl ?>" data-docara-search-runtime></script>
<?php } else { ?>
<!-- docara:search disabled -->
<?php } ?>
