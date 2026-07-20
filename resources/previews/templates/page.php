<!doctype html>
<html lang="<?= $view->locale ?>" class="theme-light" data-docara-documentation-version="<?= $view->documentationVersion ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="docara:documentation-version" content="<?= $view->documentationVersion ?>">
    <title><?= $view->title ?></title>
<?= $view->headHtml ?>
</head>
<body class="bg-surface color-on-surface m-0">
    <div class="surface-container border-bottom-1 border-outline-variant p-2">
        <div class="flex flex-wrap items-center content-main-between gap-2">
            <div class="flex flex-col gap-1">
                <strong>Декларативный preview</strong>
                <span class="color-on-surface-variant"><?= $view->pageTitle ?></span>
            </div>
            <nav class="flex flex-wrap items-center gap-1" aria-label="Preview controls">
                <a class="sf-button sf-button--outline sf-button--on-surface sf-button--size-1 radius-default decoration-none" href="<?= $view->catalogUrl ?>">Все страницы</a>
                <a class="sf-button sf-button--link sf-button--on-surface sf-button--size-1 radius-default decoration-none" href="<?= $view->legacyUrl ?>">Открыть legacy</a>
            </nav>
        </div>
    </div>
<?= $view->contentHtml ?>
</body>
</html>
