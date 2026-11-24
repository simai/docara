<sf-tooltip
<?php if (($view->text ?? '') !== '') { ?>
    text="<?= $view->text ?>"
<?php } ?>
<?php if (($view->supportingText ?? '') !== '') { ?>
    supporting-text="<?= $view->supportingText ?>"
<?php } ?>
<?php if (($view->shortcut ?? '') !== '') { ?>
    shortcut="<?= $view->shortcut ?>"
<?php } ?>
    appearance="<?= $view->appearance ?>"
    arrow="<?= $view->arrow ?>"
></sf-tooltip>
