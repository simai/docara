<?php
declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$attributes = static function (array $values) use ($escape): string {
    $html = '';
    foreach ($values as $name => $value) {
        if ($value === null || $value === false || $value === '') {
            continue;
        }
        $html .= $value === true
            ? ' ' . $name
            : ' ' . $name . '="' . $escape($value) . '"';
    }
    return $html;
};
$viewCode = is_array($view ?? null) ? (string) ($view['code'] ?? '') : '';
$presetCode = is_array($preset ?? null) ? (string) ($preset['code'] ?? '') : '';
$values = [
    'template' => (string) ($props['templateName'] ?? 'default'),
    'size' => (string) ($props['size'] ?? '1'),
    'type' => (string) ($props['type'] ?? 'outlined'),
    'mode' => (string) ($props['mode'] ?? 'select'),
    'multiple' => (bool) ($props['multiple'] ?? false),
    'portal' => (bool) ($props['portal'] ?? false),
    'value' => (string) ($props['value'] ?? ''),
    'name' => (string) ($props['name'] ?? ''),
    'label' => (string) ($props['label'] ?? ''),
    'required' => (bool) ($props['required'] ?? false),
    'placeholder' => (string) ($props['placeholder'] ?? ''),
    'search-placeholder' => (string) ($props['searchPlaceholder'] ?? 'Placeholder'),
    'search' => (bool) ($props['search'] ?? true),
    'disabled' => (bool) ($props['disabled'] ?? false),
    'aria-label' => (string) ($props['ariaLabel'] ?? ''),
    'data-sf-smart-id' => (string) ($id ?? ''),
    'data-sf-view' => $viewCode,
    'data-sf-preset' => $presetCode,
    'data-sf-slot' => (string) ($slot ?? ''),
];
?>
<sf-dropdown<?= $attributes($values) ?>><?= $childrenHtml ?></sf-dropdown>
