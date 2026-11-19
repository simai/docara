<sf-alert
    id="<?= $view->id ?>"
    data-larena-smart-runtime="<?= $view->runtimePair ?>"
    status="<?= $view->type ?>"
    appearance="<?= $view->variant ?>"
<?php if ($view->icon !== null) { ?>
    icon="<?= $view->icon ?>"
<?php } ?>
    heading="<?= $view->title ?>"
    supporting-text="<?= $view->supportingText ?>"
    aria-label="<?= $view->ariaLabel ?>"
<?php if ($view->closable) { ?>
    closable
<?php } ?>
></sf-alert>
