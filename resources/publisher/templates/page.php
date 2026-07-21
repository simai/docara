<!doctype html>
<html lang="<?= $view->locale ?>" dir="<?= $view->direction ?>" class="theme-light" data-docara-documentation-version="<?= $view->documentationVersion ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="docara:documentation-version" content="<?= $view->documentationVersion ?>">
<?php if ($view->description !== null) { ?>
    <meta name="description" content="<?= $view->description ?>">
<?php } ?>
<?php if ($view->favicon !== null) { ?>
    <link rel="icon" href="<?= $view->favicon ?>"<?php if ($view->faviconType !== null) { ?> type="<?= $view->faviconType ?>"<?php } ?>>
<?php } ?>
<?php foreach ($view->alternates as $alternate) { ?>
    <link rel="alternate" hreflang="<?= $alternate['locale'] ?>" href="<?= $alternate['url'] ?>">
<?php } ?>
    <title><?= $view->documentTitle ?></title>
<?= $view->headHtml ?>
    <link rel="stylesheet" href="<?= $view->shellCssUrl ?>" data-docara-declarative-shell-style>
<?= $view->themeBootstrap ?>
<?= $view->chrome['head'] ?>
</head>
<body class="bg-surface">
    <a class="docara-skip-link bg-surface-0 border radius-1 p-1" href="#docara-main"><?= $view->copy['shell.skip_to_content'] ?></a>
    <header class="docara-header sticky top-0 z-2 bg-surface-0 border-bottom-1 border-outline-variant" data-docara-region="header">
        <div class="docara-header-row flex items-center content-main-between gap-2 p-1">
            <?= $view->regions['header'] ?>
            <?= $view->chrome['header_actions'] ?>
        </div>
        <?= $view->chrome['mobile_navigation'] ?>
    </header>
<?php if ($view->preset === 'landing') { ?>
    <main id="docara-main" tabindex="-1" class="docara-landing p-4" data-docara-region="main">
        <article class="docara-content docara-prose flex flex-col gap-2" data-width="<?= $view->maxWidth ?>"><?= $view->regions['main'] ?></article>
    </main>
<?php } else { ?>
    <div class="docara-docs-layout gap-0" data-sidebar="<?= $view->regions['sidebar'] === '' ? 'false' : 'true' ?>" data-outline="<?= $view->regions['outline'] === '' ? 'false' : 'true' ?>">
<?php if ($view->regions['sidebar'] !== '') { ?>
        <aside class="docara-sidebar p-2" data-docara-region="sidebar"><?= $view->regions['sidebar'] ?></aside>
<?php } ?>
        <div class="docara-reading-column flex flex-col gap-2 p-3">
            <?= $view->chrome['breadcrumbs'] ?>
            <?= $view->chrome['mobile_toc'] ?>
            <main id="docara-main" tabindex="-1" class="docara-content" data-width="<?= $view->maxWidth ?>" data-docara-region="main">
                <article class="docara-prose"><?= $view->regions['main'] ?></article>
                <?= $view->chrome['pager'] ?>
            </main>
        </div>
<?php if ($view->regions['outline'] !== '') { ?>
        <aside class="docara-outline-rail p-2" data-docara-region="outline"><?= $view->regions['outline'] ?></aside>
<?php } ?>
    </div>
<?php } ?>
<?php if ($view->regions['footer'] !== '') { ?>
    <footer data-docara-region="footer"><?= $view->regions['footer'] ?></footer>
<?php } ?>
    <?= $view->chrome['search_dialog'] ?>
    <?= $view->chrome['reader_settings'] ?>
    <script type="application/json" id="docara-runtime-copy"><?= $view->runtimeCopyJson ?></script>
    <script src="<?= $view->shellRuntimeUrl ?>" data-docara-shell-controller></script>
</body>
</html>
