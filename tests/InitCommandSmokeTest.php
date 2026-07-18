<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InitCommandSmokeTest extends TestCase
{
    #[Test]
    public function init_update_respects_custom_docs_dir_and_copies_core(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-smoke-' . bin2hex(random_bytes(4));
        $docsDir = $tmp . '/source/customdocs';
        $coreFile = $tmp . '/source/_core/bootstrap.php';
        $this->assertTrue(mkdir($docsDir, 0777, true));
        $this->assertTrue(mkdir($tmp . '/source/Helpers', 0777, true));

        $original = "ORIGINAL\n";
        file_put_contents($docsDir . '/index.md', $original);
        file_put_contents($tmp . '/source/index.blade.md', "PROJECT ENTRYPOINT\n");
        file_put_contents($tmp . '/source/Helpers/Project.php', "PROJECT HELPER\n");

        file_put_contents($tmp . '/.env', <<<'ENV'
DOCS_DIR=customdocs
AZURE_KEY=
AZURE_REGION=
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
ENV);

        try {
            $binary = realpath(__DIR__ . '/../vendor/bin/docara')
                ?: realpath(__DIR__ . '/../docara')
                ?: 'docara';
            $env = ['APP_ENV' => 'test', 'DOCS_DIR' => 'customdocs', 'DOCARA_SKIP_FRONTEND_INSTALL' => 'true'];
            $process = new Process([PHP_BINARY, $binary, 'init', '--update'], $tmp, $env);
            $process->run();

            $this->assertTrue($process->isSuccessful(), "docara init --update failed: {$process->getErrorOutput()} {$process->getOutput()}");
            $this->assertSame($original, file_get_contents($docsDir . '/index.md'), 'Existing docs were overwritten');
            $this->assertSame("PROJECT ENTRYPOINT\n", file_get_contents($tmp . '/source/index.blade.md'));
            $this->assertSame("PROJECT HELPER\n", file_get_contents($tmp . '/source/Helpers/Project.php'));
            $this->assertFileDoesNotExist($tmp . '/source/Helpers/CustomTags/ResponsiveTags.php');
            $this->assertFileExists($coreFile, 'Bundled _core was not copied');
            $this->assertFileExists($tmp . '/package.json', 'Root package contract was not copied');
            $this->assertFileExists($tmp . '/yarn.lock', 'Root frozen lockfile was not copied');
            $package = json_decode((string) file_get_contents($tmp . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('yarn@1.22.22', $package['packageManager'] ?? null);
            $this->assertSame('^20.19.0 || >=22.12.0', $package['engines']['node'] ?? null);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_an_npm_only_project_before_any_mutation(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-npm-lock-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        $lock = "{\"lockfileVersion\":3}\n";
        file_put_contents($tmp . '/package-lock.json', $lock);

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin', true);

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString(
                'Legacy npm-only frontend contract detected',
                $process->getErrorOutput() . $process->getOutput(),
            );
            $this->assertSame($lock, file_get_contents($tmp . '/package-lock.json'));
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertFileDoesNotExist($tmp . '/yarn.lock');
            $this->assertFileDoesNotExist($tmp . '/package.json');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_update_preserves_project_identity_and_additional_scripts_but_refreshes_the_canonical_frontend_graph(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-package-merge-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp . '/source/docs', 0777, true));
        file_put_contents($tmp . '/.env', $this->environment());
        file_put_contents($tmp . '/source/docs/index.md', "# Existing\n");
        file_put_contents($tmp . '/yarn.lock', "# stale project lock\n");
        file_put_contents($tmp . '/package.json', json_encode([
            'name' => 'custom-docs',
            'version' => '2.3.4-beta-1+build.7',
            'description' => 'Project identity',
            'scripts' => [
                'prod' => 'legacy-build',
                'validate:graph' => 'node scripts/validate.js',
            ],
            'devDependencies' => ['vite' => '^1.0.0'],
            'config' => ['project-owned' => true],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        try {
            $binary = realpath(__DIR__ . '/../docara') ?: 'docara';
            $process = new Process(
                [PHP_BINARY, $binary, 'init', '--update', '--no-interaction'],
                $tmp,
                [
                    'APP_ENV' => 'test',
                    'DOCS_DIR' => 'docs',
                    'DOCARA_SKIP_FRONTEND_INSTALL' => 'true',
                ],
            );
            $process->run();

            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput() . $process->getOutput());
            $package = json_decode((string) file_get_contents($tmp . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
            $canonical = json_decode(
                (string) file_get_contents(__DIR__ . '/../stubs/site/source/_core/package.json'),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            $this->assertSame('custom-docs', $package['name']);
            $this->assertSame('2.3.4-beta-1+build.7', $package['version']);
            $this->assertSame('Project identity', $package['description']);
            $this->assertSame(['project-owned' => true], $package['config']);
            $this->assertSame('node scripts/validate.js', $package['scripts']['validate:graph']);
            $this->assertSame($canonical['scripts']['prod'], $package['scripts']['prod']);
            $this->assertSame($canonical['devDependencies'], $package['devDependencies']);
            $this->assertSame(
                file_get_contents(__DIR__ . '/../stubs/site/source/_core/yarn.lock'),
                file_get_contents($tmp . '/yarn.lock'),
            );
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_two_existing_frontend_lockfiles_before_any_mutation(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-dual-lock-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        file_put_contents($tmp . '/package-lock.json', "{}\n");
        file_put_contents($tmp . '/yarn.lock', "# existing\n");

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString(
                'keep exactly one lockfile',
                $process->getErrorOutput() . $process->getOutput(),
            );
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertFileDoesNotExist($tmp . '/package.json');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_invalid_package_json_before_any_mutation(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-invalid-package-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp . '/build_production', 0777, true));
        $package = "not-json\n";
        $lock = "# project lock\n";
        file_put_contents($tmp . '/package.json', $package);
        file_put_contents($tmp . '/yarn.lock', $lock);
        file_put_contents($tmp . '/build_production/marker.txt', "keep\n");

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('must be a valid JSON object', $process->getErrorOutput() . $process->getOutput());
            $this->assertSame($package, file_get_contents($tmp . '/package.json'));
            $this->assertSame($lock, file_get_contents($tmp . '/yarn.lock'));
            $this->assertFileExists($tmp . '/build_production/marker.txt');
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_project_dependency_extensions_before_any_mutation(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-extra-dependency-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp . '/build_production', 0777, true));
        $package = json_encode([
            'name' => 'custom-docs',
            'dependencies' => ['custom-runtime' => '^1.0.0'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $lock = "# project lock\n";
        file_put_contents($tmp . '/package.json', $package);
        file_put_contents($tmp . '/yarn.lock', $lock);
        file_put_contents($tmp . '/build_production/marker.txt', "keep\n");

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('extends canonical dependencies', $process->getErrorOutput() . $process->getOutput());
            $this->assertSame($package, file_get_contents($tmp . '/package.json'));
            $this->assertSame($lock, file_get_contents($tmp . '/yarn.lock'));
            $this->assertFileExists($tmp . '/build_production/marker.txt');
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_noncanonical_package_manager_and_install_metadata_before_tool_launch(): void
    {
        $cases = [
            'package-manager' => [
                'package' => ['name' => 'custom-docs', 'packageManager' => 'yarn@4.9.1'],
                'message' => 'packageManager does not match',
            ],
            'install-config' => [
                'package' => ['name' => 'custom-docs', 'installConfig' => ['pnp' => true]],
                'message' => 'unsupported frontend graph key [installConfig]',
            ],
            'flat-install' => [
                'package' => ['name' => 'custom-docs', 'flat' => true],
                'message' => 'unsupported frontend graph key [flat]',
            ],
            'node-engine' => [
                'package' => ['name' => 'custom-docs', 'engines' => ['node' => '>=18']],
                'message' => 'Node.js engine does not match',
            ],
        ];

        foreach ($cases as $name => $case) {
            $tmp = sys_get_temp_dir() . '/docara-install-metadata-' . $name . '-' . bin2hex(random_bytes(4));
            $this->assertTrue(mkdir($tmp . '/build_production', 0777, true));
            $package = json_encode($case['package'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents($tmp . '/package.json', $package);
            file_put_contents($tmp . '/yarn.lock', "# project lock\n");
            file_put_contents($tmp . '/build_production/marker.txt', "keep\n");

            try {
                $process = $this->runInit($tmp, '/path/that/must/not/be-used');

                $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
                $this->assertStringContainsString($case['message'], $process->getErrorOutput() . $process->getOutput());
                $this->assertSame($package, file_get_contents($tmp . '/package.json'));
                $this->assertFileExists($tmp . '/build_production/marker.txt');
                $this->assertFileDoesNotExist($tmp . '/.env');
                $this->assertDirectoryDoesNotExist($tmp . '/source');
            } finally {
                $this->deleteDir($tmp);
            }
        }
    }

    #[Test]
    public function init_rejects_a_symlinked_lockfile_without_touching_its_external_target(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-symlink-lock-' . bin2hex(random_bytes(4));
        $outside = sys_get_temp_dir() . '/docara-external-lock-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        $sentinel = "# external sentinel\n";
        file_put_contents($outside, $sentinel);
        $this->assertTrue(symlink($outside, $tmp . '/yarn.lock'));

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('Frontend lockfile must be one regular project file', $process->getErrorOutput() . $process->getOutput());
            $this->assertTrue(is_link($tmp . '/yarn.lock'));
            $this->assertSame($sentinel, file_get_contents($outside));
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
            @unlink($outside);
        }
    }

    #[Test]
    public function init_rejects_project_lifecycle_scripts_before_they_can_execute(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-lifecycle-script-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        $package = json_encode([
            'name' => 'custom-docs',
            'scripts' => ['postinstall' => 'touch should-not-exist'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $lock = "# project lock\n";
        file_put_contents($tmp . '/package.json', $package);
        file_put_contents($tmp . '/yarn.lock', $lock);

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('lifecycle scripts', $process->getErrorOutput() . $process->getOutput());
            $this->assertSame($package, file_get_contents($tmp . '/package.json'));
            $this->assertSame($lock, file_get_contents($tmp . '/yarn.lock'));
            $this->assertFileDoesNotExist($tmp . '/should-not-exist');
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_a_dangling_package_symlink_without_creating_its_external_target(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-dangling-package-' . bin2hex(random_bytes(4));
        $outside = sys_get_temp_dir() . '/docara-external-package-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        $this->assertFileDoesNotExist($outside);
        $this->assertTrue(symlink($outside, $tmp . '/package.json'));
        $lock = "# project lock\n";
        file_put_contents($tmp . '/yarn.lock', $lock);

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('regular project file', $process->getErrorOutput() . $process->getOutput());
            $this->assertTrue(is_link($tmp . '/package.json'));
            $this->assertFileDoesNotExist($outside);
            $this->assertSame($lock, file_get_contents($tmp . '/yarn.lock'));
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
            @unlink($outside);
        }
    }

    #[Test]
    public function init_rejects_a_dangling_package_lock_symlink_before_any_mutation(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-dangling-npm-lock-' . bin2hex(random_bytes(4));
        $outside = sys_get_temp_dir() . '/docara-external-npm-lock-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        $this->assertFileDoesNotExist($outside);
        $this->assertTrue(symlink($outside, $tmp . '/package-lock.json'));

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin', true);

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('Frontend lockfile must be one regular project file', $process->getErrorOutput() . $process->getOutput());
            $this->assertTrue(is_link($tmp . '/package-lock.json'));
            $this->assertFileDoesNotExist($outside);
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertFileDoesNotExist($tmp . '/package.json');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
            @unlink($outside);
        }
    }

    #[Test]
    public function init_rejects_project_yarn_configuration_before_yarn_path_can_execute(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-yarn-path-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        file_put_contents($tmp . '/.yarnrc', "yarn-path \"./fake-yarn.js\"\n");
        file_put_contents(
            $tmp . '/fake-yarn.js',
            "require('fs').writeFileSync('yarn-path-executed', 'yes');\n",
        );

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('Unsupported frontend configuration [.yarnrc]', $process->getErrorOutput() . $process->getOutput());
            $this->assertFileDoesNotExist($tmp . '/yarn-path-executed');
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertFileDoesNotExist($tmp . '/package.json');
            $this->assertFileDoesNotExist($tmp . '/yarn.lock');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_yarnclean_before_any_mutation(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-yarnclean-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp, 0777, true));
        file_put_contents($tmp . '/.yarnclean', "*.md\n");

        try {
            $process = $this->runInit($tmp, '/path/that/must/not/be-used');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('Unsupported frontend configuration [.yarnclean]', $process->getErrorOutput() . $process->getOutput());
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertFileDoesNotExist($tmp . '/package.json');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_allows_internal_node_modules_links_but_keeps_them_confined(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-internal-node-link-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp . '/node_modules/.bin', 0777, true));
        $this->assertTrue(mkdir($tmp . '/node_modules/tool/bin', 0777, true));
        file_put_contents($tmp . '/node_modules/tool/bin/tool.js', "console.log('tool');\n");
        $this->assertTrue(symlink('../tool/bin/tool.js', $tmp . '/node_modules/.bin/tool'));

        try {
            $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin', true);

            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertTrue(is_link($tmp . '/node_modules/.bin/tool'));
            $this->assertSame("console.log('tool');\n", file_get_contents($tmp . '/node_modules/tool/bin/tool.js'));
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function project_env_cannot_inject_node_code_or_change_the_canonical_install_mode(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-frontend-env-' . bin2hex(random_bytes(4));
        $bin = $tmp . '/bin';
        $this->assertTrue(mkdir($bin, 0777, true));
        $hook = $tmp . '/node-options-hook.js';
        $marker = $tmp . '/node-options-executed';
        $capture = $tmp . '/frontend-environment.json';
        file_put_contents($hook, 'require(\'fs\').writeFileSync(' . json_encode($marker) . ", 'executed');\n");
        file_put_contents(
            $bin . '/yarn',
            "#!/usr/bin/env node\n"
            . "const fs = require('fs');\n"
            . "if (process.argv.includes('--version')) { console.log('1.22.22'); process.exit(0); }\n"
            . 'fs.writeFileSync(' . json_encode($capture) . ", JSON.stringify({nodeOptions: process.env.NODE_OPTIONS || null, nodeEnv: process.env.NODE_ENV || null, yarnIgnoreScripts: process.env.YARN_IGNORE_SCRIPTS || null, args: process.argv.slice(2)}));\n",
        );
        chmod($bin . '/yarn', 0755);
        file_put_contents($tmp . '/.env', implode("\n", [
            'DOCS_DIR=docs',
            'NODE_OPTIONS=--require=' . $hook,
            'NODE_ENV=production',
            'YARN_IGNORE_SCRIPTS=1',
            '',
        ]));

        try {
            $process = $this->runInit($tmp, $bin . ':' . (getenv('PATH') ?: '/usr/bin:/bin'));

            $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertFileDoesNotExist($marker);
            $environment = json_decode((string) file_get_contents($capture), true, flags: JSON_THROW_ON_ERROR);
            $this->assertNull($environment['nodeOptions']);
            $this->assertNull($environment['nodeEnv']);
            $this->assertNull($environment['yarnIgnoreScripts']);
            $this->assertContains('--production=false', $environment['args']);
            $this->assertContains('--frozen-lockfile', $environment['args']);
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function poisoned_materialized_core_cannot_override_the_installed_canonical_root_contract(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-poisoned-core-' . bin2hex(random_bytes(4));
        $this->assertTrue(mkdir($tmp . '/source/_core', 0777, true));
        $this->assertTrue(mkdir($tmp . '/source/docs', 0777, true));
        file_put_contents($tmp . '/.env', $this->environment());
        file_put_contents($tmp . '/source/docs/index.md', "# Existing\n");
        file_put_contents($tmp . '/source/_core/package.json', json_encode([
            'name' => 'poison',
            'scripts' => ['postinstall' => 'touch poisoned'],
            'dependencies' => ['unexpected-package' => '*'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        file_put_contents($tmp . '/source/_core/yarn.lock', "# poisoned lock\n");
        file_put_contents($tmp . '/package.json', json_encode([
            'name' => 'project-docs',
            'version' => '4.5.6',
            'scripts' => ['validate:project' => 'echo validate'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        file_put_contents($tmp . '/yarn.lock', "# stale root lock\n");

        try {
            $binary = realpath(__DIR__ . '/../docara') ?: 'docara';
            $process = new Process(
                [PHP_BINARY, $binary, 'init', '--update', '--no-interaction'],
                $tmp,
                [
                    'APP_ENV' => 'test',
                    'DOCS_DIR' => 'docs',
                    'DOCARA_SKIP_FRONTEND_INSTALL' => 'true',
                ],
            );
            $process->run();

            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput() . $process->getOutput());
            $package = json_decode((string) file_get_contents($tmp . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
            $canonical = json_decode(
                (string) file_get_contents(__DIR__ . '/../stubs/site/source/_core/package.json'),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            $this->assertSame('project-docs', $package['name']);
            $this->assertSame('4.5.6', $package['version']);
            $this->assertSame('echo validate', $package['scripts']['validate:project']);
            $this->assertArrayNotHasKey('postinstall', $package['scripts']);
            $this->assertArrayNotHasKey('unexpected-package', $package['dependencies']);
            $this->assertSame($canonical['dependencies'], $package['dependencies']);
            $this->assertSame(
                file_get_contents(__DIR__ . '/../stubs/site/source/_core/yarn.lock'),
                file_get_contents($tmp . '/yarn.lock'),
            );
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_external_symlink_boundaries_before_cleanup_or_scaffolding(): void
    {
        foreach (['source', 'build_production', '.cache', 'node_modules', 'archived', 'config.php', '.env', 'yarn-error.log'] as $boundary) {
            $suffix = bin2hex(random_bytes(4));
            $tmp = sys_get_temp_dir() . '/docara-boundary-' . $suffix;
            $outside = sys_get_temp_dir() . '/docara-boundary-outside-' . $suffix;
            $directoryBoundary = in_array($boundary, ['source', 'build_production', '.cache', 'node_modules', 'archived'], true);
            $this->assertTrue(mkdir($tmp, 0777, true));
            if ($directoryBoundary) {
                $this->assertTrue(mkdir($outside, 0777, true));
                file_put_contents($outside . '/sentinel.txt', "keep\n");
            } else {
                file_put_contents($outside, "keep\n");
            }
            $this->assertTrue(symlink($outside, $tmp . '/' . $boundary));

            try {
                $process = $this->runInit($tmp, getenv('PATH') ?: '/usr/bin:/bin', true);

                $this->assertSame(1, $process->getExitCode(), $boundary . ': ' . $process->getErrorOutput() . $process->getOutput());
                $this->assertStringContainsString('Unsafe init boundary', $process->getErrorOutput() . $process->getOutput());
                $this->assertTrue(is_link($tmp . '/' . $boundary));
                if ($directoryBoundary) {
                    $this->assertSame("keep\n", file_get_contents($outside . '/sentinel.txt'));
                } else {
                    $this->assertSame("keep\n", file_get_contents($outside));
                }
                if ($boundary !== '.env') {
                    $this->assertFileDoesNotExist($tmp . '/.env');
                }
                $this->assertFileDoesNotExist($tmp . '/package.json');
                $this->assertFileDoesNotExist($tmp . '/yarn.lock');
            } finally {
                $this->deleteDir($tmp);
                $directoryBoundary ? $this->deleteDir($outside) : @unlink($outside);
            }
        }
    }

    #[Test]
    public function init_propagates_a_frozen_frontend_install_failure(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-yarn-failure-' . bin2hex(random_bytes(4));
        $bin = $tmp . '/bin';
        $this->assertTrue(mkdir($bin, 0777, true));
        file_put_contents($tmp . '/.env', $this->environment());
        $this->writeFakeNode($bin, 'v22.12.0');
        file_put_contents($bin . '/yarn', "#!/bin/sh\nif [ \"\$1\" = \"--no-default-rc\" ] && [ \"\$2\" = \"--version\" ]; then echo 1.22.22; exit 0; fi\nexit 23\n");
        chmod($bin . '/yarn', 0755);

        try {
            $process = $this->runInit($tmp, $bin . ':/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString(
                'yarn install failed',
                $process->getErrorOutput() . $process->getOutput(),
            );
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_rejects_the_wrong_yarn_version_before_any_mutation(): void
    {
        $tmp = sys_get_temp_dir() . '/docara-wrong-yarn-' . bin2hex(random_bytes(4));
        $bin = $tmp . '/bin';
        $this->assertTrue(mkdir($bin, 0777, true));
        $this->writeFakeNode($bin, 'v22.12.0');
        file_put_contents($bin . '/yarn', "#!/bin/sh\necho 1.22.19\n");
        chmod($bin . '/yarn', 0755);

        try {
            $process = $this->runInit($tmp, $bin . ':/usr/bin:/bin');

            $this->assertSame(1, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('Yarn 1.22.22 is required', $process->getErrorOutput() . $process->getOutput());
            $this->assertFileDoesNotExist($tmp . '/.env');
            $this->assertFileDoesNotExist($tmp . '/package.json');
            $this->assertFileDoesNotExist($tmp . '/yarn.lock');
            $this->assertDirectoryDoesNotExist($tmp . '/source');
        } finally {
            $this->deleteDir($tmp);
        }
    }

    #[Test]
    public function init_enforces_the_exact_node_engine_boundaries_before_mutation(): void
    {
        $versions = [
            'v20.18.99' => false,
            'v21.7.3' => false,
            'v22.11.99' => false,
            'v20.19.0' => true,
            'v22.12.0' => true,
            'v23.1.0' => true,
        ];

        foreach ($versions as $version => $supported) {
            $tmp = sys_get_temp_dir() . '/docara-node-boundary-' . str_replace('.', '-', $version) . '-' . bin2hex(random_bytes(4));
            $bin = $tmp . '/bin';
            $this->assertTrue(mkdir($bin, 0777, true));
            $this->writeFakeNode($bin, $version);
            file_put_contents($bin . '/yarn', "#!/bin/sh\necho 1.22.22\n");
            chmod($bin . '/yarn', 0755);

            try {
                $process = $this->runInit($tmp, $bin . ':/usr/bin:/bin');

                $this->assertSame($supported ? 0 : 1, $process->getExitCode(), $version . ': ' . $process->getErrorOutput() . $process->getOutput());
                if ($supported) {
                    $this->assertFileExists($tmp . '/package.json');
                    $this->assertFileExists($tmp . '/yarn.lock');
                } else {
                    $this->assertStringContainsString('Node.js 20.19+ or 22.12+ is required', $process->getErrorOutput() . $process->getOutput());
                    $this->assertFileDoesNotExist($tmp . '/.env');
                    $this->assertFileDoesNotExist($tmp . '/package.json');
                    $this->assertFileDoesNotExist($tmp . '/yarn.lock');
                    $this->assertDirectoryDoesNotExist($tmp . '/source');
                }
            } finally {
                $this->deleteDir($tmp);
            }
        }
    }

    private function writeFakeNode(string $bin, string $version): void
    {
        file_put_contents($bin . '/node', "#!/bin/sh\necho {$version}\n");
        chmod($bin . '/node', 0755);
    }

    private function runInit(string $tmp, string $path, bool $skipFrontendInstall = false): Process
    {
        $binary = realpath(__DIR__ . '/../docara') ?: 'docara';
        $environment = ['APP_ENV' => 'test', 'DOCS_DIR' => 'docs', 'PATH' => $path];
        if ($skipFrontendInstall) {
            $environment['DOCARA_SKIP_FRONTEND_INSTALL'] = 'true';
        }
        $process = new Process(
            [PHP_BINARY, $binary, 'init', '--no-interaction'],
            $tmp,
            $environment,
        );
        $process->run();

        return $process;
    }

    private function environment(): string
    {
        return <<<'ENV'
DOCS_DIR=docs
AZURE_KEY=
AZURE_REGION=
AZURE_ENDPOINT=https://api.cognitive.microsofttranslator.com
ENV;
    }

    private function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }
}
