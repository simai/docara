#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Simai\Docara\Release\AiContractReleaseGate;

$options = getopt('', ['previous:', 'current:', 'skill-dna:', 'federation-lock:', 'skill-revision:']);
if (! is_array($options) || count($options) !== 5) {
    fwrite(STDERR, "Usage: php scripts/verify-ai-contract-release.php --previous=<capabilities.json> --current=<capabilities.json> --skill-dna=<skill-dna.json> --federation-lock=<stable.json> --skill-revision=<40-char-sha>\n");
    exit(2);
}

try {
    $read = static function (string $path): array {
        if (! is_file($path) || is_link($path)) {
            throw new RuntimeException('AI_RELEASE_INPUT_UNSAFE:Release gate input is missing or unsafe.');
        }
        $value = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($value)) {
            throw new RuntimeException('AI_RELEASE_INPUT_INVALID:Release gate input must be a JSON object.');
        }

        return $value;
    };
    $result = (new AiContractReleaseGate)->verify(
        $read((string) $options['previous']),
        $read((string) $options['current']),
        $read((string) $options['skill-dna']),
        $read((string) $options['federation-lock']),
        (string) $options['skill-revision'],
    );
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
