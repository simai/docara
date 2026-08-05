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
    'size' => (string) ($props['size'] ?? '1'),
    'label' => (string) ($props['label'] ?? ''),
    'description' => (string) ($props['description'] ?? ''),
    'help' => (string) ($props['help'] ?? ''),
    'position' => (string) ($props['position'] ?? 'start'),
    'class' => trim((string) ($props['rootClass'] ?? '')),
    'checked' => (bool) ($props['checked'] ?? false),
    'disabled' => (bool) ($props['disabled'] ?? false),
    'indeterminate' => (bool) ($props['indeterminate'] ?? false),
    'name' => (string) ($props['name'] ?? ''),
    'value' => (string) ($props['value'] ?? ''),
    'error' => (bool) ($props['error'] ?? false),
    'data-sf-smart-id' => (string) ($id ?? ''),
    'data-sf-view' => $viewCode,
    'data-sf-preset' => $presetCode,
    'data-sf-slot' => (string) ($slot ?? ''),
];
?>
<sf-checkbox<?= $attributes($values) ?>><?= $childrenHtml ?></sf-checkbox>
