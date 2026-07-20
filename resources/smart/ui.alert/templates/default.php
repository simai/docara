<sf-alert
    id="<?= $view->id ?>"
    data-larena-smart-runtime="<?= $view->runtimePair ?>"
    type="<?= $view->type ?>"
    variant="<?= $view->variant ?>"
<?php if ($view->icon !== null): ?>
    icon="<?= $view->icon ?>"
<?php endif; ?>
    title="<?= $view->title ?>"
    supporting-text="<?= $view->supportingText ?>"
    aria-label="<?= $view->ariaLabel ?>"
<?php if ($view->closable): ?>
    closable
<?php endif; ?>
></sf-alert>
