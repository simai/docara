<?php

declare(strict_types=1);

namespace Simai\Docara\I18n;

final readonly class UiCopy
{
    private const IDS = [
        'shell.skip_to_content',
        'navigation.open',
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
        'reader.open',
        'reader.title',
        'reader.close',
        'reader.appearance',
        'reader.help',
        'reader.reset',
        'reader.theme_system',
        'reader.theme_system_description',
        'reader.theme_light',
        'reader.theme_light_description',
        'reader.theme_dark',
        'reader.theme_dark_description',
        'reader.saved',
        'reader.applied_not_saved',
        'reader.restored',
        'redirect.title',
        'redirect.message',
        'redirect.link',
    ];

    public function __construct(private Translator $translator) {}

    /** @return array<string, string> */
    public function forLocale(string $locale): array
    {
        $copy = [];
        foreach (self::IDS as $id) {
            $copy[$id] = $this->translator->message($locale, $id);
        }

        return $copy;
    }
}
