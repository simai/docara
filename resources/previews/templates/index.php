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
            <p class="m-0 color-primary weight-7">SHADOW MODE</p>
            <h1 class="m-0">Декларативный preview Docara</h1>
            <p class="m-0 color-on-surface-variant">Здесь можно открыть результат новой цепочки рядом с неизменной legacy-страницей.</p>
            <div class="flex flex-wrap gap-1">
                <span class="surface-container border border-outline-variant radius-1 p-1">Собрано: <?= $view->renderedCount ?></span>
                <span class="surface-container border border-outline-variant radius-1 p-1">Пропущено: <?= $view->skippedCount ?></span>
                <a class="sf-button sf-button--outline sf-button--on-surface sf-button--size-1 radius-default decoration-none" href="<?= $view->receiptUrl ?>">Открыть JSON receipt</a>
            </div>
        </header>
        <section class="surface-0 border border-outline-variant radius-2 overflow-hidden" aria-labelledby="preview-pages-title">
            <h2 id="preview-pages-title" class="m-0 p-2 border-bottom-1 border-outline-variant">Страницы</h2>
            <div class="flex flex-col">
<?php foreach ($view->items as $item) { ?>
                <article class="flex flex-wrap items-center content-main-between gap-2 p-2 border-bottom-1 border-outline-variant">
                    <div class="flex flex-col gap-1">
                        <strong><?= $item->title ?></strong>
                        <a class="color-on-surface-variant" href="<?= $item->legacyUrl ?>"><?= $item->legacyUrl ?></a>
<?php if ($item->unsupportedLabel !== '') { ?>
                        <span class="color-on-surface-variant">Не поддержано: <?= $item->unsupportedLabel ?></span>
<?php } ?>
                    </div>
<?php if ($item->previewUrl !== null) { ?>
                    <a class="sf-button sf-button--default sf-button--primary sf-button--size-1 bg-primary color-on-primary p-1/2 line-none radius-default inline-flex items-center content-main-center decoration-none" href="<?= $item->previewUrl ?>"><span class="sf-button-text-container">Открыть preview</span></a>
<?php } else { ?>
                    <span class="surface-container border border-outline-variant radius-1 p-1">Только legacy</span>
<?php } ?>
                </article>
<?php } ?>
            </div>
        </section>
    </main>
</body>
</html>
