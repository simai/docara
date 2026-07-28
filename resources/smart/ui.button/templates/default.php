<sf-button
    data-larena-smart-runtime="<?= $view->runtimePair ?>"
    text="<?= $view->text ?>"
    size="<?= $view->size ?>"
    type="<?= $view->type ?>"
    scheme="<?= $view->scheme ?>"
    native-type="<?= $view->nativeType ?>"
    aria-label="<?= $view->ariaLabel ?>"
<?php if ($view->radius !== null) { ?>
    radius="<?= $view->radius ?>"
<?php } ?>
<?php if ($view->loading) { ?>
    loading
<?php } ?>
<?php if ($view->disabled) { ?>
    disabled
<?php } ?>
></sf-button>
