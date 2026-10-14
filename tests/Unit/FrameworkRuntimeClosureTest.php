<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkAssetPlanner;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkManifestRepository;
use Simai\Docara\Framework\FrameworkRuntimeClosure;

final class FrameworkRuntimeClosureTest extends TestCase
{
    #[Test]
    public function the_eager_set_is_the_admitted_closure_and_ignores_any_hand_list(): void
    {
        $runtime = ['components' => [
            'sf-alert' => ['javascript' => 'smart/smart/alert/js/alert.js', 'css' => null, 'requires' => ['sf-icon', 'sf-icon-button']],
            'sf-button' => ['javascript' => 'smart/smart/buttons/js/buttons.js', 'css' => null, 'requires' => ['sf-icon']],
            'sf-close' => ['javascript' => 'smart/smart/close/js/close.js', 'css' => null],
            'sf-icon' => ['javascript' => 'smart/smart/icons/js/icons.js', 'css' => null],
            'sf-icon-button' => [
                'javascript' => 'smart/smart/icon-buttons/js/icon-buttons.js',
                'css' => 'smart/smart/icon-buttons/css/icon-buttons.css',
                'requires' => ['sf-close', 'sf-icon'],
            ],
            'sf-modal' => ['javascript' => 'smart/smart/modal/js/modal.js', 'css' => null],
            'sf-table' => ['javascript' => 'smart/smart/table/js/table.js', 'css' => null, 'requires' => ['sf-button']],
        ]];

        self::assertSame(
            ['sf-alert', 'sf-button', 'sf-icon', 'sf-modal'],
            FrameworkRuntimeClosure::admissionRootTags(['sf-alert', 'sf-button']),
        );
        // The old hand list named alert, buttons, icons and modal. The closure
        // also reaches sf-icon-button through sf-alert and sf-close through
        // sf-icon-button; a component nothing reaches (sf-table) stays out.
        self::assertSame([
            'smart/alert/js/alert.js',
            'smart/buttons/js/buttons.js',
            'smart/close/js/close.js',
            'smart/icon-buttons/css/icon-buttons.css',
            'smart/icon-buttons/js/icon-buttons.js',
            'smart/icons/js/icons.js',
            'smart/modal/js/modal.js',
        ], FrameworkRuntimeClosure::eagerSmartFiles($runtime, ['sf-alert', 'sf-button']));

        $cyclic = $runtime;
        $cyclic['components']['sf-close']['requires'] = ['sf-icon-button'];
        $this->expectException(FrameworkComponentException::class);
        $this->expectExceptionMessage('FRAMEWORK_RUNTIME_DEPENDENCY_CYCLE');
        FrameworkRuntimeClosure::eagerSmartFiles($cyclic, ['sf-alert']);
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
        $locked = array_keys($lock['asset_projection']['files']);
        sort($locked, SORT_STRING);
        self::assertSame($derived, $locked);

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
        self::assertSame($derived, $planned);
    }
}
