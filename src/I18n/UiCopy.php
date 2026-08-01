<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

final readonly class UiCopy
{
    private const IDS = [
        'shell.skip_to_content',
        'navigation.open',
        'navigation.mobile_title',
        'navigation.primary',
        'navigation.sections',
        'navigation.title',
        'navigation.close',
        'navigation.breadcrumbs',
        'navigation.breadcrumbs_expand',
        'navigation.outline',
        'navigation.outline_close',
        'navigation.previous_next',
        'navigation.previous',
        'navigation.next',
        'navigation.expand',
        'navigation.collapse',
        'navigation.contains_current',
        'language.label',
        'search.open',
        'search.label',
        'search.title',
        'search.close',
        'search.query',
        'search.placeholder',
        'search.idle',
        'search.loading',
        'search.found',
        'search.empty',
        'search.error',
        'search.navigate',
        'search.open_result',
        'search.dismiss',
        'reader.open',
        'reader.title',
        'reader.close',
        'reader.appearance',
        'reader.appearance_description',
        'reader.help',
        'reader.reset',
        'reader.theme_title',
        'reader.theme_description',
        'reader.theme_system',
        'reader.theme_system_description',
        'reader.theme_light',
        'reader.theme_light_description',
        'reader.theme_dark',
        'reader.theme_dark_description',
        'reader.modal_blur_title',
        'reader.modal_blur_description',
        'reader.modal_blur_none',
        'reader.modal_blur_none_description',
        'reader.modal_blur_small',
        'reader.modal_blur_small_description',
        'reader.modal_blur_medium',
        'reader.modal_blur_medium_description',
        'reader.modal_blur_large',
        'reader.modal_blur_large_description',
        'reader.saved',
        'reader.applied_not_saved',
        'reader.restored',
        'redirect.title',
        'redirect.message',
        'redirect.link',
    ];

    private const OPTIONAL_IDS = [
        'code.copy' => 'Copy',
        'code.copied' => 'Copied',
    ];

    public function __construct(private Translator $translator) {}

    /** @return array<string, string> */
    public function forLocale(string $locale): array
    {
        $copy = [];
        foreach (self::IDS as $id) {
            $copy[$id] = $this->translator->message($locale, $id);
        }
        foreach (self::OPTIONAL_IDS as $id => $default) {
            $copy[$id] = $this->translator->messageOr($locale, $id, $default);
        }

        return $copy;
    }
}
