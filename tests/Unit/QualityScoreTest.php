<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Quality\QualityChecks;
use Simai\Docara\Quality\QualityScore;

final class QualityScoreTest extends TestCase
{
    private const ALL_GROUPS = QualityChecks::GROUPS;

    #[Test]
    public function the_weights_add_up_to_one_hundred_and_cover_every_group(): void
    {
        self::assertSame(100, array_sum(QualityScore::WEIGHTS));
        self::assertSame(QualityChecks::GROUPS, array_keys(QualityScore::WEIGHTS));
        self::assertSame(['warning' => 25, 'advice' => 10], QualityScore::PENALTIES);
    }

    #[Test]
    public function a_page_without_findings_scores_one_hundred(): void
    {
        $score = new QualityScore;

        self::assertSame(100.0, $score->scoreOf([], self::ALL_GROUPS));
        self::assertSame(100.0, $score->scoreOf([], ['links']));
    }

    #[Test]
    public function a_failed_check_costs_its_group_once_per_check(): void
    {
        $score = new QualityScore;
        $groups = $score->groupScores([
            ['code' => 'QUALITY_LINK_BROKEN'],
            ['code' => 'QUALITY_LINK_BROKEN'],
            ['code' => 'QUALITY_ANCHOR_MISSING'],
            ['code' => 'QUALITY_TOPIC_UNCLEAR'],
        ], self::ALL_GROUPS);

        self::assertSame(50.0, $groups['links']);
        self::assertSame(90.0, $groups['findability']);
        self::assertSame(100.0, $groups['completeness']);
        // Page: (25*100 + 10*100 + 20*90 + 15*100 + 15*100 + 15*50) / 100.
        self::assertEqualsWithDelta(90.5, $score->pageScore($groups), 1e-9);
    }

    #[Test]
    public function a_group_does_not_drop_below_zero(): void
    {
        $findings = array_map(static fn (string $code): array => ['code' => $code], [
            'QUALITY_IMAGE_ALT_MISSING', 'QUALITY_LINK_TEXT_GENERIC', 'QUALITY_HEADING_ORDER',
            'QUALITY_HEADING_LEVEL_SKIPPED', 'QUALITY_TABLE_HEADER_MISSING', 'QUALITY_LANG_MISSING',
        ]);

        self::assertSame(0.0, (new QualityScore)->groupScores($findings, self::ALL_GROUPS)['accessibility']);
    }

    #[Test]
    public function a_group_that_does_not_apply_is_left_out_and_the_weights_keep_their_proportions(): void
    {
        $score = new QualityScore;
        $withoutFreshness = array_values(array_diff(self::ALL_GROUPS, ['freshness']));
        $groups = $score->groupScores([['code' => 'QUALITY_DESCRIPTION_MISSING'], ['code' => 'QUALITY_SOURCE_CHANGED']], $withoutFreshness);

        self::assertArrayNotHasKey('freshness', $groups);
        // Completeness 75 with weight 25 of 90.
        self::assertEqualsWithDelta(100 - 25 * 25 / 90, $score->pageScore($groups), 1e-9);
    }

    #[Test]
    public function the_impact_is_the_site_gain_when_one_finding_is_fixed(): void
    {
        $score = new QualityScore;
        $findings = [['code' => 'QUALITY_DESCRIPTION_MISSING'], ['code' => 'QUALITY_TOPIC_UNCLEAR']];

        // 25 points of completeness (weight 25 of 100) on one of ten pages.
        self::assertEqualsWithDelta(0.625, $score->impact($findings, self::ALL_GROUPS, 'QUALITY_DESCRIPTION_MISSING', 10), 1e-9);
        self::assertEqualsWithDelta(0.2, $score->impact($findings, self::ALL_GROUPS, 'QUALITY_TOPIC_UNCLEAR', 10), 1e-9);
        self::assertSame(0.0, $score->impact($findings, self::ALL_GROUPS, 'QUALITY_LINK_BROKEN', 10));
        self::assertSame(0.0, $score->impact($findings, self::ALL_GROUPS, 'QUALITY_TOPIC_UNCLEAR', 0));
    }

    #[Test]
    public function site_score_is_the_mean_and_display_rounds_half_up(): void
    {
        $score = new QualityScore;

        self::assertSame(80.0, $score->mean([100.0, 60.0]));
        self::assertSame(100.0, $score->mean([]));
        self::assertSame(83, QualityScore::display(82.5));
        self::assertSame(82, QualityScore::display(82.49));
        self::assertSame(0.63, QualityScore::impactDisplay(0.625));
    }
}
