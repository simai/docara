<?php

declare(strict_types=1);

namespace Simai\Docara\Smart\Runtime\Context;

use Simai\Docara\Declarative\Rendering\View\PreferencesViewModel;
use Simai\Docara\Smart\Runtime\SmartInvocation;

final class PreferencesContextAdapter implements SmartContextAdapter
{
    public function id(): string
    {
        return 'docara.preferences';
    }

    public function prepare(SmartInvocation $invocation): object
    {
        $groups = [];
        foreach ($invocation->props['groups'] ?? [] as $group) {
            $fields = [];
            foreach ($group['fields'] as $field) {
                $options = [];
                foreach ($field['options'] as $option) {
                    $options[] = [
                        'value' => $this->escape((string) $option['value']),
                        'title' => $this->escape((string) $option['title']),
                        'description' => $this->escape((string) $option['description']),
                    ];
                }
                $fields[] = [
                    'id' => $this->escape((string) $field['id']),
                    'title' => $this->escape((string) $field['title']),
                    'description' => $this->escape((string) $field['description']),
                    'control' => $this->escape((string) $field['control']),
                    'configured' => $this->escape((string) $field['configured']),
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

        return new PreferencesViewModel(
            $this->escape((string) ($invocation->props['position'] ?? '')),
            $groups,
            $this->escape((string) ($invocation->props['title'] ?? '')),
            $this->escape((string) ($invocation->props['close_label'] ?? '')),
            $this->escape((string) ($invocation->props['reset_label'] ?? '')),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
