<div data-docara-demonstrator-detail class="flex flex-col gap-4">
    <header class="flex flex-col gap-1">
        <span class="color-primary weight-7"><?= $view->category ?></span>
        <h1><?= $view->title ?></h1>
        <p class="color-on-surface-variant m-0"><?= $view->description ?></p>
    </header>
    <section class="flex flex-col gap-2" aria-labelledby="example-result-title">
        <div class="flex flex-wrap items-center content-main-between gap-1">
            <h2 id="example-result-title" class="m-0"><?= $view->resultLabel ?></h2>
            <a class="sf-button sf-button--outline sf-button--primary sf-button--size-1 radius-default decoration-none inline-flex items-center content-main-center" href="<?= $view->resultUrl ?>" target="_blank" rel="noopener"><span class="sf-button-text-container"><?= $view->openSeparatelyLabel ?></span></a>
        </div>
        <div class="docara-example-preview surface-0 border border-outline-variant radius-2 overflow-hidden" data-preview-size="<?= $view->previewSize ?>">
            <iframe src="<?= $view->resultUrl ?>" title="<?= $view->resultFrameLabel ?>" loading="lazy"></iframe>
        </div>
    </section>
    <section class="flex flex-col gap-2" aria-labelledby="example-source-title">
        <h2 id="example-source-title" class="m-0"><?= $view->sourcesLabel ?></h2>
        <p class="color-on-surface-variant m-0"><?= $view->sourcesDescription ?></p>
<?php foreach ($view->sources as $source) { ?>
        <article class="surface-0 border border-outline-variant radius-2 overflow-hidden">
            <header class="flex flex-wrap items-center content-main-between gap-1 p-2 border-bottom-1 border-outline-variant">
                <h3 class="m-0"><?= $source->label ?></h3>
                <code><?= $source->path ?></code>
            </header>
            <pre class="docara-example-source m-0 p-2 overflow-auto" data-language="<?= $source->language ?>"><code><?= $source->code ?></code></pre>
        </article>
<?php } ?>
    </section>
</div>
