<article data-docara-declarative-page="<?= $view->pageKey ?>" data-docara-page-title="<?= $view->title ?>">
    <header data-docara-region="header"><?= $view->regions['header'] ?></header>
    <aside data-docara-region="sidebar"><?= $view->regions['sidebar'] ?></aside>
    <main data-docara-region="main"><?= $view->regions['main'] ?></main>
    <aside data-docara-region="outline"><?= $view->regions['outline'] ?></aside>
    <footer data-docara-region="footer"><?= $view->regions['footer'] ?></footer>
</article>
