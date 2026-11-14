<div class="docara-header-tools flex items-cross-center gap-1/3">
<?php if ($view->searchEnabled) { ?>
    <sf-button id="docara-search-trigger" data-docara-search-trigger class="docara-search-trigger" text="<?= $view->copy['search.label'] ?>" aria-label="<?= $view->copy['search.open'] ?>" appearance="outline" scheme="on-surface" icon-start="search" shortcut="mod+k" style="display: contents;"><button type="button" class="sf-button sf-button--size-1 sf-button--outline sf-button--on-surface flex items-cross-center" aria-label="<?= $view->copy['search.open'] ?>" aria-haspopup="dialog" aria-expanded="false" aria-keyshortcuts="Meta+K" data-sf-static><sf-icon root-class="pointer-event-none" weight="400" aria-hidden="true" icon="search" style="display: contents;"><i class="sf-icon sf-icon--weight-regular pointer-event-none" style="--sf-icon--grade:0;--sf-icon--optical-size:24" aria-hidden="true" data-sf-static>search</i></sf-icon><span class="sf-button-text-container text-center pointer-event-none"><?= $view->copy['search.label'] ?></span><kbd class="sf-kbd--group sf-button-shortcut pointer-event-none" aria-hidden="true"><kbd class="sf-button-shortcut-key--chars-4">⌘</kbd><kbd>K</kbd></kbd></button></sf-button>
<?php } ?>
<?php if (count($view->languageOptions) > 1) {
    // The Framework's language picker: each item carries the address of this
    // page in that language, so a choice follows it. The menu keeps the
    // header as narrow as the other tool buttons.
    $docaraLanguageItems = [];
    $docaraLanguageCurrent = '';
    foreach ($view->languageOptions as $option) {
        $code = html_entity_decode($option['locale'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $docaraLanguageItems[] = [
            'code' => $code,
            'label' => html_entity_decode($option['label'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'href' => html_entity_decode($option['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];
        if ($option['current']) {
            $docaraLanguageCurrent = $code;
        }
    }
    $docaraLanguageJson = json_encode(
        ['items' => $docaraLanguageItems],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP,
    ); ?>
    <sf-language data-docara-language mode="menu" size="1"<?php if ($docaraLanguageCurrent !== '') { ?> value="<?= htmlspecialchars($docaraLanguageCurrent, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"<?php } ?> label="<?= $view->copy['language.label'] ?>"><script type="application/json" data-sf-settings><?= $docaraLanguageJson ?></script></sf-language>
<?php } ?>
<?php if ($view->readerPreferencesEnabled) { ?>
    <button id="docara-reader-settings-trigger" class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default" data-docara-reader-settings-trigger type="button" aria-haspopup="<?= str_contains($view->readerPreferencesHtml, '<sf-settings') ? 'menu' : 'dialog' ?>" aria-controls="docara-reader-settings-dialog" aria-expanded="false" aria-label="<?= $view->copy['reader.open'] ?>"><sf-icon icon="settings" aria-hidden="true"></sf-icon></button>
<?php } ?>
<?php if ($view->repository !== null) { ?>
    <a href="<?= $view->repository['url'] ?>" rel="noopener" data-docara-repository-link class="sf-icon-button sf-icon-button--icon sf-icon-button--on-surface sf-icon-button--link sf-icon-button--size-1 radius-default flex items-cross-center content-main-center" aria-label="<?= $view->copy['navigation.repository'] ?>" title="<?= $view->copy['navigation.repository'] ?>"><?php if ($view->repository['github']) { ?><svg class="docara-repository-mark" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"/></svg><?php } else { ?><sf-icon icon="code" aria-hidden="true"></sf-icon><?php } ?></a>
<?php } ?>
</div>
