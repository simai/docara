<?php

declare(strict_types=1);

namespace Larena\Docara\Admin;

use Illuminate\Contracts\Translation\Translator;
use Larena\Ui\Smart;

final readonly class DocumentationBlockEditorPresenter
{
    public function __construct(private Translator $translator) {}

    /** @param list<array<string,mixed>> $definitions */
    public function typeSelector(array $definitions): string
    {
        $options = array_map(fn (array $definition): array => [
            'text' => $this->admin((string) $definition['label_key']), 'value' => (string) $definition['key'],
        ], $definitions);
        $value = (string) ($options[0]['value'] ?? '');
        return $this->dropdown('larena-block-type', '', $this->text('add_label'), $value, $options, true, false);
    }

    public function button(string $labelKey, string $scheme = 'secondary', string $nativeType = 'button', string $text = ''): string
    {
        return Smart::render('sf-button', [
            'text' => $text !== '' ? $text : $this->text($labelKey), 'scheme' => $scheme,
            'type' => $scheme === 'danger' ? 'tonal' : 'default', 'native-type' => $nativeType,
            'aria-label' => $this->text($labelKey),
        ])->html;
    }

    public function checkbox(string $name, bool $checked, bool $disabled): string
    {
        return Smart::render('sf-checkbox', [
            'name' => $name, 'value' => '1', 'label' => $this->text('enabled'),
            'checked' => $checked, 'disabled' => $disabled, 'size' => '1',
        ])->html;
    }

    /** @param array<string,mixed> $field @param iterable<mixed> $images */
    public function field(array $field, string $name, string $value, iterable $images, bool $disabled): string
    {
        $label = $this->admin((string) $field['label_key']);
        $required = (bool) $field['required'];
        return match ((string) $field['type']) {
            'text' => Smart::render('sf-textarea', ['name' => $name, 'label' => $label, 'value' => $value, 'rows' => 4, 'required' => $required, 'disabled' => $disabled, 'type' => 'bordered', 'size' => '1'])->html,
            'select' => $this->dropdown('', $name, $label, $value, array_map(fn (string $option): array => ['text' => $this->text('options.' . $option), 'value' => $option], $field['options']), $required, $disabled),
            'file' => $this->fileDropdown($name, $label, $value, $images, $required, $disabled),
            default => Smart::render('sf-input', ['name' => $name, 'label' => $label, 'value' => $value, 'required' => $required, 'disabled' => $disabled, 'input-type' => 'text', 'type' => 'bordered', 'size' => '1'])->html,
        };
    }

    public function alert(string $key, string $type = 'info'): string
    {
        return Smart::render('sf-alert', ['type' => $type, 'supporting-text' => $this->text($key)])->html;
    }

    /** @param iterable<mixed> $images */
    private function fileDropdown(string $name, string $label, string $value, iterable $images, bool $required, bool $disabled): string
    {
        $options = [['text' => $this->text('no_image'), 'value' => '__none__']];
        foreach ($images as $image) {
            $options[] = ['text' => (string) $image->display_name . ' · ' . (string) $image->mime_type, 'value' => (string) $image->logical_ref];
        }
        return $this->dropdown('', $name, $label, $value === '' ? '__none__' : $value, $options, $required, $disabled);
    }

    /** @param list<array{text:string,value:string}> $options */
    private function dropdown(string $id, string $name, string $label, string $value, array $options, bool $required, bool $disabled): string
    {
        return Smart::render('sf-dropdown', [
            'id' => $id, 'name' => $name, 'label' => $label, 'value' => $value,
            'required' => $required, 'disabled' => $disabled, 'type' => 'outlined', 'size' => '1',
            'options' => array_map(static fn (array $option): array => $option + ['selected' => $option['value'] === $value], $options),
        ])->html;
    }

    private function text(string $key): string { return $this->admin('blocks.' . $key); }
    private function admin(string $key): string { return (string) $this->translator->get('larena-docara::admin.' . $key); }
}
