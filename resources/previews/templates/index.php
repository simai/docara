<!doctype html>
<html lang="<?= $view->locale ?>" class="theme-light" data-docara-documentation-version="<?= $view->documentationVersion ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="docara:documentation-version" content="<?= $view->documentationVersion ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E">
    <title><?= $view->title ?></title>
<?= $view->headHtml ?>
</head>
<body class="bg-surface color-on-surface m-0">
    <main class="container flex flex-col gap-3 p-3">
        <header class="flex flex-col gap-2">
            <p class="m-0 color-primary weight-7"><?= $view->copy['eyebrow'] ?></p>
            <h1 class="m-0"><?= $view->copy['title'] ?></h1>
            <p class="m-0 color-on-surface-variant"><?= $view->copy['description'] ?></p>
            <div class="flex flex-wrap gap-1">
                <span class="surface-container border border-outline-variant radius-1 p-1"><?= $view->copy['rendered'] ?>: <?= $view->renderedCount ?></span>
                <span class="surface-container border border-outline-variant radius-1 p-1"><?= $view->copy['skipped'] ?>: <?= $view->skippedCount ?></span>
                <a class="sf-button sf-button--outline sf-button--on-surface sf-button--size-1 radius-default decoration-none" href="<?= $view->receiptUrl ?>"><?= $view->copy['receipt'] ?></a>
            </div>
        </header>
        <section class="surface-0 border border-outline-variant radius-2 overflow-hidden" aria-labelledby="preview-pages-title">
            <h2 id="preview-pages-title" class="m-0 p-2 border-bottom-1 border-outline-variant"><?= $view->copy['pages'] ?></h2>
            <div class="flex flex-col">
<?php foreach ($view->items as $item) { ?>
                <article class="flex flex-wrap items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant">
                    <div class="flex flex-col gap-1">
                        <strong><?= $item->title ?></strong>
                        <a class="color-on-surface-variant" href="<?= $item->legacyUrl ?>"><?= $item->legacyUrl ?></a>
<?php if ($item->unsupportedLabel !== '') { ?>
                        <span class="color-on-surface-variant"><?= $view->copy['unsupported'] ?>: <?= $item->unsupportedLabel ?></span>
<?php } ?>
                    </div>
<?php if ($item->previewUrl !== null) { ?>
                    <a class="sf-button sf-button--default sf-button--primary sf-button--size-1 bg-primary color-on-primary p-1/2 line-none radius-default inline-flex items-center content-main-center decoration-none" href="<?= $item->previewUrl ?>"><span class="sf-button-text-container"><?= $view->copy['open_preview'] ?></span></a>
<?php } else { ?>
                    <span class="surface-container border border-outline-variant radius-1 p-1"><?= $view->copy['legacy_only'] ?></span>
<?php } ?>
                </article>
<?php } ?>
            </div>
        </section>
    </main>
</body>
</html>
