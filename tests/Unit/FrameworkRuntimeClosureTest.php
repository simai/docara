<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkRuntimeClosure;

final class FrameworkRuntimeClosureTest extends TestCase
{
    #[Test]
    public function the_eager_set_is_the_admitted_closure_and_ignores_any_hand_list(): void
    {
        $runtime = ['components' => [
            'sf-alert' => ['javascript' => 'smart/smart/alert/js/alert.js', 'css' => null, 'requires' => ['sf-icon', 'sf-button-icon']],
            'sf-button' => ['javascript' => 'smart/smart/buttons/js/buttons.js', 'css' => null, 'requires' => ['sf-icon']],
            'sf-button-close' => ['javascript' => 'smart/smart/button-close/js/button-close.js', 'css' => null],
            'sf-icon' => ['javascript' => 'smart/smart/icons/js/icons.js', 'css' => null],
            'sf-button-icon' => [
                'javascript' => 'smart/smart/button-icon/js/button-icon.js',
                'css' => 'smart/smart/button-icon/css/button-icon.css',
                'requires' => ['sf-button-close', 'sf-icon'],
            ],
            'sf-modal' => ['javascript' => 'smart/smart/modal/js/modal.js', 'css' => null],
            'sf-view-table' => ['javascript' => 'smart/smart/view-table/js/view-table.js', 'css' => null, 'requires' => ['sf-button']],
        ]];

        self::assertSame(
            ['sf-alert', 'sf-button', 'sf-icon', 'sf-modal'],
            FrameworkRuntimeClosure::admissionRootTags(['sf-alert', 'sf-button']),
        );
        // The old hand list named alert, buttons, icons and modal. The closure
        // also reaches sf-button-icon through sf-alert and sf-button-close through
        // sf-button-icon; a component nothing reaches (sf-view-table) stays out.
        self::assertSame([
            'smart/alert/js/alert.js',
            'smart/button-close/js/button-close.js',
            'smart/button-icon/css/button-icon.css',
            'smart/button-icon/js/button-icon.js',
            'smart/buttons/js/buttons.js',
            'smart/icons/js/icons.js',
            'smart/modal/js/modal.js',
        ], FrameworkRuntimeClosure::eagerSmartFiles($runtime, ['sf-alert', 'sf-button']));

        // Lazy relations may name each other: a cycle is reached once, in a
        // stable order, and projects the same files.
        $cyclic = $runtime;
        $cyclic['components']['sf-button-close']['requires'] = ['sf-button-icon'];
        self::assertSame(
            FrameworkRuntimeClosure::eagerSmartFiles($runtime, ['sf-alert', 'sf-button']),
            FrameworkRuntimeClosure::eagerSmartFiles($cyclic, ['sf-alert', 'sf-button']),
        );
        $ordered = FrameworkRuntimeClosure::orderedTags($cyclic, ['sf-alert']);
        self::assertSame($ordered, array_values(array_unique($ordered)));
        self::assertContains('sf-button-close', $ordered);
        self::assertContains('sf-button-icon', $ordered);
        self::assertSame($ordered, FrameworkRuntimeClosure::orderedTags($cyclic, ['sf-alert']));
    }

    #[Test]
    public function the_bundled_eager_projection_is_exactly_what_the_preflight_closure_reaches(): void
    {
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $manifestTags = [];
        foreach (array_keys($lock['manifests']) as $key) {
            $manifest = json_decode(
                (string) file_get_contents(
                    dirname(__DIR__, 2) . '/resources/framework/' . FrameworkManifestRepository::manifestRelativePath($key),
                ),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $manifestTags[] = $manifest['frontend']['tag'];
        }
        $derived = FrameworkRuntimeClosure::eagerSmartFiles($lock['runtime'], $manifestTags);
        // The lock carries each eager file with its minified twin (and the
        // twin's licence file); without them it is exactly the closure.
        $locked = array_keys($lock['asset_projection']['files']);
        $readable = array_values(array_filter(
            $locked,
            static fn (string $path): bool => ! str_contains($path, '.min.'),
        ));
        sort($readable, SORT_STRING);
        self::assertSame($derived, $readable);
        $twins = [];
        foreach ($derived as $path) {
            $twin = (string) preg_replace('/\.(css|js)$/D', '.min.$1', $path);
            if (in_array($twin, $locked, true)) {
                $twins[$path] = $twin;
            }
        }
        self::assertCount(count($derived), $twins, 'Every eager entrypoint has its minified twin.');

        // The planner's own closure check agrees with the derivation.
        $repository = FrameworkManifestRepository::bundled(FrameworkLock::fromArray($lock));
        $plan = (new FrameworkAssetPlanner($repository, '/_docara/framework-runtime'))->assertExactProjection(
            $repository->keys(),
            FrameworkAssetPlanner::DOCARA_SHELL_RUNTIME_TAGS,
        );
        $planned = [];
        foreach ($plan->assets as $asset) {
            $path = is_string($asset['url'] ?? null) ? (string) parse_url($asset['url'], PHP_URL_PATH) : '';
            if (str_starts_with($path, '/_docara/framework-runtime/')) {
                $planned[] = substr($path, strlen('/_docara/framework-runtime/'));
            }
        }
        $planned = array_values(array_unique($planned));
        sort($planned, SORT_STRING);
        // A page links the twins.
        $linkedTwins = array_values($twins);
        sort($linkedTwins, SORT_STRING);
        self::assertSame($linkedTwins, $planned);
    }
}
