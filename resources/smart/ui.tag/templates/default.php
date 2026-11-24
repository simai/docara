<sf-tag
<?php if (($view->text ?? '') !== '') { ?>
    text="<?= $view->text ?>"
<?php } ?>
    kind="<?= $view->kind ?>"
<?php if (($view->status ?? '') !== '') { ?>
    status="<?= $view->status ?>"
<?php } ?>
    appearance="<?= $view->appearance ?>"
    size="<?= $view->size ?>"
    height="<?= $view->height ?>"
<?php if (($view->inline ?? false) === true) { ?>
    inline
<?php } ?>
<?php if (($view->count ?? '') !== '') { ?>
    count="<?= $view->count ?>"
<?php } ?>
<?php if (($view->disabled ?? false) === true) { ?>
    disabled
<?php } ?>
<?php if (($view->ariaLabel ?? '') !== '') { ?>
    aria-label="<?= $view->ariaLabel ?>"
<?php } ?>
></sf-tag>
