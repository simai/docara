<div class="flex flex-none items-cross-center gap-1">
<?php if ($view->mobileNavigationEnabled) { ?>
    <button type="button" data-docara-sheet-trigger aria-haspopup="dialog" aria-controls="docara-mobile-navigation" aria-expanded="false" class="docara-mobile-navigation-trigger<?= $view->primaryNavigationEnabled ? ' docara-mobile-navigation-trigger--primary' : '' ?> sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default flex items-cross-center content-main-center" aria-label="<?= $view->copy['navigation.open'] ?>"><sf-icon icon="menu" aria-hidden="true"></sf-icon></button>
<?php } ?>
<?php if ($view->searchEnabled) { ?>
    <sf-button id="docara-search-trigger" data-docara-search-trigger class="docara-search-trigger" text="<?= $view->copy['search.label'] ?>" aria-label="<?= $view->copy['search.open'] ?>" appearance="outline" scheme="on-surface" icon-start="search" shortcut="mod+k" style="display: contents;"><button type="button" class="sf-button sf-button--size-1 sf-button--outline sf-button--on-surface flex items-cross-center" aria-label="<?= $view->copy['search.open'] ?>" aria-haspopup="dialog" aria-expanded="false" aria-keyshortcuts="Meta+K" data-sf-static><sf-icon root-class="pointer-event-none" weight="400" aria-hidden="true" icon="search" style="display: contents;"><i class="sf-icon sf-icon--weight-regular pointer-event-none" style="--sf-icon--grade:0;--sf-icon--optical-size:24" aria-hidden="true" data-sf-static>search</i></sf-icon><span class="sf-button-text-container text-center pointer-event-none"><?= $view->copy['search.label'] ?></span><kbd class="sf-kbd--group sf-button-shortcut pointer-event-none" aria-hidden="true"><kbd class="sf-button-shortcut-key--chars-4">⌘</kbd><kbd>K</kbd></kbd></button></sf-button>
<?php } ?>
<?php if (count($view->languageOptions) > 1) { ?>
    <details data-docara-language-menu class="docara-language-menu relative">
        <summary data-docara-language-trigger aria-label="<?= $view->copy['language.label'] ?>" aria-expanded="false" class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default cursor-pointer"><sf-icon icon="language" aria-hidden="true"></sf-icon></summary>
        <nav class="docara-language-menu__popup" aria-label="<?= $view->copy['language.label'] ?>">
            <ul class="list-none m-0 p-1 flex flex-col gap-1/3">
<?php foreach ($view->languageOptions as $option) { ?>
                <li><a data-docara-language-option href="<?= $option['url'] ?>" lang="<?= $option['locale'] ?>" hreflang="<?= $option['locale'] ?>"<?php if ($option['current']) { ?> aria-current="page"<?php } ?> class="docara-language-menu__option flex items-cross-center content-main-between gap-2 radius-default p-inline-1 p-block-1/2 decoration-none color-on-surface"><span><?= $option['label'] ?></span><?php if ($option['current']) { ?><sf-icon icon="check" aria-hidden="true"></sf-icon><?php } ?></a></li>
<?php } ?>
            </ul>
        </nav>
    </details>
<?php } ?>
<?php if ($view->repository !== null) { ?>
    <a href="<?= $view->repository['url'] ?>" rel="noopener" data-docara-repository-link class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default flex items-cross-center content-main-center" aria-label="<?= $view->copy['navigation.repository'] ?>" title="<?= $view->copy['navigation.repository'] ?>"><?php if ($view->repository['github']) { ?><svg class="docara-repository-mark" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"/></svg><?php } else { ?><sf-icon icon="code" aria-hidden="true"></sf-icon><?php } ?></a>
<?php } ?>
<?php if ($view->readerPreferencesEnabled) { ?>
    <button class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" data-docara-reader-settings-trigger type="button" aria-haspopup="<?= str_contains($view->readerPreferencesHtml, '<sf-context-menu') ? 'menu' : 'dialog' ?>" aria-controls="docara-reader-settings-dialog" aria-expanded="false" aria-label="<?= $view->copy['reader.open'] ?>"><sf-icon icon="more_vert" aria-hidden="true"></sf-icon></button>
<?php } ?>
</div>
