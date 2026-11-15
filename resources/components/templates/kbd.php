<?php
// The SIMAI Framework key (component.kbd). A key of one sign (⌘, K, ↵) is
// drawn square, as the Framework asks of such keys; Esc or Enter is not.
$sign = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$signs = function_exists('grapheme_strlen') ? grapheme_strlen($sign) : mb_strlen($sign, 'UTF-8');
$classes = $signs === 1 ? 'sf-kbd sf-kbd--square' : 'sf-kbd';
?><kbd class="<?= $classes ?>"><?= $label ?></kbd>
