<?php

declare(strict_types=1);

namespace Larena\Docara\Admin;

use Illuminate\Contracts\Translation\Translator;
use Larena\Ui\Smart;

final readonly class DocumentationMenuFormPresenter
{
    public function __construct(private Translator $translator) {}

    /** @param array<string,string> $values @param array<string,string> $errors */
    public function create(array $values, array $errors): array
    {
        return [
            'validation' => $this->validation($errors),
            'name' => $this->input('menu-name', 'name', 'name', $values['name'], true, $errors),
            'code' => $this->input('menu-code', 'code', 'code', $values['code'], true, $errors, $this->text('fields.code_help')),
            'locale' => $this->dropdown('menu-locale', 'locale', 'locale', $values['locale'], [['text' => 'English', 'value' => 'en'], ['text' => 'Русский', 'value' => 'ru']], true),
            'active' => $this->checkbox('is_active', $values['is_active'] === '1'),
            'submit' => $this->button('actions.create', 'primary'),
        ];
    }

    /** @param array<string,string> $errors */
    public function settings(string $name, bool $active, array $errors): array
    {
        return [
            'name' => $this->input('menu-name', 'name', 'name', $name, true, $errors),
            'active' => $this->checkbox('is_active', $active),
            'submit' => $this->button('actions.save', 'primary'),
            'delete' => $this->button('actions.delete', 'danger'),
        ];
    }

    /** @param list<array{text:string,value:string}> $parents */
    public function item(int $id, string $label, ?int $parentId, int $order, bool $active, array $parents): array
    {
        return [
            'label' => $this->input("item-{$id}-label", 'label', 'label', $label, true, []),
            'parent' => $this->dropdown("item-{$id}-parent", 'parent_id', 'parent', $parentId === null ? '__root__' : (string) $parentId, $parents, false),
            'order' => $this->number("item-{$id}-order", $order),
            'active' => $this->checkbox('is_active', $active),
            'save' => $this->button('actions.save_item', 'secondary'),
            'remove' => $this->button('actions.remove_item', 'danger'),
        ];
    }

    /** @param list<array{text:string,value:string}> $pages @param list<array{text:string,value:string}> $parents */
    public function add(array $pages, array $parents, array $old): array
    {
        return [
            'page' => $this->dropdown('new-page', 'page_ref', 'page', (string) ($old['page_ref'] ?? ''), $pages, true),
            'label' => $this->input('new-label', 'label', 'label', (string) ($old['label'] ?? ''), true, []),
            'parent' => $this->dropdown('new-parent', 'parent_id', 'parent', (string) ($old['parent_id'] ?? '__root__'), $parents, false),
            'order' => $this->number('new-order', (int) ($old['sort_order'] ?? 100)),
            'active' => $this->checkbox('is_active', (string) ($old['is_active'] ?? '1') === '1'),
            'submit' => $this->button('actions.add_item', 'primary'),
        ];
    }

    public function alert(string $key, string $type = 'info'): string
    {
        return Smart::render('sf-alert', ['type' => $type, 'supporting-text' => $this->text($key)])->html;
    }

    /** @param array<string,string> $errors */
    private function validation(array $errors): string
    {
        return $errors === [] ? '' : Smart::render('sf-alert', ['type' => 'danger', 'title' => $this->text('validation_heading'), 'supporting-text' => implode(' ', array_values($errors))])->html;
    }

    /** @param array<string,string> $errors */
    private function input(string $id, string $name, string $label, string $value, bool $required, array $errors, string $hint = ''): string
    {
        return Smart::render('sf-input', ['id' => $id, 'name' => $name, 'label' => $this->text('fields.' . $label), 'value' => $value, 'required' => $required, 'type' => 'bordered', 'size' => '1', 'error' => isset($errors[$name]), 'hint' => $errors[$name] ?? $hint])->html;
    }

    private function number(string $id, int $value): string
    {
        return Smart::render('sf-input', ['id' => $id, 'name' => 'sort_order', 'label' => $this->text('fields.order'), 'value' => (string) $value, 'required' => true, 'input-type' => 'number', 'min' => 0, 'max' => 100000, 'type' => 'bordered', 'size' => '1'])->html;
    }

    /** @param list<array{text:string,value:string}> $options */
    private function dropdown(string $id, string $name, string $label, string $value, array $options, bool $required): string
    {
        return Smart::render('sf-dropdown', ['id' => $id, 'name' => $name, 'label' => $this->text('fields.' . $label), 'value' => $value, 'required' => $required, 'type' => 'outlined', 'size' => '1', 'options' => array_map(static fn (array $option): array => $option + ['selected' => $option['value'] === $value], $options)])->html;
    }

    private function checkbox(string $name, bool $checked): string
    {
        return Smart::render('sf-checkbox', ['name' => $name, 'value' => '1', 'label' => $this->text('fields.active'), 'checked' => $checked, 'size' => '1'])->html;
    }

    private function button(string $key, string $scheme): string
    {
        return Smart::render('sf-button', ['text' => $this->text($key), 'type' => $scheme === 'danger' ? 'tonal' : 'default', 'scheme' => $scheme, 'native-type' => 'submit'])->html;
    }

    private function text(string $key): string
    {
        return (string) $this->translator->get('larena-docara::admin.menus.' . $key);
    }
}
