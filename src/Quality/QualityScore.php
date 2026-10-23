<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

/**
 * The documentation quality score, in one place.
 *
 * 1. A group score of a page starts at 100. Every check that fails costs
 *    25 points for a warning and 10 for advice, once per check however many
 *    times it occurs on the page. The score does not drop below 0.
 * 2. The page score is the weighted mean of its group scores:
 *    completeness 25, freshness 10, findability 20, readability 15,
 *    accessibility 15, links 15. A group that does not apply (freshness
 *    without documentation tracking) is left out and the other weights
 *    keep their proportions.
 * 3. A site group score and the site score are the plain means over the
 *    scored pages.
 * 4. The impact of one finding is how many points the site score gains when
 *    that finding alone is fixed. "Fix first" lists findings by impact.
 *
 * Scores are rounded half up to whole numbers only for display; impacts keep
 * two decimals. The formula uses no clock, network or randomness.
 */
final class QualityScore
{
    /** @var array<string, int> */
    public const WEIGHTS = [
        'completeness' => 25,
        'freshness' => 10,
        'findability' => 20,
        'readability' => 15,
        'accessibility' => 15,
        'links' => 15,
    ];

    /** @var array<string, int> */
    public const PENALTIES = [
        QualityChecks::WARNING => 25,
        QualityChecks::ADVICE => 10,
    ];

    /**
     * @param  list<array{code:string}>  $findings  the failed checks of one page
     * @param  list<string>  $groups  the groups that apply to the page
     * @return array<string, float> group => score 0..100
     */
    public function groupScores(array $findings, array $groups): array
    {
        $scores = [];
        foreach (QualityChecks::GROUPS as $group) {
            if (in_array($group, $groups, true)) {
                $scores[$group] = 100.0;
            }
        }
        $seen = [];
        foreach ($findings as $finding) {
            $code = $finding['code'];
            $check = QualityChecks::get($code);
            if (isset($seen[$code]) || ! isset($scores[$check['group']])) {
                continue;
            }
            $seen[$code] = true;
            $scores[$check['group']] = max(0.0, $scores[$check['group']] - self::PENALTIES[$check['severity']]);
        }

        return $scores;
    }

    /** @param array<string, float> $groupScores */
    public function pageScore(array $groupScores): float
    {
        $weight = 0;
        $sum = 0.0;
        foreach ($groupScores as $group => $score) {
            $weight += self::WEIGHTS[$group];
            $sum += self::WEIGHTS[$group] * $score;
        }

        return $weight === 0 ? 100.0 : $sum / $weight;
    }

    /** @param list<array{code:string}> $findings @param list<string> $groups */
    public function scoreOf(array $findings, array $groups): float
    {
        return $this->pageScore($this->groupScores($findings, $groups));
    }

    /** @param list<float> $scores */
    public function mean(array $scores): float
    {
        return $scores === [] ? 100.0 : array_sum($scores) / count($scores);
    }

    /**
     * Site points gained when one finding of a page is fixed: the page score
     * without the finding, minus the page score with it, divided by the
     * number of scored pages.
     *
     * @param  list<array{code:string}>  $findings
     * @param  list<string>  $groups
     */
    public function impact(array $findings, array $groups, string $code, int $pages): float
    {
        if ($pages < 1) {
            return 0.0;
        }
        $without = array_values(array_filter($findings, static fn (array $finding): bool => $finding['code'] !== $code));

        return ($this->scoreOf($without, $groups) - $this->scoreOf($findings, $groups)) / $pages;
    }

    public static function display(float $score): int
    {
        return (int) floor($score + 0.5);
    }

    public static function impactDisplay(float $impact): float
    {
        return floor($impact * 100 + 0.5) / 100;
    }
}
