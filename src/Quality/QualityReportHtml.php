<?php

declare(strict_types=1);

namespace Simai\Docara\Quality;

/**
 * A standalone HTML page of the quality report: one file with inline styles,
 * no scripts and no external requests. It is written next to the build
 * (`build_<env>.docara/quality-report.html`) and is never published with the
 * site.
 */
final class QualityReportHtml
{
    /** @param array<string, mixed> $report */
    public function render(array $report): string
    {
        $e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $site = $report['site'];
        $rows = '';
        foreach ($report['groups'] as $group => $value) {
            $rows .= '<tr><th scope="row">' . $e($group) . '</th><td>' . ($value['score'] === null ? 'n/a' : $e($value['score'])) . '</td><td>' . $e($value['weight']) . '</td></tr>';
        }
        $fix = '';
        foreach ($report['fix_first'] as $index => $item) {
            $fix .= '<tr><td>' . ($index + 1) . '</td><td>+' . $e(number_format((float) $item['impact'], 2, '.', '')) . '</td><td><a href="' . $e($item['url']) . '">' . $e($item['url']) . '</a></td><td><code>' . $e($item['code']) . '</code><br><span class="' . $e($item['severity']) . '">' . $e($item['severity']) . '</span></td><td>' . $e($item['detail']) . '<br><small>' . $e($item['hint']) . '</small></td></tr>';
        }
        $pages = '';
        foreach ($report['pages'] as $page) {
            $findings = '';
            foreach ($page['findings'] as $finding) {
                $findings .= '<li><code>' . $e($finding['code']) . '</code> <span class="' . $e($finding['severity']) . '">' . $e($finding['severity']) . '</span> ' . $e($finding['detail']) . '</li>';
            }
            $pages .= '<tr><td>' . $e($page['score']) . '</td><td><a href="' . $e($page['url']) . '">' . $e($page['url']) . '</a><br><small>' . $e($page['source']) . '</small></td><td>' . ($findings === '' ? '—' : '<ul>' . $findings . '</ul>') . '</td></tr>';
        }

        return '<!doctype html>' . "\n"
            . '<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex"><title>Documentation quality report</title><style>'
            . 'body{font:15px/1.5 system-ui,sans-serif;margin:0 auto;max-width:72rem;padding:1rem;color:#1d1d1f;background:#fff}'
            . 'table{border-collapse:collapse;width:100%;margin:0 0 2rem}th,td{border-bottom:1px solid #ddd;padding:.4rem .5rem;text-align:left;vertical-align:top}'
            . 'code{font-size:.85em}.warning{color:#a40000}.advice{color:#5a5a5a}ul{margin:0;padding-left:1.1rem}small{color:#555}'
            . '@media (prefers-color-scheme:dark){body{background:#151515;color:#eee}th,td{border-color:#333}.warning{color:#ff8a80}.advice,small{color:#aaa}a{color:#8ab4f8}}'
            . '</style></head><body>'
            . '<h1>Documentation quality: ' . $e($site['score']) . '/100</h1>'
            . '<p>' . $e($site['pages']) . ' pages scored, ' . $e($site['pages_needing_attention']) . ' need attention, ' . $e($site['skipped']) . ' skipped. '
            . 'Each failed check costs a group 25 points (warning) or 10 points (advice); the page score is the weighted mean of its groups; the site score is the mean of the pages.</p>'
            . '<h2>Groups</h2><table><thead><tr><th scope="col">Group</th><th scope="col">Score</th><th scope="col">Weight</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<h2>Fix first</h2><table><thead><tr><th scope="col">#</th><th scope="col">Site points</th><th scope="col">Page</th><th scope="col">Check</th><th scope="col">Detail and fix</th></tr></thead><tbody>' . $fix . '</tbody></table>'
            . '<h2>Pages</h2><table><thead><tr><th scope="col">Score</th><th scope="col">Page</th><th scope="col">Findings</th></tr></thead><tbody>' . $pages . '</tbody></table>'
            . '</body></html>' . "\n";
    }
}
