<sf-reference-link
<?php if (($view->label ?? '') !== '') { ?>
    label="<?= $view->label ?>"
<?php } ?>
<?php if (($view->href ?? '') !== '') { ?>
    href="<?= $view->href ?>"
<?php } ?>
<?php if (($view->text ?? '') !== '') { ?>
    text="<?= $view->text ?>"
<?php } ?>
    appearance="<?= $view->appearance ?>"
<?php if (($view->icon ?? '') !== '') { ?>
    icon="<?= $view->icon ?>"
<?php } ?>
<?php if (($view->size ?? '') !== '') { ?>
    size="<?= $view->size ?>"
<?php } ?>
<?php if (($view->line ?? '') !== '') { ?>
    line="<?= $view->line ?>"
<?php } ?>
<?php if (($view->state ?? '') !== '') { ?>
    state="<?= $view->state ?>"
<?php } ?>
    target="<?= $view->target ?>"
<?php if (($view->disabled ?? false) === true) { ?>
    disabled
<?php } ?>
<?php if (($view->ariaLabel ?? '') !== '') { ?>
    aria-label="<?= $view->ariaLabel ?>"
<?php } ?>
></sf-reference-link>
