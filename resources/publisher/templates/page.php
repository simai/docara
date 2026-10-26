<!doctype html>
<html lang="<?= $view->locale ?>" dir="<?= $view->direction ?>" class="theme-light" data-docara-documentation-version="<?= $view->documentationVersion ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="docara:documentation-version" content="<?= $view->documentationVersion ?>">
<?php if ($view->description !== null) { ?>
    <meta name="description" content="<?= $view->description ?>">
<?php } ?>
<?php if ($view->robots !== null) { ?>
    <meta name="robots" content="<?= $view->robots ?>">
<?php } ?>
<?php if ($view->favicon !== null) { ?>
    <link rel="icon" href="<?= $view->favicon ?>"<?php if ($view->faviconType !== null) { ?> type="<?= $view->faviconType ?>"<?php } ?>>
<?php } ?>
    <link rel="canonical" href="<?= $view->canonicalUrl ?>">
<?php foreach ($view->alternates as $alternate) { ?>
    <link rel="alternate" hreflang="<?= $alternate['locale'] ?>" href="<?= $alternate['url'] ?>">
<?php } ?>
<?php if ($view->socialMeta !== null) { ?>
<?= $view->socialMeta ?>
<?php } ?>
    <title><?= $view->documentTitle ?></title>
<?= $view->headHtml ?>
<?php foreach ($view->shellCssUrls as $shellCssUrl) { ?>
    <link rel="stylesheet" href="<?= $shellCssUrl ?>" data-docara-declarative-shell-style>
<?php } ?>
<?php if ($view->themeCssUrl !== null) { ?>
    <link rel="stylesheet" href="<?= $view->themeCssUrl ?>" data-docara-site-theme>
<?php } ?>
<?php if ($view->portalHome !== null) { ?>
    <style data-docara-portal>.docara-portal-home{display:inline-flex;align-items:center;flex:0 0 auto;padding-inline-end:var(--sf-space-1);border-inline-end:1px solid var(--sf-outline-variant);color:var(--sf-on-surface-variant);font-size:var(--sf-text-small--size);text-decoration:none;white-space:nowrap}.docara-portal-home:hover{color:var(--sf-on-surface)}.docara-portal-home:focus-visible{outline:var(--sf-focus--width) var(--sf-focus--style) var(--sf-focus--color,Highlight);outline-offset:var(--sf-focus--offset)}</style>
<?php } ?>
<?= $view->themeBootstrap ?>
<?= $view->chrome['head'] ?>
</head>
<body class="bg-surface <?= $view->containerClass ?>" data-docara-shell>
    <a class="docara-skip-link bg-surface-0 border radius-1 p-1" href="#docara-main"><?= $view->copy['shell.skip_to_content'] ?></a>
    <header class="docara-header sticky top-0 z-2 bg-surface-0 border-bottom-1 border-outline-variant" data-docara-region="header"<?php if ($view->portalHome !== null) { ?> data-portal-shell="header"<?php } ?>>
        <div class="docara-header-row container w-full m-inline-auto flex items-cross-center content-main-between gap-2 p-1">
<?php if ($view->portalHome !== null) { ?>
            <a class="docara-portal-home" href="<?= $view->portalHome['url'] ?>" data-docara-portal-home><?= $view->portalHome['label'] ?></a>
<?php } ?>
            <?= $view->regions['header'] ?>
            <?= $view->chrome['header_actions'] ?>
        </div>
        <?= $view->chrome['mobile_navigation'] ?>
    </header>
<?php if ($view->preset === 'landing') { ?>
    <main id="docara-main" tabindex="-1" class="docara-landing" data-docara-region="main">
        <article class="docara-content docara-prose container w-full m-inline-auto min-w-0<?= $view->contentGap === 0 ? '' : ' flex flex-col gap-' . $view->contentGap ?>"<?= $view->contentLanguage ?>><?= $view->regions['main'] ?></article>
    </main>
<?php } else { ?>
    <div class="docara-docs-layout container w-full m-inline-auto gap-0" data-sidebar="<?= $view->regions['sidebar'] === '' ? 'false' : 'true' ?>" data-outline="<?= $view->regions['outline'] === '' ? 'false' : 'true' ?>" data-mobile-toc="<?= $view->mobileTocState ?>">
<?php if ($view->regions['sidebar'] !== '') { ?>
        <aside class="docara-sidebar" data-docara-region="sidebar" data-sf-observer="ignore">
            <div class="docara-sidebar-panel">
                <div class="docara-sidebar-scroll sf-scrollbar"<?= $view->scrollbarPreset === 'overlay' ? '' : ' data-sf-scrollbar="' . $view->scrollbarPreset . '"' ?>>
                    <div class="sf-scrollbar__viewport" tabindex="0" aria-label="<?= $view->copy['navigation.sections'] ?>"><?= $view->regions['sidebar'] ?></div>
                </div>
<?php if ($view->sidebarCredit !== null) { ?>
                <?= $view->sidebarCredit ?>

<?php } ?>
            </div>
        </aside>
<?php /* Before the first paint: a page reached from this site keeps the sidebar where the reader left it (declarative-shell.js saves it on pagehide and link click); then, or on a page opened directly, a current item out of view is centred. Only the sidebar scrolls, at once even under the Framework's smooth scroll-behavior, so the page does not move and nothing animates. */ ?>
        <script data-docara-sidebar-scroll>(function(){var v=document.querySelector('aside[data-docara-region="sidebar"]>.docara-sidebar-panel>.docara-sidebar-scroll>.sf-scrollbar__viewport');if(!v)return;function to(top){var b=v.style.scrollBehavior;v.style.scrollBehavior='auto';v.scrollTop=top;v.style.scrollBehavior=b}try{if(document.referrer&&new URL(document.referrer).origin===location.origin){var s=JSON.parse(sessionStorage.getItem('docara.navigation.scroll')||'null');if(s&&typeof s.sidebar==='number'&&s.sidebar>0)to(s.sidebar)}}catch(e){}var a=v.querySelector('[aria-current="page"]');if(!a)return;var r=v.getBoundingClientRect(),c=a.getBoundingClientRect();if(r.height>0&&c.height>0&&(c.top<r.top||c.bottom>r.bottom))to(v.scrollTop+c.top-r.top-(r.height-c.height)/2)})();</script>
<?php } ?>
        <div class="docara-reading-column flex min-w-0 flex-col gap-2 p-3">
            <div class="docara-reading-toolbar flex items-cross-center gap-1 min-w-0"><div class="docara-reading-toolbar__location flex-1 min-w-0"><?= $view->chrome['breadcrumbs'] ?></div><?= $view->chrome['reading_actions'] ?></div>
            <?= $view->chrome['mobile_toc'] ?>
            <main id="docara-main" tabindex="-1" class="docara-content min-w-0" data-docara-region="main">
                <article class="docara-prose min-w-0<?= $view->contentGap === 0 ? '' : ' flex flex-col gap-' . $view->contentGap ?>"<?= $view->contentLanguage ?>><?= $view->regions['main'] ?></article>
                <?= $view->chrome['pager'] ?>
            </main>
        </div>
<?php if ($view->regions['outline'] !== '') { ?>
        <aside id="docara-outline" class="docara-outline-rail" data-docara-region="outline">
            <div class="docara-outline-scroll sf-scrollbar"<?= $view->scrollbarPreset === 'overlay' ? '' : ' data-sf-scrollbar="' . $view->scrollbarPreset . '"' ?>>
                <div class="sf-scrollbar__viewport" tabindex="0" aria-label="<?= $view->copy['navigation.outline'] ?>"><?= $view->regions['outline'] ?></div>
            </div>
        </aside>
<?php } ?>
    </div>
<?php } ?>
<?php if ($view->regions['footer'] !== '') { ?>
    <footer class="container w-full m-inline-auto" data-docara-region="footer"<?php if ($view->portalHome !== null) { ?> data-portal-shell="footer"<?php } ?>><?= $view->regions['footer'] ?></footer>
<?php } ?>
    <?= $view->chrome['search_dialog'] ?>
    <?= $view->chrome['reader_settings'] ?>
    <script type="application/json" id="docara-runtime-copy"><?= $view->runtimeCopyJson ?></script>
    <script src="<?= $view->shellRuntimeUrl ?>" data-docara-shell-controller></script>
</body>
</html>
