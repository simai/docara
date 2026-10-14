<?php

declare(strict_types=1);

namespace Simai\Docara\Framework;

/**
 * Groups the first-frame stylesheet segments of a whole site into a few
 * shared, content-addressed bundles per page.
 *
 * Every page needs an exact ordered closure of segments. A bundle is a run of
 * segments published as one file; a page links bundles whose concatenation is
 * its closure, reordered only where {@see ShellStylesheetCascade} proves the
 * cascade cannot change. No page ever links a segment it does not need.
 *
 * 1. Each page's closure is rewritten into a canonical order: segments that
 *    many pages use come first, while every pair of conflicting segments keeps
 *    the page's relative order. Pages then share long identical runs.
 * 2. Runs are merged into bundles greedily, pair by pair (as in byte-pair
 *    encoding): a merge is taken when the requests it saves, weighted by the
 *    pages that make them, outweigh the bytes of the new bundle. Pages above
 *    the request target weigh more.
 * 3. The weight of a request is lowered step by step until the published
 *    bytes fit the budget: at most twice the bytes of the distinct segments.
 */
final class ShellStylesheetBundler
{
    public const TARGET_REQUESTS = 6;

    public const BYTE_BUDGET_FACTOR = 2;

    /** Pages above the target count this many times when weighing a merge. */
    private const OVER_TARGET_WEIGHT = 4;

    /** Bytes one saved request is worth, tried from the most to the least eager. */
    private const REQUEST_WEIGHTS = [
        65536, 46341, 32768, 23170, 16384, 11585, 8192, 5793, 4096, 2896, 2048, 1448,
        1024, 724, 512, 362, 256, 181, 128, 91, 64, 45, 32, 23, 16, 11, 8, 6, 4, 3, 2, 1,
    ];

    /** @var list<string> */
    private array $ids = [];

    /** @var list<int> */
    private array $bytes = [];

    /** @var array<int, array<int, bool>> */
    private array $conflicts = [];

    /** @var \Closure(string, string): bool */
    private \Closure $conflict;

    /**
     * @param  callable(string, string): bool  $conflict  whether two segments
     *                                                    may not change places
     */
    public function __construct(callable $conflict)
    {
        $this->conflict = \Closure::fromCallable($conflict);
    }

    /**
     * Plan the stylesheets of a whole site. Returns the bundles (bundle key
     * => segment ids) and each page's bundle keys in link order.
     *
     * @param  array<string, list<string>>  $pages  page => ordered segment ids
     * @param  array<string, int>  $bytes  segment id => byte length
     * @return array{bundles: array<string, list<string>>, pages: array<string, list<string>>, request_weight: int, budget: int, bytes: int}
     */
    public function bundle(array $pages, array $bytes): array
    {
        ksort($pages, SORT_STRING);
        $this->ids = [];
        $this->bytes = [];
        $this->conflicts = [];
        $index = [];
        $frequency = [];
        $position = [];
        foreach ($pages as $page => $closure) {
            $count = count($closure);
            if ($count === 0 || count(array_unique($closure)) !== $count) {
                throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_INVALID', (string) $page);
            }
            foreach ($closure as $offset => $id) {
                if (! isset($index[$id])) {
                    if (! isset($bytes[$id])) {
                        throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_INVALID', $id);
                    }
                    $index[$id] = count($this->ids);
                    $this->ids[] = $id;
                    $this->bytes[] = $bytes[$id];
                    $frequency[$id] = 0;
                    $position[$id] = 0;
                }
                $frequency[$id]++;
                $position[$id] += intdiv($offset * 1000000, $count);
            }
        }
        // Rank: segments on more pages first, then their usual place.
        $order = $this->ids;
        usort($order, static function (string $a, string $b) use ($frequency, $position): int {
            return [$frequency[$b], intdiv($position[$a], $frequency[$a]), $a]
                <=> [$frequency[$a], intdiv($position[$b], $frequency[$b]), $b];
        });
        $rank = [];
        foreach ($order as $offset => $id) {
            $rank[$index[$id]] = $offset;
        }

        // Distinct canonical sequences with the number of pages using each.
        $sequences = [];
        $weights = [];
        $pageSequence = [];
        foreach ($pages as $page => $closure) {
            $canonical = $this->canonical(array_map(static fn (string $id): int => $index[$id], $closure), $rank);
            $key = implode(',', $canonical);
            if (! isset($sequences[$key])) {
                $sequences[$key] = $canonical;
                $weights[$key] = 0;
            }
            $weights[$key]++;
            $pageSequence[$page] = $key;
        }

        $distinctBytes = array_sum($this->bytes);
        $budget = self::BYTE_BUDGET_FACTOR * $distinctBytes;
        // Fewer requests need a higher weight; find the highest weight whose
        // bundles fit the budget (a weight of zero publishes every segment
        // alone, which always fits).
        $best = null;
        $bestWeight = 0;
        $low = 0;
        $high = count(self::REQUEST_WEIGHTS) - 1;
        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            $weight = self::REQUEST_WEIGHTS[$middle];
            $tokens = $this->merge($sequences, $weights, $weight);
            if ($this->publishedBytes($tokens) <= $budget) {
                $best = $tokens;
                $bestWeight = $weight;
                $high = $middle - 1;
            } else {
                $low = $middle + 1;
            }
        }
        if ($best === null) {
            $best = [];
            foreach ($sequences as $key => $sequence) {
                $best[$key] = array_map(static fn (int $segment): array => [$segment], $sequence);
            }
        }

        $bundles = [];
        $pageBundles = [];
        foreach ($pageSequence as $page => $key) {
            $links = [];
            foreach ($best[$key] as $token) {
                $bundleKey = implode("\n", array_map(fn (int $segment): string => $this->ids[$segment], $token));
                $bundles[$bundleKey] ??= array_map(fn (int $segment): string => $this->ids[$segment], $token);
                $links[] = $bundleKey;
            }
            $linked = array_merge(...array_map(static fn (string $bundle): array => $bundles[$bundle], $links));
            if (! $this->preservesCascade($pages[$page], $linked)) {
                throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_ORDER_INVALID', (string) $page);
            }
            $pageBundles[$page] = $links;
        }
        ksort($bundles, SORT_STRING);

        return [
            'bundles' => $bundles,
            'pages' => $pageBundles,
            'request_weight' => $bestWeight,
            'budget' => $budget,
            'bytes' => $this->publishedBytes($best),
        ];
    }

    /**
     * Cover one page's closure with existing bundles where they fit and new
     * bundles for the rest, without re-planning other pages. Used by
     * single-page rebuilds; a complete build re-plans the whole site.
     *
     * @param  list<string>  $closure
     * @param  list<list<string>>  $existing
     * @return list<list<string>>
     */
    public function cover(array $closure, array $existing, array $bytes): array
    {
        $this->ids = array_values($closure);
        $this->bytes = array_map(static fn (string $id): int => $bytes[$id] ?? throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_INVALID', $id), $this->ids);
        $this->conflicts = [];
        $count = count($closure);
        $position = array_flip($this->ids);
        if (count($position) !== $count || $count === 0) {
            throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_INVALID', 'closure');
        }
        $predecessors = array_fill(0, $count, []);
        for ($j = 0; $j < $count; $j++) {
            for ($i = 0; $i < $j; $i++) {
                if ($this->conflicting($i, $j)) {
                    $predecessors[$j][] = $i;
                }
            }
        }
        $candidates = [];
        foreach ($existing as $bundle) {
            $members = [];
            foreach ($bundle as $id) {
                if (! isset($position[$id])) {
                    continue 2;
                }
                $members[] = $position[$id];
            }
            if ($members !== []) {
                $candidates[implode(',', $members)] = $members;
            }
        }
        $candidates = array_values($candidates);
        usort($candidates, function (array $a, array $b): int {
            return [$this->tokenBytes($b), count($b), $a] <=> [$this->tokenBytes($a), count($a), $b];
        });

        $done = [];
        $result = [];
        $run = [];
        $ready = static function (int $segment, array $done) use ($predecessors): bool {
            foreach ($predecessors[$segment] as $predecessor) {
                if (! isset($done[$predecessor])) {
                    return false;
                }
            }

            return true;
        };
        while (count($done) < $count) {
            $chosen = null;
            foreach ($candidates as $candidate) {
                $trial = $done;
                foreach ($candidate as $segment) {
                    if (isset($trial[$segment]) || ! $ready($segment, $trial)) {
                        continue 2;
                    }
                    $trial[$segment] = true;
                }
                $chosen = $candidate;
                break;
            }
            if ($chosen !== null) {
                if ($run !== []) {
                    $result[] = $run;
                    $run = [];
                }
                $result[] = $chosen;
                foreach ($chosen as $segment) {
                    $done[$segment] = true;
                }

                continue;
            }
            for ($segment = 0; $segment < $count; $segment++) {
                if (! isset($done[$segment]) && $ready($segment, $done)) {
                    $run[] = $segment;
                    $done[$segment] = true;
                    break;
                }
            }
        }
        if ($run !== []) {
            $result[] = $run;
        }
        $links = array_map(fn (array $token): array => array_map(fn (int $segment): string => $this->ids[$segment], $token), $result);
        if (! $this->preservesCascade($closure, array_merge(...$links))) {
            throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_ORDER_INVALID', 'cover');
        }

        return $links;
    }

    /**
     * Whether $linked holds exactly the segments of $closure and keeps every
     * pair that may not change places in the closure's order.
     *
     * @param  list<string>  $closure
     * @param  list<string>  $linked
     */
    public function preservesCascade(array $closure, array $linked): bool
    {
        if (count($closure) !== count($linked)) {
            return false;
        }
        $position = array_flip($linked);
        if (count($position) !== count($linked)) {
            return false;
        }
        foreach ($closure as $id) {
            if (! isset($position[$id])) {
                return false;
            }
        }
        $count = count($closure);
        for ($j = 0; $j < $count; $j++) {
            for ($i = 0; $i < $j; $i++) {
                if ($position[$closure[$i]] > $position[$closure[$j]]
                    && ($this->conflict)($closure[$i], $closure[$j])
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Canonical order of one closure: a topological order of its conflict
     * graph that emits a segment as soon as the best-ranked segment waiting
     * for it is wanted.
     *
     * @param  list<int>  $closure
     * @param  array<int, int>  $rank
     * @return list<int>
     */
    private function canonical(array $closure, array $rank): array
    {
        $count = count($closure);
        $successors = array_fill(0, $count, []);
        $waiting = array_fill(0, $count, 0);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($this->conflicting($closure[$i], $closure[$j])) {
                    $successors[$i][] = $j;
                    $waiting[$j]++;
                }
            }
        }
        $urgency = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $urgency[$i] = $rank[$closure[$i]];
            foreach ($successors[$i] as $j) {
                $urgency[$i] = min($urgency[$i], $urgency[$j]);
            }
        }
        $available = [];
        for ($i = 0; $i < $count; $i++) {
            if ($waiting[$i] === 0) {
                $available[$i] = true;
            }
        }
        $result = [];
        while ($available !== []) {
            $next = null;
            foreach ($available as $i => $_) {
                if ($next === null
                    || [$urgency[$i], $rank[$closure[$i]]] < [$urgency[$next], $rank[$closure[$next]]]
                ) {
                    $next = $i;
                }
            }
            unset($available[$next]);
            $result[] = $closure[$next];
            foreach ($successors[$next] as $j) {
                if (--$waiting[$j] === 0) {
                    $available[$j] = true;
                }
            }
        }
        if (count($result) !== $count) {
            throw new FrameworkComponentException('FRAMEWORK_SHELL_CSS_ORDER_INVALID', 'canonical');
        }

        return $result;
    }

    /**
     * Merge adjacent tokens while a merge saves more weighted requests than
     * the bytes it adds.
     *
     * @param  array<string, list<int>>  $sequences
     * @param  array<string, int>  $weights
     * @return array<string, list<list<int>>>
     */
    private function merge(array $sequences, array $weights, int $requestWeight): array
    {
        $tokens = [];
        $tokenBytes = [];
        foreach ($sequences as $key => $sequence) {
            $tokens[$key] = [];
            foreach ($sequence as $segment) {
                $tokens[$key][] = (string) $segment;
                $tokenBytes[(string) $segment] ??= $this->bytes[$segment];
            }
        }
        while (true) {
            $value = [];
            $occurrences = [];
            $usage = [];
            foreach ($tokens as $key => $sequence) {
                $weight = $weights[$key];
                $pressure = $weight * (count($sequence) > self::TARGET_REQUESTS ? self::OVER_TARGET_WEIGHT : 1);
                $previous = null;
                foreach ($sequence as $token) {
                    $usage[$token] = ($usage[$token] ?? 0) + $weight;
                    if ($previous !== null) {
                        $pair = $previous . '|' . $token;
                        $value[$pair] = ($value[$pair] ?? 0) + $pressure;
                        $occurrences[$pair] = ($occurrences[$pair] ?? 0) + $weight;
                    }
                    $previous = $token;
                }
            }
            $bestPair = null;
            $bestGain = 0;
            foreach ($value as $pair => $pressure) {
                [$first, $second] = explode('|', $pair, 2);
                $merged = $first . ',' . $second;
                $gain = $requestWeight * $pressure
                    - (($usage[$merged] ?? 0) > 0 ? 0 : $tokenBytes[$first] + $tokenBytes[$second]);
                if ($usage[$first] === $occurrences[$pair]) {
                    $gain += $tokenBytes[$first];
                }
                if ($first !== $second && $usage[$second] === $occurrences[$pair]) {
                    $gain += $tokenBytes[$second];
                }
                if ($gain > $bestGain) {
                    $bestGain = $gain;
                    $bestPair = [$first, $second];
                }
            }
            if ($bestPair === null) {
                break;
            }
            [$first, $second] = $bestPair;
            $merged = $first . ',' . $second;
            $tokenBytes[$merged] ??= $tokenBytes[$first] + $tokenBytes[$second];
            foreach ($tokens as $key => $sequence) {
                $result = [];
                $count = count($sequence);
                for ($i = 0; $i < $count; $i++) {
                    if ($i + 1 < $count && $sequence[$i] === $first && $sequence[$i + 1] === $second) {
                        $result[] = $merged;
                        $i++;
                    } else {
                        $result[] = $sequence[$i];
                    }
                }
                $tokens[$key] = $result;
            }
        }

        $result = [];
        foreach ($tokens as $key => $sequence) {
            $result[$key] = array_map(
                static fn (string $token): array => array_map('intval', explode(',', $token)),
                $sequence,
            );
        }

        return $result;
    }

    /** @param array<string, list<list<int>>> $tokens */
    private function publishedBytes(array $tokens): int
    {
        $published = [];
        foreach ($tokens as $sequence) {
            foreach ($sequence as $token) {
                $published[implode(',', $token)] ??= $this->tokenBytes($token);
            }
        }

        return array_sum($published);
    }

    /** @param list<int> $token */
    private function tokenBytes(array $token): int
    {
        $total = 0;
        foreach ($token as $segment) {
            $total += $this->bytes[$segment];
        }

        return $total;
    }

    private function conflicting(int $a, int $b): bool
    {
        if ($a > $b) {
            [$a, $b] = [$b, $a];
        }

        return $this->conflicts[$a][$b] ??= ($this->conflict)($this->ids[$a], $this->ids[$b]);
    }
}
