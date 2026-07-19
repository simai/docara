<?php

/*
 * Read-only router for Docara's local PHP preview server.
 *
 * The router serves every response itself instead of delegating to the PHP
 * built-in server. This is intentional: returning false here could execute a
 * PHP file accidentally copied into a generated site.
 */

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

$respond = static function (int $status, string $body, string $contentType = 'text/plain; charset=UTF-8') use ($method): bool {
    http_response_code($status);
    header("Content-Type: {$contentType}");
    header('Content-Length: ' . strlen($body));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');

    if ($method !== 'HEAD') {
        echo $body;
    }

    return true;
};

if (! in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');

    return $respond(405, "Method Not Allowed\n");
}

$documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
if ($documentRoot === false || ! is_dir($documentRoot)) {
    return $respond(500, "Preview root is unavailable\n");
}

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (! is_string($requestPath)) {
    return $respond(404, "Not Found\n");
}

$requestPath = rawurldecode($requestPath);
if (
    $requestPath === ''
    || $requestPath[0] !== '/'
    || str_contains($requestPath, '\\')
    || str_contains($requestPath, "\0")
    || str_contains($requestPath, '//')
    || preg_match('/[\x00-\x1F\x7F]/', $requestPath) === 1
) {
    return $respond(404, "Not Found\n");
}

$trimmedPath = trim($requestPath, '/');
$segments = $trimmedPath === '' ? [] : explode('/', $trimmedPath);

foreach ($segments as $segment) {
    if ($segment === '' || $segment === '.' || $segment === '..') {
        return $respond(404, "Not Found\n");
    }
}

$candidate = $documentRoot;
if ($segments !== []) {
    $candidate .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
}

if ($segments === [] || str_ends_with($requestPath, '/') || is_dir($candidate)) {
    $candidate = rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
}

$resolvedPath = realpath($candidate);
$rootPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (
    $resolvedPath === false
    || ! is_file($resolvedPath)
    || ! is_readable($resolvedPath)
    || ! str_starts_with($resolvedPath, $rootPrefix)
) {
    return $respond(404, "Not Found\n");
}

$filename = basename($resolvedPath);
if (preg_match('/\.(?:php(?:[0-9]+)?|phtml|phar)(?:\.|$)/i', $filename) === 1) {
    return $respond(404, "Not Found\n");
}

$extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
$contentTypes = [
    'avif' => 'image/avif',
    'css' => 'text/css; charset=UTF-8',
    'gif' => 'image/gif',
    'html' => 'text/html; charset=UTF-8',
    'ico' => 'image/x-icon',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'js' => 'text/javascript; charset=UTF-8',
    'json' => 'application/json; charset=UTF-8',
    'map' => 'application/json; charset=UTF-8',
    'png' => 'image/png',
    'svg' => 'image/svg+xml; charset=UTF-8',
    'txt' => 'text/plain; charset=UTF-8',
    'wasm' => 'application/wasm',
    'webp' => 'image/webp',
    'xml' => 'application/xml; charset=UTF-8',
];

$size = filesize($resolvedPath);
http_response_code(200);
header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
if ($size !== false) {
    header("Content-Length: {$size}");
}
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($method !== 'HEAD') {
    readfile($resolvedPath);
}

return true;
