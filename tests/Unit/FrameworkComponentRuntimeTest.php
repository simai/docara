<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkComponentRuntime;
use Simai\Docara\PortableSite\PortableMarkdownRenderer;

final class FrameworkComponentRuntimeTest extends TestCase
{
    public function test_bundled_projections_are_exact_and_provider_locked(): void
    {
        self::assertSame(
            '84f61a452422814ef4ca11e5c5787ba48cdb36e923466c6309a8d389b84576fb',
            hash_file('sha256', $this->root() . '/resources/framework/manifests/ui-button.json'),
        );
        self::assertSame(
            '699b79d012d8e8af9a55f013ff19bafbc421cd16ee37990cb5ff070a0b1f490f',
            hash_file('sha256', $this->root() . '/resources/framework/manifests/ui-alert.json'),
        );
        self::assertSame(
            'd415eece461ee91000d7c82c4d17c08f7af1005d9c0bfd94ef6b7affdf5866ad',
            hash_file('sha256', $this->root() . '/resources/framework/runtime-lock.json'),
        );
        foreach ([
            'smart/alert/js/alert.js' => 'e994066dd2a7f9c4d15c573ea66bb47ccb0f12c24f4cf2e7dedee29eaddf9f1c',
            'smart/buttons/js/buttons.js' => 'fe977fc7c608b7bacb79b7641a302c30a6195659ac2351594ae5aef0656d0a27',
            'smart/icons/js/icons.js' => 'c810be681b51f98002e01fb8852e992e454fa607af005033f9cc10309016fa09',
        ] as $relativePath => $sha256) {
            self::assertSame(
                $sha256,
                hash_file('sha256', $this->root() . '/resources/framework/assets/' . $relativePath),
            );
        }
        self::assertStringContainsString(
            'resources/framework/assets/** -text',
            (string) file_get_contents($this->root() . '/.gitattributes'),
        );

        $document = $this->runtime()->extract(":::ui.button\n{}\n:::\n", 'index.md');
        self::assertSame('4b055d09926fec4c32f2ae43b2e7e0a6f64d7663', $document->normalizedCalls[0]['provider_revision']);
        self::assertSame('bounded_consumer_verified', $document->diagnostics['mode']);
        self::assertSame(['production_ready', 'all_framework_components_ready'], $document->diagnostics['nonclaims']);
    }

    public function test_it_extracts_renders_and_hydrates_components_outside_code_fences(): void
    {
        $markdown = <<<'MARKDOWN'
# Components

```markdown
:::ui.alert
{"title":"Not rendered"}
:::
```

:::ui.button
{"preset":"outline","text":"Open <guide>","loading":false,"disabled":false}
:::

:::ui.alert
{"title":"Heads <up>","supporting-text":"Use \"x\""}
:::
MARKDOWN;

        $document = $this->runtime()->extract($markdown . "\n", 'guides/start.md');

        self::assertCount(2, $document->normalizedCalls);
        self::assertStringContainsString(':::ui.alert', $document->markdownWithPlaceholders);
        self::assertStringContainsString('DOCARA_COMPONENT_', $document->markdownWithPlaceholders);
        self::assertStringNotContainsString('<sf-', $document->markdownWithPlaceholders);

        $button = $document->normalizedCalls[0];
        self::assertSame('docara.component_call.v1', $button['schema']);
        self::assertSame('ui.button', $button['id']);
        self::assertSame('outline', $button['props']['type']);
        self::assertSame('primary', $button['props']['scheme']);
        self::assertSame('Open <guide>', $button['props']['aria-label']);
        self::assertSame(
            '<sf-button data-larena-smart-runtime="sf-v5.3.2-7e836d8a-dd786bba" text="Open &lt;guide&gt;" size="1" type="outline" scheme="primary" native-type="button" aria-label="Open &lt;guide&gt;"></sf-button>',
            $button['html'],
        );

        $alert = $document->normalizedCalls[1];
        self::assertSame('docara.component_call.v1', $alert['schema']);
        self::assertSame('ui.alert', $alert['id']);
        self::assertSame('Heads <up>', $alert['props']['aria-label']);
        self::assertMatchesRegularExpression('/^docara-alert-[a-f0-9]{16}$/', $alert['props']['id']);
        self::assertStringStartsWith('<sf-alert id="' . $alert['props']['id'] . '" data-larena-smart-runtime="sf-v5.3.2-7e836d8a-dd786bba"', $alert['html']);
        self::assertStringContainsString('title="Heads &lt;up&gt;"', $alert['html']);
        self::assertStringContainsString('supporting-text="Use &quot;x&quot;"', $alert['html']);
        self::assertStringNotContainsString(' closable ', $alert['html']);

        $renderedMarkdown = '<h1>Components</h1>' . "\n";
        foreach (array_keys($document->renderedHtml) as $placeholder) {
            $renderedMarkdown .= '<p>' . $placeholder . '</p>' . "\n";
        }
        $hydrated = $document->hydrate($renderedMarkdown);
        self::assertStringContainsString('<sf-button', $hydrated);
        self::assertStringContainsString('<sf-alert', $hydrated);
        self::assertStringNotContainsString('DOCARA_COMPONENT_', $hydrated);
    }

    public function test_four_space_indented_smart_directives_remain_commonmark_code(): void
    {
        $document = $this->runtime()->extract(
            "    :::ui.button\n    {\"text\":\"Example\"}\n    :::\n",
            'guide.md',
        );

        self::assertSame([], $document->normalizedCalls);
        self::assertStringContainsString(':::ui.button', $document->markdownWithPlaceholders);
        self::assertStringNotContainsString('DOCARA_COMPONENT_', $document->markdownWithPlaceholders);
    }

    public function test_legacy_closing_fences_accept_zero_to_three_spaces_and_crlf(): void
    {
        foreach (['', ' ', '  ', '   '] as $indent) {
            $document = $this->runtime()->extract(
                ":::ui.button\r\n{}\r\n{$indent}:::\r\n",
                'guide.md',
            );

            self::assertCount(1, $document->normalizedCalls);
        }
    }

    public function test_longer_matching_colon_fences_are_supported(): void
    {
        $document = $this->runtime()->extract(
            "::::ui.button\n{\"text\":\"Long fence\"}\n::::\n",
            'guide.md',
        );

        self::assertCount(1, $document->normalizedCalls);
        self::assertSame('Long fence', $document->normalizedCalls[0]['props']['text']);
    }

    public function test_portable_and_smart_blocks_are_adjacent_boundaries_in_both_orders(): void
    {
        foreach ([
            ":::card\nCard\n:::\n:::ui.button\n{}\n:::\n",
            ":::ui.button\n{}\n:::\n:::card\nCard\n:::\n",
            ":::steps\n1. Step\n:::\n:::ui.button\n{}\n:::\n",
            ":::steps\r\n1. Step\r\n:::\r\n:::ui.button\r\n{}\r\n:::\r\n",
        ] as $markdown) {
            $document = $this->runtime()->extract($markdown, 'guide.md');
            self::assertCount(1, $document->normalizedCalls);

            $html = (new PortableMarkdownRenderer)->render($document->markdownWithPlaceholders);
            $hydrated = $document->hydrate($html);
            self::assertSame(1, substr_count($hydrated, '<section'));
            self::assertSame(1, substr_count($hydrated, '<sf-button'));
        }
    }

    public function test_smart_directive_like_text_inside_inline_code_and_reference_titles_is_literal(): void
    {
        foreach ([
            "Prefix ``\n:::ui.button\n`` suffix\n",
            "[a\\]b]: /guide/ \"\n:::ui.button\n\"\n[Guide][a\\]b]\n",
        ] as $markdown) {
            $document = $this->runtime()->extract($markdown, 'guide.md');
            self::assertSame([], $document->normalizedCalls);
            self::assertStringContainsString(':::ui.button', $document->markdownWithPlaceholders);
        }
    }

    public function test_smart_and_portable_directives_cannot_be_nested_in_either_direction(): void
    {
        foreach ([
            "::::card\nCard\n:::ui.button\n{}\n:::\n::::\n",
            "::::steps\n1. Step\n:::ui.alert\n{}\n:::\n::::\n",
            "::::ui.button\n:::card\nCard\n:::\n::::\n",
            "::::ui.alert\n:::steps\n1. Step\n:::\n::::\n",
            "::::ui.button\n1. Fake payload\n:::ui.alert\n{}\n:::\n::::\n",
            "::::ui.alert\n> Fake payload\n:::ui.button\n{}\n:::\n::::\n",
        ] as $markdown) {
            $this->expectFailure(
                fn () => $this->runtime()->extract($markdown, 'guide.md'),
                'FRAMEWORK_DIRECTIVE_NESTING_UNSUPPORTED',
            );
        }
    }

    public function test_indented_root_openers_remain_literal_but_lazy_container_openers_fail(): void
    {
        foreach ([' ', '  ', '   '] as $indent) {
            $document = $this->runtime()->extract(
                "{$indent}::::ui.button\n{$indent}{}\n{$indent}::::\n",
                'guide.md',
            );
            self::assertSame([], $document->normalizedCalls);
        }

        foreach ([
            "- Before\n::::ui.button\n{}\n::::\n",
            "1. Before\n::::ui.button\n{}\n::::\n",
            "> Before\n::::ui.button\n{}\n::::\n",
        ] as $markdown) {
            $this->expectFailure(
                fn () => $this->runtime()->extract($markdown, 'guide.md'),
                'FRAMEWORK_DIRECTIVE_INDENTATION_UNSUPPORTED',
            );
        }
    }

    public function test_a_top_level_smart_directive_after_a_closed_list_is_recognized(): void
    {
        foreach (["\n\n", "\n\n\n"] as $separator) {
            $document = $this->runtime()->extract(
                "1. Before{$separator}:::ui.button\n{}\n:::\n",
                'guide.md',
            );

            self::assertCount(1, $document->normalizedCalls);
        }
    }

    public function test_smart_directives_inside_list_contained_fences_remain_code(): void
    {
        $document = $this->runtime()->extract(
            "- ```markdown\n  :::ui.button\n  {}\n  :::\n  ```\n",
            'guide.md',
        );

        self::assertSame([], $document->normalizedCalls);
        self::assertStringContainsString(':::ui.button', $document->markdownWithPlaceholders);
    }

    public function test_smart_directives_inside_html_comments_are_not_executed(): void
    {
        $document = $this->runtime()->extract(
            "<!--\n:::ui.button\n{}\n:::\n-->\n",
            'guide.md',
        );

        self::assertSame([], $document->normalizedCalls);
        self::assertStringContainsString(':::ui.button', $document->markdownWithPlaceholders);
    }

    public function test_smart_boundaries_do_not_hide_a_following_list_contained_fence(): void
    {
        $document = $this->runtime()->extract(<<<'MD'
:::ui.button
{}
:::
2. ```markdown
   :::ui.button
   {}
   :::
   ```
MD, 'guide.md');

        self::assertCount(1, $document->normalizedCalls);
        self::assertStringContainsString(':::ui.button', $document->markdownWithPlaceholders);
    }

    public function test_smart_boundaries_preserve_following_html_block_opacity(): void
    {
        $document = $this->runtime()->extract(
            ":::ui.button\n{}\n:::\n<span>\n:::ui.button\n{}\n:::\n</span>\n",
            'guide.md',
        );

        self::assertCount(1, $document->normalizedCalls);
    }

    public function test_indented_smart_directives_fail_instead_of_being_reparented_out_of_a_list(): void
    {
        $this->expectFailure(
            fn () => $this->runtime()->extract(
                "- Before\n  :::ui.button\n  {}\n  :::\n  After\n",
                'guide.md',
            ),
            'FRAMEWORK_DIRECTIVE_INDENTATION_UNSUPPORTED',
        );
    }

    public function test_commonmark_container_state_decides_where_a_smart_fence_closes(): void
    {
        $document = $this->runtime()->extract(
            "- ```markdown\n  :::ui.button\n  {}\n```\n:::ui.button\n{}\n:::\n",
            'guide.md',
        );

        self::assertSame([], $document->normalizedCalls);
        self::assertSame(2, substr_count($document->markdownWithPlaceholders, ':::ui.button'));
    }

    public function test_four_space_indented_closing_delimiter_does_not_close_a_smart_directive(): void
    {
        $this->expectFailure(
            fn () => $this->runtime()->extract(
                ":::ui.button\n{}\n    :::\n",
                'guide.md',
            ),
            'FRAMEWORK_DIRECTIVE_UNCLOSED',
        );
    }

    public function test_asset_plan_is_commit_pinned_and_boot_order_is_deterministic(): void
    {
        $document = $this->runtime()->extract(
            ":::ui.button\n{}\n:::\n\n:::ui.alert\n{}\n:::\n",
            'index.md',
        );

        self::assertSame([
            'docara.framework.storage.compatibility',
            'simai.framework.boot',
            'simai.framework.core.css',
            'simai.framework.utility.full.css',
            'simai.framework.icon_font.css',
            'simai.framework.icon_font.ready',
            'simai.framework.smart_base.js',
            'simai.framework.core.js',
            'simai.framework.sf_icon.js',
            'simai.framework.sf_alert.js',
            'simai.framework.sf_button.js',
        ], array_column($document->assetPlan->assets, 'key'));

        $serialized = json_encode($document->assetPlan->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        self::assertStringContainsString('simai/ui@7e836d8a9414d5da553fb1ab0404721e5b48769a/', $serialized);
        self::assertStringNotContainsString('simai/ui-smart@', $serialized);
        self::assertStringContainsString('/_docara/framework/smart/alert/js/alert.js', $serialized);
        self::assertStringContainsString('"source_revision":"dd786bbae98391fb21df9b4e1e6cd402ead0614c"', $serialized);
        self::assertStringNotContainsString('@main', strtolower($serialized));
        self::assertStringNotContainsString('@latest', strtolower($serialized));
        self::assertStringContainsString('/distr/core/css/utility.full.css', $serialized);
        self::assertStringContainsString('/distr/\\";window.sfSmartPath=\\"/_docara/framework', $serialized);
        self::assertStringContainsString('/distr/fonts/MaterialSymbols-Outlined.woff2', $serialized);
        self::assertStringContainsString('docaraFullFontReady', $serialized);
        self::assertStringContainsString("Object.defineProperty(window,'localStorage'", $serialized);
        self::assertStringContainsString('docaraFrameworkStorage', $serialized);
        self::assertStringContainsString('key:function(index)', $serialized);
        self::assertStringNotContainsString('sessionStorage', $serialized);
        self::assertStringContainsString('"kind":"smart_javascript"', $serialized);
        self::assertMatchesRegularExpression(
            '#/_docara/framework/smart/alert/js/alert\.js\?sf_v=sf-v5\.3\.2-7e836d8a-dd786bba-[a-f0-9]{16}#',
            $serialized,
        );
        self::assertStringNotContainsString('smart_components.loader', $serialized);
        self::assertLessThan(
            strpos($serialized, 'simai.framework.sf_alert.js'),
            strpos($serialized, 'simai.framework.sf_icon.js'),
        );
    }

    public function test_equivalent_author_json_is_deterministic(): void
    {
        $first = $this->runtime()->extract(
            ":::ui.button\n{\"text\":\"Read\",\"preset\":\"secondary\"}\n:::\n",
            'guide.md',
        );
        $second = $this->runtime()->extract(
            ":::ui.button\n{\"preset\":\"secondary\",\"text\":\"Read\"}\n:::\n",
            'guide.md',
        );

        self::assertSame($first->normalizedCalls, $second->normalizedCalls);
        self::assertSame($first->markdownWithPlaceholders, $second->markdownWithPlaceholders);
        self::assertSame($first->assetPlan->toArray(), $second->assetPlan->toArray());
    }

    public function test_author_text_cannot_collide_with_a_generated_component_placeholder(): void
    {
        $pagePath = 'guide.md';
        $oldPlaceholder = 'DOCARA_COMPONENT_' . strtoupper(substr(hash(
            'sha256',
            $pagePath . "\0" . 1 . "\0" . 'ui.button' . "\0" . 0,
        ), 0, 24));
        $document = $this->runtime()->extract(
            "```text\n{$oldPlaceholder}\n```\n\n:::ui.button\n{}\n:::\n",
            $pagePath,
        );
        $actualPlaceholder = array_key_first($document->renderedHtml);
        self::assertNotSame($oldPlaceholder, $actualPlaceholder);

        $hydrated = $document->hydrate(
            "<pre><code>{$oldPlaceholder}</code></pre>\n<p>{$actualPlaceholder}</p>\n",
        );
        self::assertStringContainsString("<code>{$oldPlaceholder}</code>", $hydrated);
        self::assertSame(1, substr_count($hydrated, '<sf-button'));
    }

    public function test_entity_equivalent_component_placeholder_fails_closed(): void
    {
        $pagePath = 'guide.md';
        $placeholder = 'DOCARA_COMPONENT_' . strtoupper(substr(hash(
            'sha256',
            $pagePath . "\0" . 1 . "\0" . 'ui.button' . "\0" . 0,
        ), 0, 24));
        $entityEquivalent = str_replace('_', '&#95;', $placeholder);
        $document = $this->runtime()->extract(
            "{$entityEquivalent}\n\n:::ui.button\n{}\n:::\n",
            $pagePath,
        );
        $rendered = (new PortableMarkdownRenderer)->render($document->markdownWithPlaceholders);

        $this->expectFailure(
            fn () => $document->hydrate($rendered),
            'FRAMEWORK_PLACEHOLDER_CARDINALITY_INVALID',
        );
    }

    #[DataProvider('invalidDirectiveProvider')]
    public function test_invalid_directives_fail_closed(string $markdown, string $expectedCode): void
    {
        try {
            $this->runtime()->extract($markdown, 'broken.md');
            self::fail('Invalid directive unexpectedly passed.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame($expectedCode, $exception->errorCode);
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidDirectiveProvider(): iterable
    {
        yield 'invalid UTF-8' => ["\xFF\n:::ui.button\n{}\n:::\n", 'FRAMEWORK_DIRECTIVE_MARKDOWN_INVALID'];
        yield 'malformed JSON' => [":::ui.button\n{\n:::\n", 'FRAMEWORK_DIRECTIVE_JSON_INVALID'];
        yield 'non-object JSON' => [":::ui.button\n[]\n:::\n", 'FRAMEWORK_DIRECTIVE_PROPS_INVALID'];
        yield 'unknown component' => [":::ui.card\n{}\n:::\n", 'FRAMEWORK_COMPONENT_UNSUPPORTED'];
        yield 'unclosed' => [":::ui.alert\n{}\n", 'FRAMEWORK_DIRECTIVE_UNCLOSED'];
        yield 'unknown prop' => [":::ui.button\n{\"onclick\":\"bad\"}\n:::\n", 'FRAMEWORK_PROP_UNKNOWN'];
        yield 'runtime-managed alert id' => [":::ui.alert\n{\"id\":\"author-id\"}\n:::\n", 'FRAMEWORK_PROP_MANAGED'];
        yield 'invalid constraint' => [":::ui.button\n{\"type\":\"default\",\"scheme\":\"secondary\"}\n:::\n", 'FRAMEWORK_CONSTRAINT_COMBINATION_INVALID'];
        yield 'loading requires disabled' => [":::ui.button\n{\"loading\":true,\"disabled\":false}\n:::\n", 'FRAMEWORK_CONSTRAINT_REQUIREMENT_INVALID'];
        yield 'unknown preset' => [":::ui.button\n{\"preset\":\"invented\"}\n:::\n", 'FRAMEWORK_PRESET_UNKNOWN'];
        yield 'alert dependency outside bounded pair' => [":::ui.alert\n{\"closable\":true}\n:::\n", 'FRAMEWORK_PROP_UNSUPPORTED_IN_BOUNDED_RUNTIME'];
    }

    public function test_source_directive_marker_count_is_bounded_before_extraction(): void
    {
        $this->expectFailure(
            fn () => $this->runtime()->extract(str_repeat(":::ui.button\n{}\n:::\n", 65), 'large.md'),
            'FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED',
        );
        $this->expectFailure(
            fn () => $this->runtime()->extract(str_repeat(":::features\n- One\n- Two\n:::\n", 65), 'large.md'),
            'MARKDOWN_BLOCK_LIMIT_EXCEEDED',
        );
    }

    public function test_combined_directive_marker_count_is_bounded_before_cross_family_parsing(): void
    {
        $portable = ":::features\n- One\n- Two\n:::\n";
        $framework = ":::ui.button\n{}\n:::\n";

        $boundary = $this->runtime()->extract(
            str_repeat($portable, 32) . str_repeat($framework, 32),
            'mixed-boundary.md',
        );
        self::assertCount(32, $boundary->normalizedCalls);

        $this->expectFailure(
            fn () => $this->runtime()->extract(
                str_repeat($portable, 32) . str_repeat($framework, 33),
                'mixed-framework-overflow.md',
            ),
            'FRAMEWORK_DIRECTIVE_LIMIT_EXCEEDED',
        );
        $this->expectFailure(
            fn () => $this->runtime()->extract(
                str_repeat($framework, 32) . str_repeat($portable, 33),
                'mixed-portable-overflow.md',
            ),
            'MARKDOWN_BLOCK_LIMIT_EXCEEDED',
        );
    }

    public function test_runtime_and_manifest_lock_mismatches_fail_closed(): void
    {
        $providerMismatch = $this->lock();
        $providerMismatch['manifests']['ui.button']['provider_revision'] = str_repeat('a', 40);
        $this->expectFailure(
            fn () => FrameworkComponentRuntime::fromLock($providerMismatch)->extract(":::ui.button\n{}\n:::\n", 'index.md'),
            'FRAMEWORK_MANIFEST_PROVIDER_MISMATCH',
        );

        $hashMismatch = $this->lock();
        $hashMismatch['manifests']['ui.alert']['sha256'] = str_repeat('a', 64);
        $this->expectFailure(
            fn () => FrameworkComponentRuntime::fromLock($hashMismatch)->extract(":::ui.alert\n{}\n:::\n", 'index.md'),
            'FRAMEWORK_MANIFEST_HASH_MISMATCH',
        );

        $runtimeMismatch = $this->lock();
        $runtimeMismatch['runtime']['tag'] = 'v5.3.1';
        $this->expectFailure(
            fn () => FrameworkComponentRuntime::fromLock($runtimeMismatch),
            'FRAMEWORK_RUNTIME_PROJECTION_MISMATCH',
        );

        $movingReference = $this->lock();
        $movingReference['runtime']['tag'] = 'latest';
        $this->expectFailure(
            fn () => FrameworkComponentRuntime::fromLock($movingReference),
            'FRAMEWORK_MOVING_REFERENCE_FORBIDDEN',
        );

        $projectionRevisionMismatch = $this->lock();
        $projectionRevisionMismatch['asset_projection']['source']['revision'] = str_repeat('a', 40);
        $this->expectFailure(
            fn () => FrameworkComponentRuntime::fromLock($projectionRevisionMismatch),
            'FRAMEWORK_ASSET_PROJECTION_INVALID',
        );

        $projectionHashMismatch = $this->lock();
        $projectionHashMismatch['asset_projection']['files']['smart/alert/js/alert.js']['sha256'] = str_repeat('a', 64);
        $this->expectFailure(
            fn () => FrameworkComponentRuntime::fromLock($projectionHashMismatch)->extract(":::ui.alert\n{}\n:::\n", 'index.md'),
            'FRAMEWORK_BUNDLED_ASSET_HASH_MISMATCH',
        );
    }

    public function test_asset_projection_is_object_order_independent_and_asset_base_is_fail_closed(): void
    {
        $lock = $this->lock();
        $lock['asset_projection']['files'] = array_reverse($lock['asset_projection']['files'], true);
        $lock['runtime'] = array_reverse($lock['runtime'], true);
        $lock['runtime']['ui'] = array_reverse($lock['runtime']['ui'], true);
        $lock['runtime']['ui_smart'] = array_reverse($lock['runtime']['ui_smart'], true);
        $lock['manifests'] = array_reverse($lock['manifests'], true);
        $document = FrameworkComponentRuntime::fromLock($lock, '/docs/_docara/framework')
            ->extract(":::ui.button\n{}\n:::\n", 'index.md');
        self::assertStringContainsString(
            '/docs/_docara/framework/smart/buttons/js/buttons.js',
            json_encode($document->assetPlan->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        foreach (['//framework', '/../framework', '/framework/..', '/framework\\assets', '/framework?x', '/framework#x'] as $base) {
            $this->expectFailure(
                fn () => FrameworkComponentRuntime::fromLock($this->lock(), $base),
                'FRAMEWORK_ASSET_BASE_INVALID',
            );
        }
    }

    private function runtime(): FrameworkComponentRuntime
    {
        return FrameworkComponentRuntime::fromLock($this->lock());
    }

    /** @return array<string, mixed> */
    private function lock(): array
    {
        return json_decode(
            (string) file_get_contents($this->root() . '/stubs/portable/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function expectFailure(callable $callable, string $expectedCode): void
    {
        try {
            $callable();
            self::fail('Invalid lock unexpectedly passed.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame($expectedCode, $exception->errorCode);
        }
    }
}
