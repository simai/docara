<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Simai\Docara\Template\TemplateMirror;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class TemplateMirrorTest extends TestCase
{
    private string $sourceRepository;

    private string $sourceRevision;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceRepository = $this->tmpPath('source-repository');
        $this->filesystem->ensureDirectoryExists($this->sourceRepository . '/stubs');
        self::assertTrue($this->filesystem->copyDirectory(
            dirname(__DIR__, 2) . '/stubs/portable',
            $this->sourceRepository . '/stubs/portable',
        ));
        self::assertTrue($this->filesystem->copyDirectory(
            dirname(__DIR__, 2) . '/resources/template-mirror',
            $this->sourceRepository . '/resources/template-mirror',
        ));
        foreach ([
            'src/Portable/CanonicalJson.php',
            'src/Portable/FilesystemPath.php',
            'src/Template/TemplateMirror.php',
            'scripts/export-template.php',
            'scripts/verify-composer-release.php',
            'scripts/verify-template.php',
        ] as $relative) {
            $this->filesystem->ensureDirectoryExists($this->sourceRepository . '/' . dirname($relative));
            self::assertTrue(copy(
                dirname(__DIR__, 2) . '/' . $relative,
                $this->sourceRepository . '/' . $relative,
            ));
        }
        file_put_contents(
            $this->sourceRepository . '/.gitignore',
            "stubs/portable/.env\nstubs/portable/node_modules/\n",
        );
        foreach ([
            ['git', 'init', '--quiet', $this->sourceRepository],
            ['git', '-C', $this->sourceRepository, 'config', 'core.filemode', 'true'],
            ['git', '-C', $this->sourceRepository, 'add', '.'],
            [
                'git', '-C', $this->sourceRepository,
                '-c', 'user.name=Docara Test',
                '-c', 'user.email=docara@example.invalid',
                'commit', '--quiet', '-m', 'Portable starter fixture',
            ],
        ] as $command) {
            $process = new Process($command);
            $process->run();
            self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        }
        $head = new Process(['git', '-C', $this->sourceRepository, 'rev-parse', 'HEAD']);
        $head->run();
        self::assertTrue($head->isSuccessful(), $head->getErrorOutput());
        $this->sourceRevision = trim($head->getOutput());
        $tag = new Process(['git', '-C', $this->sourceRepository, 'tag', 'v9.8.7-test.1', $this->sourceRevision]);
        $tag->run();
        self::assertTrue($tag->isSuccessful(), $tag->getErrorOutput());
    }

    #[Test]
    public function it_exports_a_deterministic_php_only_mirror_from_the_portable_init_payload(): void
    {
        $repository = $this->sourceRepository;
        $destination = $this->tmpPath('mirror');
        $mirror = new TemplateMirror($repository, $this->sourceRevision);

        $written = $mirror->export($destination);

        self::assertNotEmpty($written);
        self::assertFileExists($destination . '/docara.json');
        self::assertFileExists($destination . '/content/ru/index.page.json');
        self::assertFileExists($destination . '/' . TemplateMirror::MANIFEST);
        self::assertStringContainsString(
            "if: github.repository == 'simai/docara-template'",
            (string) file_get_contents($destination . '/.github/workflows/sync.yml'),
        );
        self::assertStringContainsString(
            '[[ ! "$REQUESTED_REVISION" =~ ^[0-9a-f]{40}$ ]]',
            (string) file_get_contents($destination . '/.github/workflows/sync.yml'),
        );
        self::assertFileDoesNotExist($destination . '/package.json');
        self::assertFileDoesNotExist($destination . '/vite.config.js');
        self::assertFileDoesNotExist($destination . '/webpack.mix.js');
        self::assertSame(
            file_get_contents($repository . '/stubs/portable/content/ru/index.md'),
            file_get_contents($destination . '/content/ru/index.md'),
        );
        self::assertSame(['missing' => [], 'changed' => [], 'unexpected' => []], $mirror->diff($destination));

        $manifest = json_decode(
            (string) file_get_contents($destination . '/' . TemplateMirror::MANIFEST),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertSame('docara.template_mirror.v1', $manifest['schema']);
        self::assertSame('stubs/portable', $manifest['generated_from']['starter']);
        self::assertSame($this->sourceRevision, $manifest['generated_from']['revision']);
        self::assertSame('v9.8.7-test.1', $manifest['generated_from']['package_version']);
        self::assertSame(
            hash_file('sha256', $destination . '/content/ru/index.md'),
            $manifest['files']['content/ru/index.md']['sha256'],
        );
        self::assertSame('stubs/portable/content/ru/index.md', $manifest['files']['content/ru/index.md']['source']);
        self::assertSame('100644', $manifest['files']['content/ru/index.md']['mode']);
        self::assertSame(
            'resources/template-mirror/.github/workflows/sync.yml',
            $manifest['files']['.github/workflows/sync.yml']['source'],
        );
        self::assertMatchesRegularExpression('/\A[0-9a-f]{40}\z/', $manifest['generated_from']['support_tree']);
        self::assertStringContainsString('PHP only', (string) file_get_contents($destination . '/README.md'));
        self::assertStringContainsString('simai/docara:v9.8.7-test.1', (string) file_get_contents($destination . '/README.md'));
        self::assertStringNotContainsString('{{DOCARA_PACKAGE_VERSION}}', (string) file_get_contents($destination . '/README.md'));
        self::assertStringNotContainsString('dev-main', (string) file_get_contents($destination . '/README.md'));
        $gitignore = (string) file_get_contents($destination . '/.gitignore');
        self::assertStringContainsString('/.env', $gitignore);
        self::assertStringContainsString('/.env.*.local', $gitignore);

        $workflow = (string) file_get_contents($destination . '/.github/workflows/sync.yml');
        self::assertStringContainsString('permissions: {}', $workflow);
        self::assertStringContainsString('contents: read', $workflow);
        self::assertStringContainsString('contents: write', $workflow);
        self::assertStringContainsString('pull-requests: write', $workflow);
        self::assertStringContainsString('persist-credentials: false', $workflow);
        self::assertStringContainsString('actions/checkout@34e114876b0b11c390a56381ad16ebd13914f8d5', $workflow);
        self::assertStringContainsString('shivammathur/setup-php@b604ade2a87db23f8871b7182e69ec5e75effb45', $workflow);
        self::assertStringContainsString('actions/upload-artifact@ea165f8d65b6e75b540449e92b4886f43607fa02', $workflow);
        self::assertStringContainsString('actions/download-artifact@d3f86a106a0bac45b974a628896c90dbdf5c8093', $workflow);
        self::assertStringContainsString("if: steps.commit.outputs.propose == 'true'", $workflow);
        self::assertStringContainsString('SYNC_BRANCH: docara-sync-${{ needs.generate.outputs.revision }}', $workflow);
        self::assertStringContainsString('gh pr list --state open --head "$SYNC_BRANCH"', $workflow);
        self::assertStringContainsString('--force-with-lease="refs/heads/$SYNC_BRANCH:$REMOTE_SHA"', $workflow);
        self::assertStringNotContainsString('${{ github.run_id }}', $workflow);
        self::assertStringContainsString('gh pr create', $workflow);
        self::assertStringContainsString('Verify artifact against exact canonical Git objects', $workflow);
        self::assertStringContainsString('reconstructed-manifest', $workflow);
        self::assertStringContainsString('cmp "$RUNNER_TEMP/reconstructed-manifest" generated/docara-template-mirror.json', $workflow);
        self::assertStringContainsString("stat -c '%a' generated/docara-template-mirror.json", $workflow);
        self::assertStringContainsString('Required support contract is missing', $workflow);
        self::assertStringContainsString('REQUESTED_RELEASE', $workflow);
        self::assertStringContainsString("printf 'revision=%s\\n'", $workflow);
        self::assertStringContainsString("printf 'release=%s\\n'", $workflow);
        self::assertStringNotContainsString("grep -Eq '^[0-9a-f]{40}$'", $workflow);
        self::assertSame(2, substr_count($workflow, 'git ls-remote --tags https://github.com/simai/docara.git'));
        self::assertSame(2, substr_count($workflow, 'wc -l <'));
        self::assertStringContainsString('Verify release tag before write', $workflow);
        self::assertStringContainsString('.generated_from.package_version', $workflow);
        self::assertStringContainsString('scripts/verify-composer-release.php', $workflow);
        self::assertStringNotContainsString("php -r '", $workflow);
        self::assertStringContainsString("mode != '100644'", $workflow);
        self::assertStringNotContainsString('composer install', $workflow);
        self::assertStringNotContainsString('php canonical/', $workflow);
        self::assertStringNotContainsString('schedule:', $workflow);
        self::assertStringNotContainsString('actions/checkout@v', $workflow);
        self::assertStringNotContainsString('setup-php@v', $workflow);
        self::assertStringNotContainsString('git push origin "HEAD:refs/heads/$DEFAULT_BRANCH"', $workflow);
    }

    #[Test]
    public function exporter_and_verifier_run_from_a_fresh_checkout_without_vendor(): void
    {
        $destination = $this->tmpPath('standalone-mirror');
        $export = new Process([
            PHP_BINARY,
            $this->sourceRepository . '/scripts/export-template.php',
            $destination,
            $this->sourceRevision,
        ]);
        $export->run();
        self::assertTrue($export->isSuccessful(), $export->getErrorOutput() . $export->getOutput());

        $verify = new Process([
            PHP_BINARY,
            $this->sourceRepository . '/scripts/verify-template.php',
            $destination,
            $this->sourceRevision,
        ]);
        $verify->run();
        self::assertTrue($verify->isSuccessful(), $verify->getErrorOutput() . $verify->getOutput());
    }

    #[Test]
    public function it_reports_missing_changed_and_unexpected_mirror_files(): void
    {
        $destination = $this->tmpPath('mirror');
        $mirror = new TemplateMirror($this->sourceRepository, $this->sourceRevision);
        $mirror->export($destination);

        unlink($destination . '/content/ru/landing.md');
        file_put_contents($destination . '/content/ru/index.md', "# Changed\n");
        file_put_contents($destination . '/manual.txt', "not generated\n");

        self::assertSame([
            'missing' => ['content/ru/landing.md'],
            'changed' => ['content/ru/index.md'],
            'unexpected' => ['manual.txt'],
        ], $mirror->diff($destination));
    }

    #[Test]
    public function verifier_rejects_noncanonical_modes_and_special_entries(): void
    {
        $destination = $this->tmpPath('mirror-modes');
        $mirror = new TemplateMirror($this->sourceRepository, $this->sourceRevision);
        $mirror->export($destination);

        chmod($destination . '/content/ru/index.md', 0755);
        self::assertSame(['missing' => [], 'changed' => ['content/ru/index.md'], 'unexpected' => []], $mirror->diff($destination));
        chmod($destination . '/content/ru/index.md', 0644);

        if (! function_exists('posix_mkfifo')) {
            self::markTestSkipped('posix_mkfifo is required for the special-entry regression.');
        }
        self::assertTrue(posix_mkfifo($destination . '/unexpected.pipe', 0600));
        self::assertSame(['missing' => [], 'changed' => [], 'unexpected' => ['unexpected.pipe']], $mirror->diff($destination));
    }

    #[Test]
    public function canonical_source_rejects_executable_git_entries_instead_of_losing_their_mode(): void
    {
        $path = $this->sourceRepository . '/stubs/portable/content/ru/index.md';
        self::assertTrue(chmod($path, 0755));
        foreach ([
            ['git', '-C', $this->sourceRepository, 'add', 'stubs/portable/content/ru/index.md'],
            [
                'git', '-C', $this->sourceRepository,
                '-c', 'user.name=Docara Test',
                '-c', 'user.email=docara@example.invalid',
                'commit', '--quiet', '-m', 'Executable starter fixture',
            ],
        ] as $command) {
            $process = new Process($command);
            $process->run();
            self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        }
        $head = new Process(['git', '-C', $this->sourceRepository, 'rev-parse', 'HEAD']);
        $head->run();
        self::assertTrue($head->isSuccessful(), $head->getErrorOutput());
        $tag = new Process(['git', '-C', $this->sourceRepository, 'tag', 'v9.8.8', trim($head->getOutput())]);
        $tag->run();
        self::assertTrue($tag->isSuccessful(), $tag->getErrorOutput());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('regular non-executable files only');

        (new TemplateMirror($this->sourceRepository, trim($head->getOutput())))->expectedFiles();
    }

    #[Test]
    public function canonical_source_cannot_claim_the_reserved_generated_manifest_path(): void
    {
        file_put_contents(
            $this->sourceRepository . '/stubs/portable/' . TemplateMirror::MANIFEST,
            "untrusted manifest payload\n",
        );
        foreach ([
            ['git', '-C', $this->sourceRepository, 'add', 'stubs/portable/' . TemplateMirror::MANIFEST],
            [
                'git', '-C', $this->sourceRepository,
                '-c', 'user.name=Docara Test',
                '-c', 'user.email=docara@example.invalid',
                'commit', '--quiet', '-m', 'Reserved manifest fixture',
            ],
        ] as $command) {
            $process = new Process($command);
            $process->run();
            self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        }
        $head = new Process(['git', '-C', $this->sourceRepository, 'rev-parse', 'HEAD']);
        $head->run();
        self::assertTrue($head->isSuccessful(), $head->getErrorOutput());
        $tag = new Process(['git', '-C', $this->sourceRepository, 'tag', 'v9.8.9', trim($head->getOutput())]);
        $tag->run();
        self::assertTrue($tag->isSuccessful(), $tag->getErrorOutput());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reserved mirror manifest path');

        (new TemplateMirror($this->sourceRepository, trim($head->getOutput())))->expectedFiles();
    }

    #[Test]
    public function unexpected_manifest_fields_are_detected_as_manifest_drift(): void
    {
        $destination = $this->tmpPath('manifest-injection');
        $mirror = new TemplateMirror($this->sourceRepository, $this->sourceRevision);
        $mirror->export($destination);
        $manifestPath = $destination . '/' . TemplateMirror::MANIFEST;
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $manifest['unverified_artifact_payload'] = ['approved' => false];
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        self::assertSame([
            'missing' => [],
            'changed' => [TemplateMirror::MANIFEST],
            'unexpected' => [],
        ], $mirror->diff($destination));
    }

    #[Test]
    public function export_never_overwrites_an_existing_destination(): void
    {
        $destination = $this->tmpPath('mirror');
        $this->filesystem->ensureDirectoryExists($destination);
        file_put_contents($destination . '/owner-file.txt', "preserve\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('never overwrites existing files');

        (new TemplateMirror($this->sourceRepository, $this->sourceRevision))->export($destination);
    }

    #[Test]
    public function repository_and_destination_paths_cannot_hide_symlinks_with_dot_segments(): void
    {
        $fakeRepository = $this->tmpPath('fake-repository');
        $this->filesystem->ensureDirectoryExists($fakeRepository);
        $repositoryLink = $this->tmpPath('repository-link');
        self::assertTrue(symlink($fakeRepository, $repositoryLink));
        $repositoryFailure = null;
        try {
            new TemplateMirror($repositoryLink . '/.', $this->sourceRevision);
        } catch (RuntimeException $exception) {
            $repositoryFailure = $exception;
        } finally {
            unlink($repositoryLink);
        }
        self::assertInstanceOf(RuntimeException::class, $repositoryFailure);
        self::assertStringContainsString('symbolic link', $repositoryFailure->getMessage());

        $mirror = new TemplateMirror($this->sourceRepository, $this->sourceRevision);
        $realDestination = $this->tmpPath('real-destination');
        $this->filesystem->ensureDirectoryExists($realDestination);
        $destinationLink = $this->tmpPath('destination-link');
        self::assertTrue(symlink($realDestination, $destinationLink));
        $destinationFailure = null;
        try {
            $mirror->export($destinationLink . '/.');
        } catch (RuntimeException $exception) {
            $destinationFailure = $exception;
        } finally {
            unlink($destinationLink);
        }
        self::assertInstanceOf(RuntimeException::class, $destinationFailure);
        self::assertStringContainsString('symbolic link', $destinationFailure->getMessage());
    }

    #[Test]
    public function mirror_requires_an_exact_source_revision(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exact 40-character commit SHA');

        new TemplateMirror($this->sourceRepository, 'main');
    }

    #[Test]
    public function mirror_rejects_a_revision_that_is_not_checkout_head(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not match checkout HEAD');

        new TemplateMirror($this->sourceRepository, str_repeat('b', 40));
    }

    #[Test]
    public function mirror_rejects_an_unreleased_source_revision(): void
    {
        $deleteTag = new Process(['git', '-C', $this->sourceRepository, 'tag', '-d', 'v9.8.7-test.1']);
        $deleteTag->run();
        self::assertTrue($deleteTag->isSuccessful(), $deleteTag->getErrorOutput());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires exactly one SemVer release tag');

        new TemplateMirror($this->sourceRepository, $this->sourceRevision);
    }

    #[Test]
    public function mirror_rejects_ambiguous_release_tags_for_one_revision(): void
    {
        $secondTag = new Process(['git', '-C', $this->sourceRepository, 'tag', 'v9.8.7-test.2', $this->sourceRevision]);
        $secondTag->run();
        self::assertTrue($secondTag->isSuccessful(), $secondTag->getErrorOutput());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires exactly one SemVer release tag');

        new TemplateMirror($this->sourceRepository, $this->sourceRevision);
    }

    #[Test]
    public function mirror_rejects_a_dirty_source_checkout(): void
    {
        file_put_contents($this->sourceRepository . '/stubs/portable/content/ru/index.md', "# Dirty\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires a clean Docara source checkout');

        new TemplateMirror($this->sourceRepository, $this->sourceRevision);
    }

    #[Test]
    public function ignored_uncommitted_files_never_enter_the_exact_tree_export(): void
    {
        file_put_contents($this->sourceRepository . '/stubs/portable/.env', "TOKEN=do-not-export\n");
        $this->filesystem->ensureDirectoryExists($this->sourceRepository . '/stubs/portable/node_modules');
        file_put_contents($this->sourceRepository . '/stubs/portable/node_modules/junk.js', "junk\n");
        $destination = $this->tmpPath('ignored-files-mirror');

        (new TemplateMirror($this->sourceRepository, $this->sourceRevision))->export($destination);

        self::assertFileDoesNotExist($destination . '/.env');
        self::assertDirectoryDoesNotExist($destination . '/node_modules');
    }

    #[Test]
    public function mirror_destination_must_remain_outside_the_source_checkout(): void
    {
        $destination = $this->sourceRepository . '/stubs/portable/generated-mirror';

        try {
            (new TemplateMirror($this->sourceRepository, $this->sourceRevision))->export($destination);
            self::fail('Mirror unexpectedly exported inside its canonical source.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('outside the Docara source checkout', $exception->getMessage());
        }

        self::assertDirectoryDoesNotExist($destination);
    }

    #[Test]
    public function composer_release_verifier_requires_the_exact_version_and_source_revision(): void
    {
        $revision = str_repeat('a', 40);
        $lock = $this->tmpPath('composer.lock');
        file_put_contents($lock, json_encode([
            'packages' => [[
                'name' => 'simai/docara',
                'version' => 'v9.8.7',
                'source' => ['reference' => $revision],
            ]],
            'packages-dev' => [],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $script = dirname(__DIR__, 2) . '/scripts/verify-composer-release.php';

        $accepted = new Process([PHP_BINARY, $script, $lock, 'v9.8.7', $revision]);
        $accepted->run();
        self::assertTrue($accepted->isSuccessful(), $accepted->getErrorOutput());

        $wrongRevision = new Process([PHP_BINARY, $script, $lock, 'v9.8.7', str_repeat('b', 40)]);
        $wrongRevision->run();
        self::assertFalse($wrongRevision->isSuccessful());
        self::assertStringContainsString(
            'does not resolve to the requested Docara revision',
            $wrongRevision->getErrorOutput(),
        );

        $wrongRelease = new Process([PHP_BINARY, $script, $lock, 'v9.8.8', $revision]);
        $wrongRelease->run();
        self::assertFalse($wrongRelease->isSuccessful());

        $duplicate = json_decode((string) file_get_contents($lock), true, flags: JSON_THROW_ON_ERROR);
        $duplicate['packages-dev'] = $duplicate['packages'];
        file_put_contents(
            $lock,
            json_encode($duplicate, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
        $duplicatePackage = new Process([PHP_BINARY, $script, $lock, 'v9.8.7', $revision]);
        $duplicatePackage->run();
        self::assertFalse($duplicatePackage->isSuccessful());
    }
}
