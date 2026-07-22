<?php if ($view->searchEnabled) { ?>
<sf-modal
    id="docara-search-dialog"
    modal-id="docara-search-dialog"
    data-docara-search-dialog
    data-docara-transient-dialog
    data-docara-search-index="<?= $view->searchIndexUrl ?>"
    overlay="true"
    overlay-class="backdrop-blur-small"
    show-header="false"
    show-close="false"
    show-footer="false"
    close-on-esc="true"
    close-on-overlay="true"
    width="min(calc(100vw - 2rem), 46rem)"
    surface-padding="0"
    surface-class="docara-search-modal-surface"
    body-class="docara-search-modal-body"
    content-class="docara-search-modal-content"
><div slot="content" class="flex flex-col gap-1 min-w-0"><div class="docara-search-query bg-surface-0 border border-outline-variant radius-2 p-1 flex items-cross-center gap-1"><label class="sf-input sf-input--size-1 sf-input--bordered flex flex-col flex-1 min-w-0"><span class="sf-input-field items-cross-center transition flex"><span class="sf-input-left flex"><sf-icon icon="search" aria-hidden="true"></sf-icon></span><input data-docara-search-input class="sf-input-text-container flex-1 min-w-0" type="text" role="searchbox" inputmode="search" enterkeyhint="search" autocomplete="off" spellcheck="false" placeholder="<?= $view->copy['search.placeholder'] ?>" aria-label="<?= $view->copy['search.query'] ?>" aria-controls="docara-search-results" aria-describedby="docara-search-status"></span></label><button type="button" data-docara-search-close class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" aria-label="<?= $view->copy['search.close'] ?>"><sf-icon icon="close" aria-hidden="true"></sf-icon></button></div><section class="docara-search-results-surface bg-surface-0 border border-outline-variant radius-2 overflow-hidden color-on-surface" aria-labelledby="docara-search-title"><h2 id="docara-search-title" class="sr-only"><?= $view->copy['search.title'] ?></h2><p id="docara-search-status" data-docara-search-status data-state="idle" class="docara-search-status label-medium color-on-surface-variant m-0 p-x-2 p-y-1" aria-live="polite"><?= $view->copy['search.idle'] ?></p><ul id="docara-search-results" data-docara-search-results class="docara-search-results flex flex-col m-0 p-0"></ul><footer class="docara-search-help border-top-1 border-outline-variant p-1 flex content-main-center items-cross-center gap-2 color-on-surface-variant label-medium" aria-hidden="true"><span><kbd>↑↓</kbd> <?= $view->copy['search.navigate'] ?></span><span><kbd>Enter</kbd> <?= $view->copy['search.open_result'] ?></span><span><kbd>Esc</kbd> <?= $view->copy['search.dismiss'] ?></span></footer></section></div></sf-modal>
<?php } else { ?>
<!-- docara:search disabled -->
<?php } ?>
