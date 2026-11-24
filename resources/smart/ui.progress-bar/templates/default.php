<sf-progress-bar
    value="<?= $view->value ?>"
<?php if (($view->label ?? '') !== '') { ?>
    label="<?= $view->label ?>"
<?php } ?>
<?php if (($view->valueText ?? '') !== '') { ?>
    value-text="<?= $view->valueText ?>"
<?php } ?>
    size="<?= $view->size ?>"
    text-position="<?= $view->textPosition ?>"
<?php if (($view->status ?? '') !== '') { ?>
    status="<?= $view->status ?>"
<?php } ?>
<?php if (($view->warningAt ?? '') !== '') { ?>
    warning-at="<?= $view->warningAt ?>"
<?php } ?>
<?php if (($view->errorAt ?? '') !== '') { ?>
    error-at="<?= $view->errorAt ?>"
<?php } ?>
<?php if (($view->indeterminate ?? false) === true) { ?>
    indeterminate
<?php } ?>
<?php if (($view->paused ?? false) === true) { ?>
    paused
<?php } ?>
<?php if (($view->meter ?? false) === true) { ?>
    meter
<?php } ?>
<?php if (($view->inline ?? false) === true) { ?>
    inline
<?php } ?>
<?php if (($view->ariaLabel ?? '') !== '') { ?>
    aria-label="<?= $view->ariaLabel ?>"
<?php } ?>
></sf-progress-bar>
