#!/usr/bin/env php
<?php

declare(strict_types=1);

use Simai\Docara\Application\McpAdapter;
use Simai\Docara\Application\SdkServiceFactory;
use Simai\Docara\Portable\CanonicalJson;

$autoload = null;
foreach ([dirname(__DIR__, 2) . '/vendor/autoload.php', dirname(__DIR__, 4) . '/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if (! is_string($autoload)) {
    fwrite(STDERR, "MCP_AUTOLOAD_MISSING\n");
    exit(1);
}
require $autoload;

$root = getcwd() ?: '.';
$allowWrites = in_array('--allow-writes', $argv, true);
$adapter = new McpAdapter(SdkServiceFactory::create(), $root, $allowWrites);

while (($line = fgets(STDIN)) !== false) {
    try {
        $request = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($request)) {
            throw new RuntimeException('MCP_REQUEST_INVALID');
        }
        $response = $adapter->handle($request);
        if ($response !== []) {
            fwrite(STDOUT, CanonicalJson::encode($response) . "\n");
        }
    } catch (Throwable $exception) {
        fwrite(STDOUT, CanonicalJson::encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'MCP_PARSE_ERROR']]) . "\n");
    }
}
