<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

use Simai\Docara\Declarative\Rendering\View\PreferencesViewModel;
use Simai\Docara\Smart\Runtime\SmartInvocation;

final class PreferencesContextAdapter implements SmartContextAdapter
{
    private const ICONS = [
        'appearance.theme' => 'contrast',
        'appearance.font_size' => 'format_size',
        'appearance.content_width' => 'width_normal',
    ];

    public function id(): string
    {
        return 'docara.preferences';
    }

    public function prepare(SmartInvocation $invocation): object
    {
        $groups = [];
        $entries = [];
        foreach ($invocation->props['groups'] ?? [] as $group) {
            $fields = [];
            foreach ($group['fields'] as $field) {
                $options = [];
                foreach ($field['options'] as $option) {
                    $options[] = [
                        'value' => $this->escape((string) $option['value']),
                        'title' => $this->escape((string) $option['title']),
                        'description' => $this->escape((string) $option['description']),
                        'selected' => (string) $option['value'] === (string) $field['configured'],
                    ];
                }
                // One row of the Framework sf-context-menu settings panel: a
                // choice that opens its options in the same panel.
                $entries[] = [
                    'id' => (string) $field['id'],
                    'type' => 'choice',
                    'text' => (string) $field['title'],
                    'icon' => self::ICONS[(string) $field['id']] ?? 'tune',
                    'view' => 'drill',
                    'options' => array_map(static fn (array $option): array => array_filter([
                        'id' => (string) $option['value'],
                        'text' => (string) $option['title'],
                        'checked' => (string) $option['value'] === (string) $field['configured'] ? true : null,
                    ], static fn (mixed $value): bool => $value !== null), $field['options']),
                ];
                $fields[] = [
                    'id' => $this->escape((string) $field['id']),
                    'title' => $this->escape((string) $field['title']),
                    'description' => $this->escape((string) $field['description']),
                    'control' => $this->escape((string) $field['control']),
                    'configured' => $this->escape((string) $field['configured']),
                    // Options without descriptions are short labels and sit on one row.
                    'layout' => array_filter($options, static fn (array $option): bool => $option['description'] !== '') === []
                        ? 'inline'
                        : 'list',
                    'options' => $options,
                ];
            }
            $groups[] = [
                'id' => $this->escape((string) $group['id']),
                'title' => $this->escape((string) $group['title']),
                'description' => $this->escape((string) $group['description']),
                'fields' => $fields,
            ];
        }

        if ($entries !== []) {
            $entries[] = ['type' => 'separator'];
            $entries[] = ['id' => 'reset', 'text' => (string) ($invocation->props['reset_label'] ?? ''), 'icon' => 'restart_alt'];
        }

        return new PreferencesViewModel(
            $this->escape((string) ($invocation->props['position'] ?? '')),
            $groups,
            $this->escape((string) ($invocation->props['title'] ?? '')),
            $this->escape((string) ($invocation->props['close_label'] ?? '')),
            $this->escape((string) ($invocation->props['reset_label'] ?? '')),
            json_encode(['entries' => $entries], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
