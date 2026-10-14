<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simai\Docara\Framework\FrameworkComponentException;
use Simai\Docara\Framework\FrameworkLock;
use Simai\Docara\Framework\FrameworkPackageReceipt;

/**
 * A product component package is taken by its package receipt
 * (`simai.framework.product-package-receipt` version 1, written by ui-builder):
 * the name, revision and required free release come from the receipt, and
 * every file is checked against its hashes, read from the receipt's archive or
 * from an output directory.
 */
final class FrameworkPackageReceiptTest extends TestCase
{
    private const PACKAGE_REVISION = '98ad1a012a1750300a05cecef67ed2cbb91aa462';

    private ?string $temporaryRoot = null;

    protected function tearDown(): void
    {
        if ($this->temporaryRoot !== null && is_dir($this->temporaryRoot)) {
            exec('rm -rf ' . escapeshellarg($this->temporaryRoot));
        }
        $this->temporaryRoot = null;
        parent::tearDown();
    }

    #[Test]
    public function a_receipt_names_the_package_its_revision_and_the_release_it_requires(): void
    {
        $receipt = FrameworkPackageReceipt::fromArray($this->receipt($this->packageFiles()));

        self::assertSame('larena', $receipt->package());
        self::assertSame(self::PACKAGE_REVISION, $receipt->packageRevision());
        self::assertSame('ui-9fb0297bb5c2', $receipt->frameworkRelease());
        self::assertSame(['smart/rule.js', 'smart/table/js/table.js', 'smart/table/css/table.css'], $receipt->requiredFiles());
    }

    #[Test]
    public function the_files_are_read_from_the_archive_or_the_output_directory_and_agree(): void
    {
        [$receipt, $directory, $archive] = $this->packageOutput($this->packageFiles());

        $fromArchive = $receipt->readArchive($archive);
        $fromDirectory = $receipt->readDirectory($directory);

        self::assertSame($this->packageFiles(), $fromArchive);
        self::assertSame($fromArchive, $fromDirectory);
    }

    #[Test]
    public function the_projection_is_a_lock_entry_beside_the_release_the_receipt_names(): void
    {
        $receipt = FrameworkPackageReceipt::fromArray($this->receipt($this->packageFiles()));
        $projection = $receipt->projection(['smart/rule.js' => ['sha256' => hash('sha256', 'rule')]]);

        self::assertSame('docara.framework_package_projection', $projection['schema']);
        self::assertSame(2, $projection['schema_version']);
        self::assertSame('ui-9fb0297bb5c2', $projection['requires']);
        self::assertSame('_docara/framework-packages/larena/' . self::PACKAGE_REVISION, $projection['mount']);
        self::assertSame([
            'repository' => 'simai/larena-ui',
            'revision' => self::PACKAGE_REVISION,
            'path' => 'components',
            'tree' => str_repeat('3', 40),
        ], $projection['receipt']['package_source']);
    }

    #[Test]
    public function receipts_that_cannot_be_trusted_are_refused(): void
    {
        $files = $this->packageFiles();
        $cases = [
            'FRAMEWORK_PACKAGE_RECEIPT_SCHEMA_UNSUPPORTED' => static fn (array $receipt): array => ['schema_version' => 2] + $receipt,
            'FRAMEWORK_PACKAGE_RECEIPT_RELEASE_MISSING' => static function (array $receipt): array {
                $receipt['framework']['release'] = null;

                return $receipt;
            },
            'FRAMEWORK_PACKAGE_RECEIPT_MANIFEST_MISMATCH' => static function (array $receipt): array {
                $receipt['files'][0]['size']++;

                return $receipt;
            },
            'FRAMEWORK_PACKAGE_RECEIPT_REQUIRED_FILE_UNLISTED' => static function (array $receipt): array {
                $receipt['required_files'][1]['sha256'] = str_repeat('0', 64);

                return $receipt;
            },
        ];
        foreach ($cases as $code => $mutate) {
            try {
                FrameworkPackageReceipt::fromArray($mutate($this->receipt($files)));
                self::fail('Accepted: ' . $code);
            } catch (FrameworkComponentException $exception) {
                self::assertSame($code, $exception->errorCode);
            }
        }

        $receipt = $this->receipt($files);
        $receipt['loader']['declaration'] = 'SF_BOOT_CONFIG.packages.pro.base';
        try {
            FrameworkPackageReceipt::fromArray($receipt);
            self::fail('Accepted a loader declaration of another package.');
        } catch (FrameworkComponentException $exception) {
            self::assertSame('FRAMEWORK_PACKAGE_RECEIPT_INVALID', $exception->errorCode);
        }
    }

    #[Test]
    public function package_files_that_disagree_with_the_receipt_are_refused(): void
    {
        [$receipt, $directory, $archive] = $this->packageOutput($this->packageFiles());

        file_put_contents($archive, "\0", FILE_APPEND);
        $this->assertRefused('FRAMEWORK_PACKAGE_ARCHIVE_HASH_MISMATCH', static fn () => $receipt->readArchive($archive));

        file_put_contents($directory . '/smart/table/js/table.js', "/* changed */\n");
        $this->assertRefused('FRAMEWORK_PACKAGE_FILE_HASH_MISMATCH', static fn () => $receipt->readDirectory($directory));

        [$receipt, $directory] = $this->packageOutput($this->packageFiles());
        file_put_contents($directory . '/smart/table/js/extra.js', "/* extra */\n");
        $this->assertRefused('FRAMEWORK_PACKAGE_FILE_NOT_IN_RECEIPT', static fn () => $receipt->readDirectory($directory));

        [$receipt, $directory] = $this->packageOutput($this->packageFiles());
        unlink($directory . '/smart/rule.js.gz');
        $this->assertRefused('FRAMEWORK_PACKAGE_FILE_MISSING', static fn () => $receipt->readDirectory($directory));
    }

    #[Test]
    public function the_sync_script_lays_the_package_and_records_it_in_the_lock(): void
    {
        $lock = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/docs/site/simai-framework.lock.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $release = 'ui-' . substr((string) $lock['runtime']['ui']['commit'], 0, 12);
        [, $directory, , $receiptPath] = $this->packageOutput($this->packageFiles(), $release);
        $docara = $this->temporaryRoot . '/docara';
        mkdir($docara . '/scripts', 0755, true);
        copy(dirname(__DIR__, 2) . '/scripts/sync-framework-package-runtime.php', $docara . '/scripts/sync-framework-package-runtime.php');
        symlink(dirname(__DIR__, 2) . '/vendor', $docara . '/vendor');

        $lockPath = $this->temporaryRoot . '/simai-framework.lock.json';
        file_put_contents($lockPath, json_encode($this->v2Lock($lock), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $run = static function (array $arguments) use ($docara): array {
            exec(
                implode(' ', array_map('escapeshellarg', [PHP_BINARY, $docara . '/scripts/sync-framework-package-runtime.php', ...$arguments])) . ' 2>&1',
                $output,
                $status,
            );

            return [$status, implode("\n", $output)];
        };

        [$status, $output] = $run(['--receipt', $receiptPath, '--lock', $lockPath]);
        self::assertSame(0, $status, $output);
        $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('larena', $result['package']);
        self::assertSame('archive', $result['read_from']);
        self::assertSame(3, $result['files']);

        $written = FrameworkLock::fromJsonFile($lockPath)->packageProjections();
        self::assertSame(['larena'], array_keys($written));
        self::assertSame(
            ['smart/rule.js', 'smart/table/css/table.css', 'smart/table/js/table.js'],
            array_keys($written['larena']['files']),
        );
        $laid = $docara . '/resources/framework/runtime-packages/larena/' . self::PACKAGE_REVISION;
        self::assertSame($this->packageFiles()['smart/table/js/table.js'], file_get_contents($laid . '/smart/table/js/table.js'));
        self::assertFileDoesNotExist($laid . '/smart/rule.js.gz');

        [$status] = $run(['--receipt', $receiptPath, '--from', $directory, '--lock', $lockPath]);
        self::assertSame(0, $status);

        $other = $this->temporaryRoot . '/other.lock.json';
        // A lock of another release: the package was built against this one.
        $otherLock = $this->v2Lock($lock);
        $otherLock['runtime']['ui']['commit'] = str_repeat('c', 40);
        $otherLock = $this->v2Lock($otherLock);
        file_put_contents($other, json_encode($otherLock, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        [$status, $output] = $run(['--receipt', $receiptPath, '--lock', $other]);
        self::assertNotSame(0, $status);
        self::assertStringContainsString('FRAMEWORK_PACKAGE_FRAMEWORK_MISMATCH', $output);
    }

    /** @return array<string, string> */
    private function packageFiles(): array
    {
        $files = [
            'smart/rule.js' => "SF.RuleLoader.registerPackage(\"larena\", {rules: {}});\n",
            'smart/rule.js.gz' => "gz\n",
            'smart/table/css/table.css' => ".sf-table{}\n",
            'smart/table/js/table.js' => "/* table */\n",
            'smart/table/js/table.min.js' => "/*t*/\n",
        ];
        ksort($files, SORT_STRING);

        return $files;
    }

    /**
     * @param  array<string, string>  $files
     * @return array<string, mixed>
     */
    private function receipt(array $files, ?string $archiveSha256 = null, string $release = 'ui-9fb0297bb5c2'): array
    {
        $list = [];
        foreach ($files as $path => $bytes) {
            $list[] = ['path' => $path, 'sha256' => hash('sha256', $bytes), 'size' => strlen($bytes)];
        }
        $byPath = array_column($list, null, 'path');

        return [
            'schema_id' => 'simai.framework.product-package-receipt',
            'schema_version' => 1,
            'package' => 'larena',
            'loader' => [
                'declaration' => 'SF_BOOT_CONFIG.packages.larena.base',
                'base' => '<mount>/smart',
                'rule_file' => 'smart/rule.js',
            ],
            'package_source' => [
                'repository' => 'simai/larena-ui',
                'revision' => self::PACKAGE_REVISION,
                'path' => 'components',
                'tree' => str_repeat('3', 40),
            ],
            'framework' => [
                'source' => ['repository' => 'simai/ui-source', 'revision' => str_repeat('2', 40)],
                'release' => $release,
            ],
            'builder' => ['repository' => 'simai/ui-builder', 'revision' => str_repeat('4', 40), 'clean' => true],
            'node' => 'v20.19.5',
            'output' => [
                'runtime_path' => 'smart',
                'files_count' => count($list),
                'manifest_sha256' => hash('sha256', json_encode($list, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n"),
                'archive' => 'package-larena.tar',
                'archive_sha256' => $archiveSha256 ?? str_repeat('a', 64),
                'archive_format' => 'ustar; entries sorted; owner 0:0; mtime 0; modes 0755/0644',
            ],
            'required_files' => [
                $byPath['smart/rule.js'],
                $byPath['smart/table/js/table.js'],
                $byPath['smart/table/css/table.css'],
            ],
            'files' => $list,
            'claims' => ['production_ready' => false],
        ];
    }

    /**
     * An output directory, its archive and its receipt, as `sf-build package`
     * lays them.
     *
     * @param  array<string, string>  $files
     * @return array{0: FrameworkPackageReceipt, 1: string, 2: string, 3: string}
     */
    private function packageOutput(array $files, string $release = 'ui-9fb0297bb5c2'): array
    {
        $this->temporaryRoot ??= sys_get_temp_dir() . '/docara-package-receipt-' . bin2hex(random_bytes(6));
        $directory = $this->temporaryRoot . '/out-' . bin2hex(random_bytes(3));
        foreach ($files as $path => $bytes) {
            if (! is_dir(dirname($directory . '/' . $path))) {
                mkdir(dirname($directory . '/' . $path), 0755, true);
            }
            file_put_contents($directory . '/' . $path, $bytes);
        }
        $archive = $directory . '/package-larena.tar';
        file_put_contents($archive, $this->ustar($files));
        $data = $this->receipt($files, hash_file('sha256', $archive), $release);
        $receiptPath = $directory . '/package-receipt.json';
        file_put_contents($receiptPath, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return [FrameworkPackageReceipt::fromArray($data), $directory, $archive, $receiptPath];
    }

    /** @param array<string, string> $files */
    private function ustar(array $files): string
    {
        $entries = [];
        foreach ($files as $path => $bytes) {
            $parts = explode('/', $path);
            for ($depth = 1; $depth < count($parts); $depth++) {
                $entries[implode('/', array_slice($parts, 0, $depth)) . '/'] = null;
            }
            $entries[$path] = $bytes;
        }
        ksort($entries, SORT_STRING);
        $tar = '';
        foreach ($entries as $name => $bytes) {
            $header = str_pad($name, 100, "\0")
                . sprintf('%07o', $bytes === null ? 0755 : 0644) . "\0"
                . sprintf('%07o', 0) . "\0"
                . sprintf('%07o', 0) . "\0"
                . sprintf('%011o', strlen((string) $bytes)) . "\0"
                . sprintf('%011o', 0) . "\0"
                . str_repeat(' ', 8)
                . ($bytes === null ? '5' : '0')
                . str_repeat("\0", 100)
                . "ustar\0" . '00'
                . str_repeat("\0", 32 + 32 + 8 + 8 + 155 + 12);
            $checksum = array_sum(array_map('ord', str_split($header)));
            $header = substr_replace($header, sprintf('%06o', $checksum) . "\0 ", 148, 8);
            $tar .= $header;
            if ($bytes !== null) {
                $tar .= str_pad($bytes, (int) (ceil(strlen($bytes) / 512) * 512), "\0");
            }
        }

        return $tar . str_repeat("\0", 1024);
    }

    /**
     * The bundled pair relabelled as a v2 release, the way
     * FrameworkReleaseLockV2Test does.
     *
     * @param  array<string, mixed>  $lock
     * @return array<string, mixed>
     */
    private function v2Lock(array $lock): array
    {
        $runtime = $lock['runtime'];
        $uiCommit = $runtime['ui']['commit'];
        $pairId = 'ui-' . substr($uiCommit, 0, 12);
        $runtime['ui_smart'] = [
            'tag' => null,
            'commit' => $uiCommit,
            'tree' => 'distr/smart',
            'mount' => 'ui',
            'sha256' => $runtime['ui_smart']['sha256'],
            'files' => $runtime['ui_smart']['files'],
        ];
        $runtime['bundle_id'] = $pairId . substr($runtime['bundle_id'], strlen($runtime['pair_id']));
        $runtime['pair_id'] = $pairId;
        $runtime['framework_registry']['compatibility_id'] = $pairId;
        $lock['runtime'] = $runtime;
        foreach (['asset_projection', 'dynamic_asset_projection'] as $projection) {
            $lock[$projection]['source'] = ['provider' => 'simai/ui', 'revision' => $uiCommit];
        }

        return $lock;
    }

    private function assertRefused(string $code, callable $read): void
    {
        try {
            $read();
            self::fail('Accepted: ' . $code);
        } catch (FrameworkComponentException $exception) {
            self::assertSame($code, $exception->errorCode);
        }
    }
}
