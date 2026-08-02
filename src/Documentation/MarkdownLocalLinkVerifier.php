<?php

declare(strict_types=1);

namespace Simai\Docara\Documentation;

use RuntimeException;

final class MarkdownLocalLinkVerifier
{
    /** @param array<string, string> $files */
    public function verify(array $files): void
    {
        $normalized = [];
        foreach ($files as $path => $contents) {
            $path = $this->normalizePath($path);
            if ($path === '' || str_starts_with($path, '../')) {
                throw new RuntimeException("Documentation inventory contains an unsafe path [{$path}].");
            }
            $normalized[$path] = $contents;
        }

        foreach ($normalized as $source => $markdown) {
            if (! str_ends_with(strtolower($source), '.md')) {
                continue;
            }
            if (str_starts_with($source, 'docs/site/content/')) {
                // These links are public route references verified against the built site.
                continue;
            }
            preg_match_all('/!?\[[^\]]*\]\(([^)\s]+)(?:\s+["\'][^"\']*["\'])?\)/u', $this->withoutFencedCode($markdown), $matches);
            foreach ($matches[1] as $rawTarget) {
                $target = html_entity_decode(trim((string) $rawTarget, '<>'), ENT_QUOTES | ENT_HTML5);
                if ($target === '' || str_starts_with($target, '#') || str_starts_with($target, '/')) {
                    continue;
                }
                if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $target) === 1) {
                    continue;
                }

                $target = rawurldecode((string) preg_replace('/[?#].*$/', '', str_replace('\\', '/', $target)));
                $resolved = $this->normalizePath(($source === basename($source) ? '' : dirname($source) . '/') . $target);
                if ($resolved === '' || str_starts_with($resolved, '../')) {
                    throw new RuntimeException("Documentation link escapes its root [{$source} -> {$rawTarget}].");
                }
                if (isset($normalized[$resolved]) || $this->directoryExists($resolved, $normalized)) {
                    continue;
                }

                throw new RuntimeException("Broken local documentation link [{$source} -> {$rawTarget}] resolved as [{$resolved}].");
            }
        }
    }

    /** @param array<string, string> $files */
    private function directoryExists(string $target, array $files): bool
    {
        $prefix = rtrim($target, '/') . '/';
        foreach (array_keys($files) as $path) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === [] || end($segments) === '..') {
                    $segments[] = '..';
                } else {
                    array_pop($segments);
                }

                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function withoutFencedCode(string $markdown): string
    {
        return preg_replace('/^(?:`{3,}|~{3,}).*?^(?:`{3,}|~{3,})\h*$/ms', '', $markdown) ?? $markdown;
    }
}
