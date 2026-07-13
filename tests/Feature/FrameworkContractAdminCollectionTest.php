<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Larena\Docara\Tests\Support\FrameworkContractFixture;
use Larena\Docara\Tests\TestCase;
use Larena\Ui\Developer\FrameworkCatalogProjection;
use Larena\Ui\Frontend\FrameworkContractRegistry;
use Larena\Ui\Registry\FrameworkAdapterRegistry;

final class FrameworkContractAdminCollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            FrameworkContractRegistry::class,
            FrameworkAdapterRegistry::class,
            FrameworkCatalogProjection::class,
        ] as $abstract) {
            $this->app->forgetInstance($abstract);
        }

        $this->app->instance(FrameworkContractRegistry::class, FrameworkContractFixture::registry());
        $this->app->singleton(
            FrameworkAdapterRegistry::class,
            static fn (Application $app): FrameworkAdapterRegistry => new FrameworkAdapterRegistry(
                $app->make(FrameworkContractRegistry::class),
            ),
        );
        $this->app->singleton(
            FrameworkCatalogProjection::class,
            static fn (Application $app): FrameworkCatalogProjection => new FrameworkCatalogProjection(
                $app->make(FrameworkContractRegistry::class),
                $app->make(FrameworkAdapterRegistry::class),
            ),
        );
    }

    public function testFrameworkContractRouteIsProtectedAndBoundOnlyToReadMethod(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('larena.docara.admin.pages.framework.contract');
        self::assertNotNull($route);
        self::assertSame('admin/docara/pages/framework-contract/admin-collection', $route->uri());
        self::assertSame(['GET', 'HEAD'], $route->methods());

        $this->get('/admin/docara/pages/framework-contract/admin-collection')
            ->assertRedirect('/admin/login');

        $this->withSession($this->sessionFor('user:forbidden'))
            ->get('/admin/docara/pages/framework-contract/admin-collection')
            ->assertForbidden();
    }

    public function testAdministratorSeesRealPagesThroughResolvedReadOnlyFrameworkPlan(): void
    {
        $session = $this->sessionFor('user:admin_identity:1');
        $this->withSession($session)
            ->post('/admin/docara/pages', [
                'title' => 'Contract page',
                'slug' => 'contract-page',
                'body' => 'Real persisted page data.',
                'status' => 'draft',
            ])
            ->assertRedirect('/admin/docara/pages/contract-page/edit');

        $beforePage = (array) DB::table('docara_pages')->where('slug', 'contract-page')->first();
        $beforeAuditCount = DB::table('larena_audit_events')->count();
        self::assertNotSame([], $beforePage);

        $this->withSession($session)
            ->get('/admin/docara/pages/framework-contract/admin-collection')
            ->assertOk()
            ->assertSee('data-larena-framework-contract="docara.pages.admin.collection"', false)
            ->assertSee('data-framework-data-source="docara.pages"', false)
            ->assertSee('data-framework-effects-allowed="false"', false)
            ->assertSee('data-framework-read-only="true"', false)
            ->assertSee('data-framework-upstream-gap="smart.table.read-only"', false)
            ->assertSee('data-framework-fallback="larena.ui.sf-runtime-bridge"', false)
            ->assertSee('data-production-ready="false"', false)
            ->assertSee('data-larena-framework-explorer', false)
            ->assertSee('data-framework-registry-count="8"', false)
            ->assertSee('data-framework-recipe="admin.collection"', false)
            ->assertSee('<sf-table', false)
            ->assertSee('data-larena-read-only="true"', false)
            ->assertDontSee(' read-only="true"', false)
            ->assertSee('selectable="false"', false)
            ->assertSee('settings="false"', false)
            ->assertSee('actions="false"', false)
            ->assertSee('Contract page')
            ->assertSee('/contract-page')
            ->assertSee('Draft')
            ->assertSee('/admin/docara/pages/contract-page/preview', false)
            ->assertSee('component.buttons')
            ->assertSee('recipe.admin.collection')
            ->assertSee('smart.table')
            ->assertSee('utility.display')
            ->assertSee('utility.flex-direction')
            ->assertSee('utility.gap')
            ->assertSee('utility.overflow')
            ->assertSee('utility.width')
            ->assertSee('Framework Explorer')
            ->assertSee('Pinned framework contract')
            ->assertSee('8 records from the exact framework release')
            ->assertSee('docara.admin.framework-catalog.css', false)
            ->assertSee('docara.admin.framework-catalog.js', false)
            ->assertSee("Smart::render('sf-button'", false)
            ->assertSee('flex flex-col gap-1 overflow-x-auto w-full', false)
            ->assertDontSee('/admin/docara/pages/create', false)
            ->assertDontSee('/admin/docara/pages/contract-page/edit', false)
            ->assertDontSee('/admin/docara/pages/contract-page/publish', false)
            ->assertDontSee('/admin/docara/pages/contract-page/unpublish', false);

        $this->withSession($session)
            ->get('/admin/docara/pages/framework-contract/admin-collection?locale=ru')
            ->assertOk()
            ->assertSee('Обозреватель фреймворка')
            ->assertSee('Строительные блоки')
            ->assertSee('Реальные данные страниц Docara')
            ->assertSee('Зафиксированный контракт фреймворка')
            ->assertSee('Три живых примера')
            ->assertSee('Черновик');

        self::assertSame($beforePage, (array) DB::table('docara_pages')->where('slug', 'contract-page')->first());
        self::assertSame($beforeAuditCount, DB::table('larena_audit_events')->count());
    }

    public function testFrameworkContractPathCannotFallThroughToSlugMutationRoutes(): void
    {
        $session = $this->sessionFor('user:admin_identity:1');
        $beforePages = DB::table('docara_pages')->count();
        $beforeAudit = DB::table('larena_audit_events')->count();
        $path = '/admin/docara/pages/framework-contract/admin-collection';

        $this->withSession($session)->post($path)->assertMethodNotAllowed();
        $this->withSession($session)->put($path)->assertMethodNotAllowed();
        $this->withSession($session)->patch($path)->assertMethodNotAllowed();
        $this->withSession($session)->delete($path)->assertMethodNotAllowed();

        self::assertSame($beforePages, DB::table('docara_pages')->count());
        self::assertSame($beforeAudit, DB::table('larena_audit_events')->count());
    }

    /** @return array<string, array<string, mixed>> */
    private function sessionFor(string $subjectRef): array
    {
        return ['larena.auth.entry_object' => [
            'type' => 'user',
            'id' => $subjectRef,
            'subject_ref' => $subjectRef,
            'channel' => 'admin',
            'assurance_level' => 'local_testing',
            'trust_level' => 'trusted',
            'constraints' => ['identity_owner' => 'larena/auth'],
            'resolved_at' => '2026-07-13T00:00:00+00:00',
        ]];
    }
}
