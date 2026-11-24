<sf-button-copy
<?php if (($view->value ?? '') !== '') { ?>
    value="<?= $view->value ?>"
<?php } ?>
<?php if (($view->target ?? '') !== '') { ?>
    target="<?= $view->target ?>"
<?php } ?>
<?php if (($view->text ?? '') !== '') { ?>
    text="<?= $view->text ?>"
<?php } ?>
<?php if (($view->label ?? '') !== '') { ?>
    label="<?= $view->label ?>"
<?php } ?>
    size="<?= $view->size ?>"
    appearance="<?= $view->appearance ?>"
    icon="<?= $view->icon ?>"
    success-icon="<?= $view->successIcon ?>"
<?php if (($view->successText ?? '') !== '') { ?>
    success-text="<?= $view->successText ?>"
<?php } ?>
<?php if (($view->errorText ?? '') !== '') { ?>
    error-text="<?= $view->errorText ?>"
<?php } ?>
    duration="<?= $view->duration ?>"
></sf-button-copy>
