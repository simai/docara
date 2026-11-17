<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\PortableSite\InlineExampleAssetRebaser;

final class InlineExampleAssetRebaserTest extends TestCase
{
    #[Test]
    public function every_attribute_form_of_an_own_asset_moves_to_the_example_folder(): void
    {
        $base = '/_docara/examples/components/gallery/';
        $assets = ['assets/a.svg', 'assets/a.svg.gz', 'assets/b&c.svg'];
        $rebased = (new InlineExampleAssetRebaser)->rebase(
            '<img src="assets/a.svg" srcset="assets/a.svg 1x, ./assets/a.svg.gz 2x" alt="">'
            . '<div style="background-image: url(assets/a.svg)" data-image=assets/a.svg></div>'
            . '<sf-avatar image-url=\'assets/b&amp;c.svg?v=2#x\' data-json=\'{"href":"assets\/a.svg"}\'></sf-avatar>',
            $assets,
            $base,
        );

        self::assertSame(
            '<img src="/_docara/examples/components/gallery/assets/a.svg"'
            . ' srcset="/_docara/examples/components/gallery/assets/a.svg 1x, /_docara/examples/components/gallery/assets/a.svg.gz 2x" alt="">'
            . '<div style="background-image: url(/_docara/examples/components/gallery/assets/a.svg)" data-image=/_docara/examples/components/gallery/assets/a.svg></div>'
            . '<sf-avatar image-url=\'/_docara/examples/components/gallery/assets/b&amp;c.svg?v=2#x\' data-json=\'{"href":"/_docara/examples/components/gallery/assets\/a.svg"}\'></sf-avatar>',
            $rebased,
        );
    }

    #[Test]
    public function text_unknown_files_absolute_and_parent_paths_stay_as_written(): void
    {
        $html = '<p data-a="https://cdn.test/assets/a.svg" data-b="/assets/a.svg" data-c="../assets/a.svg"'
            . ' data-d="assets/a.svgz" data-e="myassets/a.svg" data-f="assets/other.svg">assets/a.svg</p>';

        self::assertSame($html, (new InlineExampleAssetRebaser)->rebase($html, ['assets/a.svg'], '/_docara/examples/x/'));
        self::assertSame($html, (new InlineExampleAssetRebaser)->rebase($html, [], '/_docara/examples/x/'));
    }
}
