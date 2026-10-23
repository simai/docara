<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

/**
 * Resolves a link of a built page to a file of the build, by the same rules
 * as `docara verify-static` (scripts/verify-static-build.php): a path is
 * relative to the page or, when it starts with "/", to the deployment base;
 * a directory or a path ending in "/" means its index.html. External links
 * (a scheme or "//") are not resolved: the report works offline.
 */
final readonly class StaticLinkResolver
{
    public function __construct(private string $deploymentBase = '/') {}

    /**
     * kind is "external", "local" (target is the build-relative file) or
     * "outside" (above the build root or the deployment base).
     *
     * @return array{kind:string,target:?string,fragment:?string}
     */
    public function resolve(string $page, string $reference): array
    {
        if ($reference === '' || str_starts_with($reference, '//') || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $reference) === 1) {
            return ['kind' => 'external', 'target' => null, 'fragment' => null];
        }
        $hash = strpos($reference, '#');
        $fragment = $hash === false ? null : rawurldecode(substr($reference, $hash + 1));
        $path = rawurldecode(preg_split('/[?#]/', $reference, 2)[0] ?? '');
        if ($path === '') {
            $candidate = $page;
        } elseif (str_starts_with($path, '/')) {
            $base = rtrim($this->deploymentBase, '/') . '/';
            if ($base === '/') {
                $candidate = ltrim($path, '/');
            } elseif ($path . '/' === $base) {
                $candidate = '';
            } elseif (str_starts_with($path, $base)) {
                $candidate = substr($path, strlen($base));
            } else {
                return ['kind' => 'outside', 'target' => null, 'fragment' => $fragment];
            }
        } else {
            $candidate = dirname($page) . '/' . $path;
        }
        $segments = [];
        foreach (explode('/', $candidate) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    return ['kind' => 'outside', 'target' => null, 'fragment' => $fragment];
                }
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }
        $target = implode('/', $segments);
        if ($path !== '' && ($target === '' || str_ends_with($path, '/'))) {
            $target = ltrim($target . '/index.html', '/');
        }

        return ['kind' => 'local', 'target' => $target, 'fragment' => $fragment === '' ? null : $fragment];
    }

    /** The public URL of a build-relative HTML file: ru/start/index.html → /ru/start/. */
    public function url(string $file): string
    {
        $base = rtrim($this->deploymentBase, '/') . '/';
        $path = preg_replace('#(?:^|/)index\.html$#', '', $file) ?? $file;

        return $base . ($path === '' ? '' : $path . (str_ends_with($path, '.html') ? '' : '/'));
    }
}
