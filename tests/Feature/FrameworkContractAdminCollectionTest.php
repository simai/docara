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

        $utilityRoute = $this->app['router']->getRoutes()->getByName('larena.docara.admin.pages.framework.utilities');
        self::assertNotNull($utilityRoute);
        self::assertSame('admin/docara/pages/framework-contract/utilities', $utilityRoute->uri());
        self::assertSame(['GET', 'HEAD'], $utilityRoute->methods());
        $this->get('/admin/docara/pages/framework-contract/utilities')->assertForbidden();
        $this->withSession($this->sessionFor('user:forbidden'))->get('/admin/docara/pages/framework-contract/utilities')->assertForbidden();

        $demonstrationRoute = $this->app['router']->getRoutes()->getByName('larena.docara.admin.pages.framework.demonstration');
        self::assertNotNull($demonstrationRoute);
        self::assertSame('admin/docara/pages/framework-contract/demos/{entryId}', $demonstrationRoute->uri());
        self::assertSame(['GET', 'HEAD'], $demonstrationRoute->methods());
        $this->get('/admin/docara/pages/framework-contract/demos/utility.gap')->assertForbidden();
        $this->withSession($this->sessionFor('user:forbidden'))->get('/admin/docara/pages/framework-contract/demos/utility.gap')->assertForbidden();
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
            ->assertSee('data-framework-registry-count="14"', false)
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
            ->assertSee('14 records from the exact framework release')
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

    public function testAdministratorCanInspectReadOnlyUtilityExplorerAndItsSixRecipes(): void
    {
        $session = $this->sessionFor('user:admin_identity:1');
        $beforePages = DB::table('docara_pages')->count();
        $beforeAudit = DB::table('larena_audit_events')->count();

        $this->withSession($session)
            ->get('/admin/docara/pages/framework-contract/utilities')
            ->assertOk()
            ->assertSee('data-larena-utility-explorer', false)
            ->assertSee('data-framework-registry-count="11"', false)
            ->assertSee('Utility Explorer')
            ->assertSee('11 utility families from the pinned framework contract')
            ->assertSee('layout.vertical-stack')
            ->assertSee('layout.balanced-toolbar')
            ->assertSee('layout.two-column-grid')
            ->assertSee('layout.card-grid')
            ->assertSee('layout.centered-container')
            ->assertSee('layout.scroll-safe-region')
            ->assertSee('utility.grid-template-columns')
            ->assertSee('data-framework-utility-demo-link', false)
            ->assertSee('/admin/docara/pages/framework-contract/demos/utility.gap', false)
            ->assertDontSee('data-framework-utility-demo"', false)
            ->assertDontSee('utility.gap.vertical-stack')
            ->assertSee('It does not enumerate every allowed class value')
            ->assertSee('docara.admin.framework-catalog.css', false)
            ->assertSee('docara.admin.framework-catalog.js', false)
            ->assertDontSee('/admin/docara/pages/create', false)
            ->assertDontSee('/admin/docara/pages/contract-page/edit', false);

        $this->withSession($session)
            ->get('/admin/docara/pages/framework-contract/utilities?locale=ru')
            ->assertOk()
            ->assertSee('Обозреватель утилит')
            ->assertSee('проверенных рецептов макета')
            ->assertSee('Открыть пример')
            ->assertSee('Допустимые значения классов не перечислены');

        self::assertSame($beforePages, DB::table('docara_pages')->count());
        self::assertSame($beforeAudit, DB::table('larena_audit_events')->count());
    }

    public function testAdministratorCanOpenTheSingleSourceBackedUtilityDemonstration(): void
    {
        $session = $this->sessionFor('user:admin_identity:1');
        $beforePages = DB::table('docara_pages')->count();
        $beforeAudit = DB::table('larena_audit_events')->count();
        $path = '/admin/docara/pages/framework-contract/demos/utility.gap';

        $this->withSession($session)
            ->get($path)
            ->assertOk()
            ->assertSee('data-larena-framework-demonstration', false)
            ->assertSee('data-framework-entry-id="utility.gap"', false)
            ->assertSee('data-framework-read-only="true"', false)
            ->assertSee('utility.gap.vertical-stack')
            ->assertSee('gap-1')
            ->assertSee('gap-2')
            ->assertSee('gap-3')
            ->assertSee('ui-play:examples/layout/display/index.html')
            ->assertSee('/admin/docara/pages/framework-contract/utilities', false);

        $this->withSession($session)
            ->get($path.'?locale=ru')
            ->assertOk()
            ->assertSee('Демонстратор фреймворка')
            ->assertSee('Расстояние в вертикальном стеке')
            ->assertSee('Значение расстояния')
            ->assertSee('Контракт примера');

        $this->withSession($session)->get('/admin/docara/pages/framework-contract/demos/utility.display')->assertNotFound();
        self::assertSame($beforePages, DB::table('docara_pages')->count());
        self::assertSame($beforeAudit, DB::table('larena_audit_events')->count());
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

        $utilityPath = '/admin/docara/pages/framework-contract/utilities';
        $this->withSession($session)->post($utilityPath)->assertMethodNotAllowed();
        $this->withSession($session)->put($utilityPath)->assertMethodNotAllowed();
        $this->withSession($session)->delete($utilityPath)->assertMethodNotAllowed();
        $demonstrationPath = '/admin/docara/pages/framework-contract/demos/utility.gap';
        $this->withSession($session)->post($demonstrationPath)->assertMethodNotAllowed();
        $this->withSession($session)->put($demonstrationPath)->assertMethodNotAllowed();
        $this->withSession($session)->delete($demonstrationPath)->assertMethodNotAllowed();
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
