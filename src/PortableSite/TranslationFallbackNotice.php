<?php

declare(strict_types=1);

namespace Simai\Docara\PortableSite;

use Simai\Docara\I18n\Translator;
use Simai\Docara\I18n\UiCopy;

/**
 * The notice at the top of a page that shows the source text while its
 * translation is held back (translation_tracking mode publish-current). The
 * wording is `language.fallback_notice` from the page locale's lang.json, or
 * Docara's default; `{source}` becomes a link to the original page.
 *
 * The page body around the notice is marked with the source language
 * (DeclarativePortablePagePublisher), so the notice carries the page
 * locale's `lang` and `dir` itself: it is the one part written in the
 * page locale.
 */
final readonly class TranslationFallbackNotice
{
    /** @param array{status:string,source_locale:string,source_url:string,source_label:string} $fallback */
    public function render(Translator $translator, string $locale, array $fallback, string $direction = 'ltr'): string
    {
        $message = $translator->messageOr(
            $locale,
            'language.fallback_notice',
            UiCopy::buildText($locale, 'language.fallback_notice'),
        );
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $link = '<a href="' . $escape($fallback['source_url']) . '" hreflang="' . $escape($fallback['source_locale'])
            . '" lang="' . $escape($fallback['source_locale']) . '">' . $escape($fallback['source_label']) . '</a>';

        return '<aside data-docara-translation-fallback="' . $escape($fallback['status'])
            . '" role="note" lang="' . $escape($locale) . '" dir="' . ($direction === 'rtl' ? 'rtl' : 'ltr')
            . '" class="sf-alert sf-alert--warning sf-alert--flat flex items-cross-start m-bottom-1">'
            . '<sf-icon icon="info" aria-hidden="true"></sf-icon><div class="sf-alert-wrap flex-1"><div class="sf-alert-content"><p>'
            . str_replace('{source}', $link, $escape($message))
            . '</p></div></div></aside>';
    }
}
