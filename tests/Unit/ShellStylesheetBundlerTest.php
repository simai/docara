<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\ShellStylesheetBundler;

final class ShellStylesheetBundlerTest extends TestCase
{
    public function test_pages_link_few_shared_stylesheets_that_keep_each_page_cascade(): void
    {
        [$pages, $bytes, $conflict] = $this->site(seed: 302, pages: 240);
        $bundler = new ShellStylesheetBundler($conflict);

        $result = $bundler->bundle($pages, $bytes);

        $distinct = array_sum($bytes);
        self::assertLessThanOrEqual(ShellStylesheetBundler::BYTE_BUDGET_FACTOR * $distinct, $result['bytes']);
        $published = 0;
        foreach ($result['bundles'] as $segments) {
            $published += array_sum(array_map(static fn (string $id): int => $bytes[$id], $segments));
        }
        self::assertSame($result['bytes'], $published);
        $requests = [];
        $users = [];
        foreach ($pages as $page => $closure) {
            $linked = [];
            foreach ($result['pages'][$page] as $bundle) {
                array_push($linked, ...$result['bundles'][$bundle]);
                $users[$bundle][$page] = true;
            }
            // Exactly the page's segments, nothing more, and every pair that
            // may not change places in the page's own order.
            self::assertEqualsCanonicalizing($closure, $linked, $page);
            $position = array_flip($linked);
            foreach ($closure as $i => $a) {
                foreach (array_slice($closure, $i + 1) as $b) {
                    if ($conflict($a, $b)) {
                        self::assertLessThan($position[$b], $position[$a], "$page reorders $a and $b");
                    }
                }
            }
            $requests[] = count($result['pages'][$page]);
        }
        $average = array_sum($requests) / count($requests);
        $closureAverage = array_sum(array_map('count', $pages)) / count($pages);
        self::assertLessThan($closureAverage / 4, $average);
        self::assertGreaterThan(
            count($pages) / 2,
            max(array_map('count', $users)),
            'the common segments are one stylesheet shared by most pages',
        );
    }

    public function test_segments_that_commute_gather_into_a_shared_core(): void
    {
        $bytes = [];
        $core = [];
        for ($i = 0; $i < 30; $i++) {
            $core[] = $id = sprintf('core-%02d', $i);
            $bytes[$id] = 20000 + $i;
        }
        $pages = [];
        for ($page = 0; $page < 100; $page++) {
            $extra = sprintf('extra-%d', $page % 10);
            $bytes[$extra] = 3000;
            $closure = $core;
            // The page's own segment sits in the middle of the core.
            array_splice($closure, 10 + $page % 7, 0, [$extra]);
            $pages[sprintf('page-%03d.html', $page)] = $closure;
        }

        $result = (new ShellStylesheetBundler(static fn (): bool => false))->bundle($pages, $bytes);

        foreach ($result['pages'] as $page => $links) {
            self::assertLessThanOrEqual(2, count($links), $page);
        }
        self::assertCount(11, $result['bundles']);
        self::assertLessThanOrEqual(array_sum($bytes) * ShellStylesheetBundler::BYTE_BUDGET_FACTOR, $result['bytes']);
    }

    public function test_the_plan_does_not_depend_on_the_order_pages_are_given_in(): void
    {
        [$pages, $bytes, $conflict] = $this->site(seed: 7, pages: 60);
        $shuffled = $pages;
        $keys = array_keys($shuffled);
        mt_srand(11);
        shuffle($keys);
        $shuffled = array_combine($keys, array_map(static fn (string $key): array => $pages[$key], $keys));

        self::assertSame(
            (new ShellStylesheetBundler($conflict))->bundle($pages, $bytes),
            (new ShellStylesheetBundler($conflict))->bundle($shuffled, $bytes),
        );
    }

    public function test_a_page_that_conflicts_everywhere_keeps_its_exact_order(): void
    {
        $pages = ['a.html' => ['x', 'y', 'z'], 'b.html' => ['z', 'y', 'x'], 'c.html' => ['y', 'x']];
        $bytes = ['x' => 100, 'y' => 200, 'z' => 300];
        $bundler = new ShellStylesheetBundler(static fn (): bool => true);

        $result = $bundler->bundle($pages, $bytes);

        foreach ($pages as $page => $closure) {
            $linked = array_merge(...array_map(
                static fn (string $bundle): array => $result['bundles'][$bundle],
                $result['pages'][$page],
            ));
            self::assertSame($closure, $linked, $page);
        }
        self::assertFalse($bundler->preservesCascade(['x', 'y'], ['y', 'x']));
        self::assertFalse($bundler->preservesCascade(['x', 'y'], ['x']));
        self::assertFalse($bundler->preservesCascade(['x', 'y'], ['x', 'y', 'z']));
        self::assertTrue((new ShellStylesheetBundler(static fn (): bool => false))->preservesCascade(['x', 'y'], ['y', 'x']));
    }

    public function test_a_single_page_rebuild_reuses_the_published_stylesheets(): void
    {
        $conflict = static fn (string $a, string $b): bool => in_array([$a, $b], [['a', 'd'], ['d', 'a']], true);
        $bundler = new ShellStylesheetBundler($conflict);
        $bytes = ['a' => 10, 'b' => 20, 'c' => 30, 'd' => 40, 'e' => 50];

        $links = $bundler->cover(['d', 'a', 'b', 'c', 'e'], [['a', 'b', 'c'], ['b', 'c'], ['x', 'e']], $bytes);

        self::assertContains(['b', 'c'], $links, 'an existing stylesheet is reused');
        self::assertNotContains(['x', 'e'], $links, 'a stylesheet holding a foreign segment is not');
        self::assertLessThanOrEqual(3, count($links));
        self::assertTrue($bundler->preservesCascade(['d', 'a', 'b', 'c', 'e'], array_merge(...$links)));
        // [a, b, c] may not move before d, which the page orders first.
        self::assertFalse($bundler->preservesCascade(['d', 'a', 'b', 'c', 'e'], ['a', 'b', 'c', 'd', 'e']));
    }

    public function test_it_rejects_a_closure_that_names_a_segment_twice(): void
    {
        $this->expectException(FrameworkComponentException::class);

        (new ShellStylesheetBundler(static fn (): bool => false))->bundle(['a.html' => ['x', 'x']], ['x' => 1]);
    }

    /**
     * A synthetic site shaped like a documentation site: a large common core
     * in a usual order, a few frequent components, and rare page-specific
     * segments, some of which conflict with the core.
     *
     * @return array{0: array<string, list<string>>, 1: array<string, int>, 2: \Closure(string, string): bool}
     */
    private function site(int $seed, int $pages): array
    {
        mt_srand($seed);
        $bytes = [];
        $core = [];
        for ($i = 0; $i < 40; $i++) {
            $core[] = $id = sprintf('core-%02d', $i);
            $bytes[$id] = mt_rand(200, 60000);
        }
        $frequent = [];
        for ($i = 0; $i < 4; $i++) {
            $frequent[] = $id = sprintf('frequent-%02d', $i);
            $bytes[$id] = mt_rand(1000, 40000);
        }
        $rare = [];
        for ($i = 0; $i < 120; $i++) {
            $rare[] = $id = sprintf('rare-%03d', $i);
            $bytes[$id] = mt_rand(100, 30000);
        }
        $conflicts = [];
        foreach ([...$frequent, ...$rare] as $id) {
            foreach ((array) array_rand(array_flip($core), 2) as $other) {
                if (mt_rand(0, 3) === 0) {
                    $conflicts[$id][$other] = $conflicts[$other][$id] = true;
                }
            }
        }
        $conflict = static fn (string $a, string $b): bool => isset($conflicts[$a][$b]);
        // Like the Framework registry, every segment has its usual place.
        $place = [];
        foreach ([...$frequent, ...$rare] as $id) {
            $place[$id] = mt_rand(0, count($core) * 10) / 10;
        }
        foreach ($core as $offset => $id) {
            $place[$id] = $offset;
        }
        $site = [];
        for ($page = 0; $page < $pages; $page++) {
            $closure = $core;
            foreach ($frequent as $id) {
                if (mt_rand(0, 1) === 1) {
                    $closure[] = $id;
                }
            }
            for ($i = mt_rand(0, 2); $i > 0; $i--) {
                $closure[] = $rare[mt_rand(0, count($rare) - 1)];
            }
            $closure = array_values(array_unique($closure));
            usort($closure, static fn (string $a, string $b): int => [$place[$a], $a] <=> [$place[$b], $b]);
            $site[sprintf('page-%03d.html', $page)] = $closure;
        }

        return [$site, $bytes, $conflict];
    }
}
