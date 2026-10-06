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
        'reader.theme_light',
        'reader.theme_dark',
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
        'code.wrap' => 'Wrap source lines',
        'code.unwrap' => 'Keep original source lines',
        'examples.example' => 'Example',
        'examples.viewer' => 'Check responsive preview',
        'examples.viewer_exit' => 'Exit responsive preview',
        'examples.viewer_dialog' => 'Responsive example preview',
        'examples.viewport_group' => 'Preview width',
        'examples.viewport_desktop' => 'Desktop',
        'examples.viewport_tablet' => 'Tablet',
        'examples.viewport_mobile' => 'Mobile',
        'reader.font_size_title' => 'Font size',
        'reader.font_size_description' => 'Changes the size of the page content; navigation keeps its size.',
        'reader.font_size_small' => 'Small',
        'reader.font_size_normal' => 'Normal',
        'reader.font_size_large' => 'Large',
        'reader.content_width_title' => 'Page width',
        'reader.content_width_description' => 'Wide uses the full window and does not limit line length.',
        'reader.content_width_normal' => 'Normal',
        'reader.content_width_wide' => 'Wide',
        'reader.outline_hide' => 'Hide page contents',
        'reader.focus_mode' => 'Reading mode',
        'reader.focus_mode_exit' => 'Exit reading mode (Esc)',
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
