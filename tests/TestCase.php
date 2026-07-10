<?php

declare(strict_types=1);

namespace Larena\Docara\Tests;

use Illuminate\Foundation\Application;
use Larena\Access\Providers\AccessServiceProvider;
use Larena\Admin\Providers\AdminServiceProvider;
use Larena\Audit\Providers\AuditServiceProvider;
use Larena\Auth\Providers\AuthServiceProvider;
use Larena\Docara\DocaraServiceProvider;
use Larena\Filesystem\Providers\FilesystemServiceProvider;
use Illuminate\Support\Facades\DB;
use Larena\Access\Runtime\RoleAssignmentService;
use Larena\Access\Runtime\SystemRolePresetSynchronizer;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'larena-docara-');
        if ($path === false) {
            self::fail('Could not allocate a temporary SQLite database.');
        }

        $this->databasePath = $path;
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'docara_testing',
            '--force' => true,
        ])->assertSuccessful();

        $now = now();
        DB::table('larena_admin_identities')->insert([
            'id' => 1, 'email' => 'admin@docara.test', 'display_name' => 'Docara Administrator',
            'password_hash' => password_hash('Docara-Admin!2026', PASSWORD_DEFAULT),
            'status' => 'active', 'bootstrapped_at' => $now, 'disabled_at' => null,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->app->make(SystemRolePresetSynchronizer::class)->sync();
        $this->app->make(RoleAssignmentService::class)->assign(
            'user:admin_identity:1', SystemRolePresetSynchronizer::ADMINISTRATOR, 'test-bootstrap',
        );
        DB::table('larena_audit_events')->delete();
    }

    protected function tearDown(): void
    {
        $databasePath = $this->databasePath;
        parent::tearDown();

        if (is_file($databasePath) && !unlink($databasePath)) {
            self::fail("Could not remove temporary SQLite database: {$databasePath}");
        }
    }

    /** @param Application $app */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('d', 32)));
        $app['config']->set('database.default', 'docara_testing');
        $app['config']->set('database.connections.docara_testing', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('larena-docara.admin.enabled', true);
        $app['config']->set('larena-docara.public.enabled', true);
        $app['config']->set('larena-filesystem.public_routes.enabled', true);
        $app['config']->set('filesystems.disks.local', ['driver'=>'local','root'=>sys_get_temp_dir().'/larena-docara-files','throw'=>false]);
        $app['config']->set('larena-auth.admin_entry.enabled', true);
        $app['config']->set('larena-auth.admin_entry.local_testing.enabled', true);
        $app['config']->set('larena-auth.admin_entry.login_mode', 'persistent');
        $app['config']->set('larena-admin.internal_routes.enabled', false);
    }

    /** @param Application $app
     *  @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AccessServiceProvider::class,
            AdminServiceProvider::class,
            AuditServiceProvider::class,
            AuthServiceProvider::class,
            FilesystemServiceProvider::class,
            DocaraServiceProvider::class,
        ];
    }
}
