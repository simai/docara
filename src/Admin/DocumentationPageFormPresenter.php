<?php

declare(strict_types=1);

namespace Larena\Docara\Admin;

use Larena\Ui\Smart;

final class DocumentationPageFormPresenter
{
    /**
     * @param array{title:string,slug:string,body:string} $values
     * @param array{title:string,slug:string,body:string} $errors
     * @param array{title:string,slug:string,body:string,save:string,publish:string,unpublish:string} $labels
     * @return array{title:string,slug:string,body:string,save:string,publish:string,unpublish:string,status:callable(string,string):string}
     */
    public function present(array $values, array $errors, array $labels): array
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
}
