<sf-badge
<?php if (($view->text ?? '') !== '') { ?>
    text="<?= $view->text ?>"
<?php } ?>
    size="<?= $view->size ?>"
<?php if (($view->line ?? '') !== '') { ?>
    line="<?= $view->line ?>"
<?php } ?>
    appearance="<?= $view->appearance ?>"
    scheme="<?= $view->scheme ?>"
<?php if (($view->status ?? '') !== '') { ?>
    status="<?= $view->status ?>"
<?php } ?>
<?php if (($view->activity ?? '') !== '') { ?>
    activity="<?= $view->activity ?>"
<?php } ?>
<?php if (($view->icon ?? '') !== '') { ?>
    icon="<?= $view->icon ?>"
<?php } ?>
    icon-position="<?= $view->iconPosition ?>"
<?php if (($view->iconOnly ?? false) === true) { ?>
    icon-only
<?php } ?>
<?php if (($view->tight ?? false) === true) { ?>
    tight
<?php } ?>
<?php if (($view->ariaLabel ?? '') !== '') { ?>
    aria-label="<?= $view->ariaLabel ?>"
<?php } ?>
></sf-badge>
