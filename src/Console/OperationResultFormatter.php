<?php

declare(strict_types=1);

namespace Simai\Docara\Console;

use Simai\Docara\Application\OperationResult;
use Simai\Docara\Portable\CanonicalJson;

final class OperationResultFormatter
{
    public function json(OperationResult $result): string
    {
        return CanonicalJson::encodePretty($result->toArray());
    }

    public function human(OperationResult $result): string
    {
        $value = $result->toArray();
        $subject = is_string($value['subject']) ? $value['subject'] : '';
        $lines = [sprintf('%s: %s%s', strtoupper($result->status), $result->operation, $subject === '' ? '' : ' [' . $subject . ']')];
        if (isset($value['data']['count'])) {
            $lines[] = 'Count: ' . $value['data']['count'];
        }
        foreach ($value['data']['items'] ?? [] as $item) {
            $lines[] = sprintf('- %s:%s (%s)', $item['kind'] ?? 'item', $item['id'] ?? '?', $item['provider'] ?? $item['owner'] ?? 'registered');
        }
        foreach ($value['data']['checks'] ?? [] as $check) {
            $lines[] = sprintf('- %s %s%s', strtoupper((string) ($check['status'] ?? 'unknown')), $check['code'] ?? 'CHECK', isset($check['count']) ? ' (' . $check['count'] . ')' : '');
        }
        if ($value['data'] !== [] && ! isset($value['data']['items']) && ! isset($value['data']['checks']) && ! isset($value['data']['count'])) {
            $lines[] = CanonicalJson::encodePretty($value['data']);
        }
        foreach ($value['diagnostics'] as $diagnostic) {
            $lines[] = sprintf('- %s %s: %s', strtoupper($diagnostic['severity']), $diagnostic['code'], $diagnostic['message']);
            $source = $diagnostic['source'] ?? [];
            if (is_array($source) && is_string($source['path'] ?? null)) {
                $lines[] = '  Source: ' . $source['path'] . (is_string($source['pointer'] ?? null) ? '#' . $source['pointer'] : '');
            }
            if (is_string($diagnostic['owner'] ?? null)) {
                $lines[] = '  Owner: ' . $diagnostic['owner'];
            }
            if (is_array($diagnostic['provenance'] ?? null) && $diagnostic['provenance'] !== []) {
                $lines[] = '  Provenance: ' . CanonicalJson::encode($diagnostic['provenance']);
            }
            if (is_string($diagnostic['suggestion'] ?? null)) {
                $lines[] = '  Suggestion: ' . $diagnostic['suggestion'];
            }
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }
}
