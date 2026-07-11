<?php

declare(strict_types=1);

namespace Larena\Docara\Admin;

use Illuminate\Contracts\Translation\Translator;
use Larena\Ui\Smart;

final readonly class DocaraSiteSettingsFormPresenter
{
    public function __construct(private Translator $translator)
    {
    }

    /**
     * @param array<string, string> $values
     * @param list<array{page_ref:string,title:string,locale:string,slug:string}> $pages
     * @param list<array{logical_ref:string,display_name:string,mime_type:string}> $images
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    public function present(array $values, array $pages, array $images, array $errors, bool $canWrite): array
    {
        $disabled = !$canWrite;
        $imageOptions = [['text' => $this->text('site_settings.no_image'), 'value' => '']];
        foreach ($images as $image) {
            $imageOptions[] = [
                'text' => $image['display_name'] . ' · ' . $image['mime_type'],
                'value' => $image['logical_ref'],
            ];
        }

        $components = [
            'name_en' => $this->input('name_en', 'name_en', $values['name_en'], true, $errors, $disabled),
            'name_ru' => $this->input('name_ru', 'name_ru', $values['name_ru'], true, $errors, $disabled),
            'description_en' => $this->textarea('description_en', 'description_en', $values['description_en'], $errors, $disabled),
            'description_ru' => $this->textarea('description_ru', 'description_ru', $values['description_ru'], $errors, $disabled),
            'logo_file_ref' => $this->dropdown('logo_file_ref', 'logo', $values['logo_file_ref'], $imageOptions, false, $disabled),
            'favicon_file_ref' => $this->dropdown('favicon_file_ref', 'favicon', $values['favicon_file_ref'], $imageOptions, false, $disabled),
            'default_locale' => $this->dropdown('default_locale', 'default_locale', $values['default_locale'], [
                ['text' => 'English', 'value' => 'en'],
                ['text' => 'Русский', 'value' => 'ru'],
            ], true, $disabled),
            'homepage_page_ref_en' => $this->pageDropdown('homepage_page_ref_en', 'homepage_en', 'en', $values['homepage_page_ref_en'], $pages, $disabled),
            'homepage_page_ref_ru' => $this->pageDropdown('homepage_page_ref_ru', 'homepage_ru', 'ru', $values['homepage_page_ref_ru'], $pages, $disabled),
            'save' => $canWrite ? Smart::render('sf-button', [
                'text' => $this->text('site_settings.save'),
                'type' => 'default',
                'scheme' => 'primary',
                'native-type' => 'submit',
            ])->html : '',
            'read_only' => !$canWrite ? Smart::render('sf-alert', [
                'type' => 'info',
                'supporting-text' => $this->text('site_settings.read_only'),
            ])->html : '',
            'validation' => '',
        ];

        if ($errors !== []) {
            $components['validation'] = Smart::render('sf-alert', [
                'type' => 'danger',
                'title' => $this->text('site_settings.validation_heading'),
                'supporting-text' => implode(' ', array_values($errors)),
            ])->html;
        }

        return $components;
    }

    /** @param array<string,string> $errors */
    private function input(string $name, string $label, string $value, bool $required, array $errors, bool $disabled): string
    {
        return Smart::render('sf-input', [
            'name' => $name,
            'label' => $this->text('site_settings.fields.' . $label),
            'value' => $value,
            'required' => $required,
            'type' => 'bordered',
            'size' => '1',
            'disabled' => $disabled,
            'error' => isset($errors[$name]),
            'hint' => $errors[$name] ?? '',
        ])->html;
    }

    /** @param array<string,string> $errors */
    private function textarea(string $name, string $label, string $value, array $errors, bool $disabled): string
    {
        return Smart::render('sf-textarea', [
            'name' => $name,
            'label' => $this->text('site_settings.fields.' . $label),
            'value' => $value,
            'rows' => 3,
            'type' => 'bordered',
            'size' => '1',
            'disabled' => $disabled,
            'error' => isset($errors[$name]),
            'hint' => $errors[$name] ?? '',
        ])->html;
    }

    /** @param list<array{text:string,value:string}> $options */
    private function dropdown(string $name, string $label, string $value, array $options, bool $required, bool $disabled): string
    {
        return Smart::render('sf-dropdown', [
            'name' => $name,
            'label' => $this->text('site_settings.fields.' . $label),
            'value' => $value,
            'required' => $required,
            'disabled' => $disabled,
            'placeholder' => '',
            'type' => 'outlined',
            'size' => '1',
            'options' => array_map(static fn (array $option): array => $option + ['selected' => $option['value'] === $value], $options),
        ])->html;
    }

    /** @param list<array{page_ref:string,title:string,locale:string,slug:string}> $pages */
    private function pageDropdown(string $name, string $label, string $locale, string $value, array $pages, bool $disabled): string
    {
        $options = [['text' => $this->text('site_settings.no_page'), 'value' => '']];
        foreach ($pages as $page) {
            if ($page['locale'] === $locale) {
                $options[] = ['text' => $page['title'] . ' · /' . $page['slug'], 'value' => $page['page_ref']];
            }
        }

        return $this->dropdown($name, $label, $value, $options, false, $disabled);
    }

    private function text(string $key): string
    {
        return (string) $this->translator->get('larena-docara::admin.' . $key);
    }
}
