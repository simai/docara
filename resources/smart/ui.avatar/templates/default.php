<sf-avatar
<?php if (($view->text ?? '') !== '') { ?>
    text="<?= $view->text ?>"
<?php } ?>
<?php if (($view->heading ?? '') !== '') { ?>
    heading="<?= $view->heading ?>"
<?php } ?>
<?php if (($view->icon ?? '') !== '') { ?>
    icon="<?= $view->icon ?>"
<?php } ?>
    size="<?= $view->size ?>"
<?php if (($view->line ?? '') !== '') { ?>
    line="<?= $view->line ?>"
<?php } ?>
    shape="<?= $view->shape ?>"
<?php if (($view->hue ?? '') !== '') { ?>
    hue="<?= $view->hue ?>"
<?php } ?>
    status="<?= $view->status ?>"
<?php if (($view->badgeCount ?? '') !== '') { ?>
    badge-count="<?= $view->badgeCount ?>"
<?php } ?>
<?php if (($view->badgeDot ?? false) === true) { ?>
    badge-dot
<?php } ?>
<?php if (($view->ariaLabel ?? '') !== '') { ?>
    aria-label="<?= $view->ariaLabel ?>"
<?php } ?>
></sf-avatar>
