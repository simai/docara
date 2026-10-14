<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Portable\PortableConfigurationException;
use Simai\Docara\PortableSite\ExamplePreviewPolicy;

final class ExamplePreviewPolicyTest extends TestCase
{
    #[Test]
    public function a_reusable_html_only_example_resolves_inline_like_an_example_written_in_the_page(): void
    {
        $policy = new ExamplePreviewPolicy;
        $sources = ['HTML' => '<div class="sf-dropdown" value="kazan"><button type="button">Open</button></div>'];

        foreach ([true, false] as $reusable) {
            self::assertSame(
                ['requested' => 'auto', 'resolved' => 'inline', 'reason' => 'admitted_html'],
                $policy->resolve($sources, $reusable, 'auto'),
            );
            self::assertSame('inline', $policy->resolve($sources, $reusable, 'inline')['resolved']);
            self::assertSame(
                ['requested' => 'sandbox', 'resolved' => 'sandbox', 'reason' => 'forced_sandbox'],
                $policy->resolve($sources, $reusable, 'sandbox'),
            );
        }
    }

    #[Test]
    public function a_reusable_example_with_css_or_javascript_stays_sandboxed_and_cannot_be_forced_inline(): void
    {
        $policy = new ExamplePreviewPolicy;
        self::assertSame(
            ['requested' => 'auto', 'resolved' => 'sandbox', 'reason' => 'custom_css'],
            $policy->resolve(['HTML' => '<p>Text</p>', 'CSS' => 'p{color:red}'], true, 'auto'),
        );
        self::assertSame(
            ['requested' => 'auto', 'resolved' => 'sandbox', 'reason' => 'javascript'],
            $policy->resolve(['HTML' => '<p>Text</p>', 'CSS' => 'p{}', 'JavaScript' => 'void 0;'], true, 'auto'),
        );

        foreach ([
            ['HTML' => '<p>Text</p>', 'JavaScript' => 'void 0;'],
            ['HTML' => '<p>Text</p>', 'CSS' => 'p{color:red}'],
        ] as $sources) {
            try {
                $policy->resolve($sources, true, 'inline');
                self::fail('A reusable example with author code was forced inline.');
            } catch (PortableConfigurationException $exception) {
                self::assertSame('MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED', $exception->errorCode);
            }
        }
    }

    #[Test]
    public function data_attributes_ids_slots_and_forms_without_a_destination_are_admitted_inline(): void
    {
        $policy = new ExamplePreviewPolicy;
        foreach ([
            '<div data-value="kazan" data-tooltip="save-tooltip" data-sf-modal-open="demo-modal">City</div>',
            '<span id="save-tooltip" class="sf-tooltip" hidden>Save</span>',
            '<form class="grid gap-3"><input type="text" name="status"><button type="submit">Check</button><button type="reset">Reset</button></form>',
            '<sf-modal><div slot="content"><p>Body</p></div><div slot="modal-footer">Footer</div></sf-modal>',
        ] as $html) {
            self::assertSame('admitted_html', $policy->resolve(['HTML' => $html], true, 'auto')['reason'], $html);
        }
    }

    #[Test]
    public function executable_destinations_and_shell_hooks_stay_outside_inline_examples(): void
    {
        $policy = new ExamplePreviewPolicy;
        foreach ([
            '<form action="/subscribe"><button>Send</button></form>' => 'html_attribute_not_admitted',
            '<form method="post"><button>Send</button></form>' => 'html_attribute_not_admitted',
            '<form target="_blank"><button>Send</button></form>' => 'html_attribute_not_admitted',
            '<form enctype="multipart/form-data"><button>Send</button></form>' => 'html_attribute_not_admitted',
            '<form name="forms"><button>Send</button></form>' => 'html_attribute_not_admitted',
            '<form><button formaction="/x">Send</button></form>' => 'html_attribute_not_admitted',
            '<button form="other">Send</button>' => 'html_attribute_not_admitted',
            '<script>document.body.remove()</script>' => 'html_element_not_admitted',
            '<a href="/next">Next</a>' => 'html_element_not_admitted',
            '<iframe srcdoc="x"></iframe>' => 'html_element_not_admitted',
            '<div onclick="void 0">Click</div>' => 'html_attribute_not_admitted',
            '<div style="color:red">Red</div>' => 'html_attribute_not_admitted',
            '<div data-action="javascript:alert(1)">Run</div>' => 'html_attribute_value_not_admitted',
            '<div data-docara-example="hijack">Shell</div>' => 'html_shell_attribute_not_admitted',
            '<div id="tooltip">Single word</div>' => 'html_id_not_admitted',
            '<div id="docara-main">Shell id</div>' => 'html_shell_id_not_admitted',
            '<div class="docara-prose">Shell class</div>' => 'html_shell_class_not_admitted',
            '<div slot="Content Footer">Slot</div>' => 'html_attribute_value_not_admitted',
            '<div slot="javascript:alert(1)">Slot</div>' => 'html_attribute_value_not_admitted',
        ] as $html => $reason) {
            self::assertSame(
                ['requested' => 'auto', 'resolved' => 'sandbox', 'reason' => $reason],
                $policy->resolve(['HTML' => $html], true, 'auto'),
                $html,
            );
        }

        try {
            $policy->resolve(['HTML' => '<script>void 0</script>'], true, 'inline');
            self::fail('A script was forced inline.');
        } catch (PortableConfigurationException $exception) {
            self::assertSame('MARKDOWN_EXAMPLE_INLINE_NOT_ADMITTED', $exception->errorCode);
        }
    }

    #[Test]
    public function smart_tags_admit_only_the_attributes_the_framework_lock_declares_for_them(): void
    {
        $policy = ExamplePreviewPolicy::fromFrameworkRuntime(['components' => [
            'sf-country-code' => ['attributes' => ['form', 'hint', 'iso2', 'label', 'root-class', 'src', 'template', 'use-mask']],
            'sf-inline-editor' => ['attributes' => []],
        ]]);
        $resolve = static fn (string $html): string => $policy->resolve(['HTML' => $html], true, 'auto')['reason'];

        self::assertSame('admitted_html', $resolve('<sf-country-code label="Phone" hint="Pick a country" iso2="RU" use-mask="true" root-class="p-2" name="phone" required="true"></sf-country-code>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<sf-country-code label="Phone" dial-code="+7"></sf-country-code>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<sf-country-code form="checkout"></sf-country-code>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<sf-country-code src="/flags.json"></sf-country-code>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<sf-inline-editor template="x"></sf-inline-editor>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<div label="Phone"></div>'));
    }

    #[Test]
    public function without_a_lock_smart_tags_keep_only_the_generic_attributes(): void
    {
        $policy = new ExamplePreviewPolicy;
        self::assertSame('admitted_html', $policy->resolve(['HTML' => '<sf-button class="x" aria-label="Save"></sf-button>'], true, 'auto')['reason']);
        self::assertSame('html_attribute_not_admitted', $policy->resolve(['HTML' => '<sf-country-code label="Phone"></sf-country-code>'], true, 'auto')['reason']);
    }

    #[Test]
    public function the_bundled_lock_declares_smart_attributes_from_the_smart_manifests(): void
    {
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json')->toArray();
        $policy = ExamplePreviewPolicy::fromFrameworkLock($lock);
        $resolve = static fn (string $html): string => $policy->resolve(['HTML' => $html], true, 'auto')['reason'];

        self::assertSame('admitted_html', $resolve('<sf-country-code label="Phone" name="phone" required="true" iso2="RU" value="+7 903 123-45-67" use-mask="true" hint="Pick a country."></sf-country-code>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<sf-country-code form="checkout"></sf-country-code>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<sf-country-code root-style="color:red"></sf-country-code>'));
        self::assertSame('html_attribute_not_admitted', $resolve('<sf-inline-editor template="x"></sf-inline-editor>'));
    }

    #[Test]
    public function the_breakpoints_come_from_the_bundled_utility_registry(): void
    {
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json')->toArray();

        self::assertSame(
            ['lg', 'md', 'sm', 'xl', 'xxl'],
            ExamplePreviewPolicy::frameworkBreakpoints(FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock))),
        );
    }

    #[Test]
    public function auto_frames_html_that_uses_breakpoint_utilities_so_the_responsive_viewer_applies(): void
    {
        $lock = FrameworkLock::fromJsonFile(dirname(__DIR__, 2) . '/stubs/portable/simai-framework.lock.json')->toArray();
        $policy = ExamplePreviewPolicy::fromFrameworkLock($lock);

        foreach ([
            '<div class="aspect-1x1 sm:aspect-16x9 bg-surface-container"></div>',
            '<div class="grid grid-col-1 xxl:grid-col-4"></div>',
            '<div class="query-container"><div class="grid cq-md:grid-col-2"></div></div>',
            '<div class="query-container/sidebar"><div class="cq-lg/sidebar:flex"></div></div>',
        ] as $html) {
            self::assertSame(
                ['requested' => 'auto', 'resolved' => 'sandbox', 'reason' => 'responsive_utilities'],
                $policy->resolve(['HTML' => $html], true, 'auto'),
                $html,
            );
            // Responsive markup is safe in the page; an explicit inline request stands.
            self::assertSame(
                ['requested' => 'inline', 'resolved' => 'inline', 'reason' => 'admitted_html'],
                $policy->resolve(['HTML' => $html], false, 'inline'),
                $html,
            );
        }

        foreach ([
            '<div class="aspect-16x9 flex"></div>',
            '<button type="button" class="sf-button hover:shadow-2 focus-visible:shadow-2">Save</button>',
            '<p data-note="sm:not-a-class">Text with sm:prefix words</p>',
        ] as $html) {
            self::assertSame('inline', $policy->resolve(['HTML' => $html], true, 'auto')['resolved'], $html);
        }

        // Without a lock no breakpoint is known, as before.
        self::assertSame('inline', (new ExamplePreviewPolicy)->resolve(['HTML' => '<div class="sm:aspect-16x9"></div>'], true, 'auto')['resolved']);
    }
}
