<?php

declare(strict_types=1);

namespace Larena\Docara\Admin;

use Larena\Ui\Smart;

final class DocumentationPageFormPresenter
{
    /**
     * @param array{title:string,slug:string,body:string,locale:string,hero_file_ref:string,publication_status:string} $values
     * @param array<string,string> $errors
     * @param array<string,string> $labels
     * @param list<array{text:string,value:string}> $images
     * @return array{title:string,slug:string,body:string,save:string,publish:string,unpublish:string,status:callable(string,string):string}
     */
    public function present(array $values, array $errors, array $labels, array $images, bool $editing): array
    {
        return [
            'title' => Smart::render('sf-input', $this->inputProps('page-title', 'title', $labels['title'], $values['title'], $errors['title']))->html,
            'slug' => Smart::render('sf-input', $this->inputProps('page-slug', 'slug', $labels['slug'], $values['slug'], $errors['slug']))->html,
            'body' => Smart::render('sf-textarea', [
                'id' => 'page-body',
                'name' => 'body',
                'label' => $labels['body'],
                'value' => $values['body'],
                'required' => true,
                'rows' => 12,
                'type' => 'bordered',
                'size' => '1',
                'error' => $errors['body'] !== '',
                'hint' => $errors['body'],
            ])->html,
            'locale' => $this->dropdown('page-locale', 'locale', $labels['locale'], $values['locale'], [
                ['text' => 'English', 'value' => 'en'], ['text' => 'Русский', 'value' => 'ru'],
            ], true, $editing, $errors['locale'] ?? ''),
            'hero' => $this->dropdown('page-hero', 'hero_file_ref', $labels['hero'], $values['hero_file_ref'] === '' ? '__none__' : $values['hero_file_ref'], array_merge([
                ['text' => $labels['no_hero'], 'value' => '__none__'],
            ], $images), false, false, $errors['hero_file_ref'] ?? ''),
            'publication_status' => $this->dropdown('page-status', 'status', $labels['publication_status'], $values['publication_status'], $values['publication_status'] === 'published' ? [
                ['text' => $labels['published'], 'value' => 'published'],
            ] : [
                ['text' => $labels['draft'], 'value' => 'draft'], ['text' => $labels['review'], 'value' => 'review'], ['text' => $labels['archived'], 'value' => 'archived'],
            ], true, $values['publication_status'] === 'published', $errors['status'] ?? '', $values['publication_status'] === 'published' ? $labels['unpublish_help'] : ''),
            'save' => $this->button($labels['save'], 'default', 'primary'),
            'publish' => $this->button($labels['publish'], 'default', 'primary'),
            'unpublish' => $this->button($labels['unpublish'], 'tonal', 'secondary'),
            'status' => static fn (string $text, string $status): string => Smart::render('sf-badge', [
                'size' => '1/2',
                'type' => 'tonal',
                'scheme' => $status === 'published' ? 'success' : ($status === 'archived' ? 'neutral' : 'primary'),
                'text' => $text,
            ])->html,
        ];
    }

    /** @return array<string, mixed> */
    private function inputProps(string $id, string $name, string $label, string $value, string $error): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'label' => $label,
            'value' => $value,
            'required' => true,
            'type' => 'bordered',
            'size' => '1',
            'error' => $error !== '',
            'hint' => $error,
        ];
    }

    private function button(string $text, string $type, string $scheme): string
    {
        return Smart::render('sf-button', [
            'text' => $text,
            'type' => $type,
            'scheme' => $scheme,
            'native-type' => 'submit',
        ])->html;
    }

    /** @param list<array{text:string,value:string}> $options */
    private function dropdown(string $id, string $name, string $label, string $value, array $options, bool $required, bool $disabled, string $error = '', string $hint = ''): string
    {
        $html = Smart::render('sf-dropdown', [
            'id' => $id, 'name' => $name, 'label' => $label, 'value' => $value,
            'required' => $required, 'disabled' => $disabled, 'type' => 'outlined', 'size' => '1',
            'options' => array_map(static fn (array $option): array => $option + ['selected' => $option['value'] === $value], $options),
        ])->html;
        if ($error !== '') {
            return '<div aria-invalid="true">' . $html . Smart::render('sf-alert', ['type' => 'danger', 'supporting-text' => $error])->html . '</div>';
        }
        if ($hint !== '') {
            return $html . Smart::render('sf-alert', ['type' => 'info', 'supporting-text' => $hint])->html;
        }
        return $html;
    }
}
