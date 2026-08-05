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
    'type' => (string) ($props['type'] ?? 'bordered'),
    'label' => (string) ($props['label'] ?? ''),
    'required' => (bool) ($props['required'] ?? false),
    'placeholder' => (string) ($props['placeholder'] ?? ''),
    'hint' => (string) ($props['hint'] ?? ''),
    'value' => (string) ($props['value'] ?? ''),
    'default-value' => (string) ($props['defaultValue'] ?? ''),
    'name' => (string) ($props['name'] ?? ''),
    'left-icon' => (string) ($props['leftIcon'] ?? ''),
    'right-text' => (string) ($props['rightText'] ?? ''),
    'hint-icon' => (string) ($props['hintIcon'] ?? ''),
    'disabled' => (bool) ($props['disabled'] ?? false),
    'readonly' => (bool) ($props['readonly'] ?? false),
    'error' => (bool) ($props['error'] ?? false),
    'mask' => (bool) ($props['mask'] ?? false),
    'mask-pattern' => (string) ($props['maskPattern'] ?? ''),
    'mask-lazy' => (bool) ($props['maskLazy'] ?? false),
    'mask-placeholder-char' => (string) ($props['maskPlaceholderChar'] ?? ''),
    'mask-options' => (string) ($props['maskOptions'] ?? ''),
    'class' => trim((string) ($props['rootClass'] ?? '')),
    'data-sf-smart-id' => (string) ($id ?? ''),
    'data-sf-view' => $viewCode,
    'data-sf-preset' => $presetCode,
    'data-sf-slot' => (string) ($slot ?? ''),
];
?>
<sf-input<?= $attributes($values) ?>><?= $childrenHtml ?></sf-input>
