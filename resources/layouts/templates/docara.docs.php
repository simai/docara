<article data-docara-declarative-page="<?= $view->pageKey ?>" data-docara-page-title="<?= $view->title ?>">
<?php if ($view->enabledRegions['header']): ?>
    <header data-docara-region="header"><?= $view->regions['header'] ?></header>
<?php endif; ?>
<?php if ($view->enabledRegions['sidebar']): ?>
    <aside data-docara-region="sidebar"><?= $view->regions['sidebar'] ?></aside>
<?php endif; ?>
<?php if ($view->enabledRegions['main']): ?>
    <main data-docara-region="main"><?= $view->regions['main'] ?></main>
<?php endif; ?>
<?php if ($view->enabledRegions['outline']): ?>
    <aside data-docara-region="outline"><?= $view->regions['outline'] ?></aside>
<?php endif; ?>
<?php if ($view->enabledRegions['footer']): ?>
    <footer data-docara-region="footer"><?= $view->regions['footer'] ?></footer>
<?php endif; ?>
</article>
