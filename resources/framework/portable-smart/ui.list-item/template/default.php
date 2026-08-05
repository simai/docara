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
    'type' => (string) ($props['type'] ?? 'text'),
    'size' => (string) ($props['size'] ?? '1'),
    'text' => (string) ($props['text'] ?? ''),
    'icon' => (string) ($props['icon'] ?? 'person'),
    'checked' => (bool) ($props['checked'] ?? false),
    'selected' => (bool) ($props['selected'] ?? false),
    'disabled' => (bool) ($props['disabled'] ?? false),
    'color-class' => (string) ($props['colorClass'] ?? 'bg-tertiary'),
    'avatar-image-url' => (string) ($props['avatarImageUrl'] ?? ''),
    'avatar-title' => (string) ($props['avatarTitle'] ?? ''),
    'aria-label' => (string) ($props['ariaLabel'] ?? ''),
    'data-sf-smart-id' => (string) ($id ?? ''),
    'data-sf-view' => $viewCode,
    'data-sf-preset' => $presetCode,
    'data-sf-slot' => (string) ($slot ?? ''),
];
?>
<sf-list-item<?= $attributes($values) ?>><?= $childrenHtml ?></sf-list-item>
