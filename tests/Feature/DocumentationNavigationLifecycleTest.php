<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Access\Runtime\RoleAssignmentService;
use Larena\Access\Runtime\SystemRolePresetSynchronizer;
use Larena\Docara\Tests\TestCase;

final class DocumentationNavigationLifecycleTest extends TestCase
{
    public function testMenuAdminUsesSf5ComponentsAndExternalConfirmationAsset(): void
    {
        $this->publishedPage('docara:page:home', 'home', 'Home', 'en');
        $admin = $this->sessionFor('user:admin_identity:1');

        $this->withSession($admin)->get('/admin/docara/menus/create')
            ->assertOk()->assertSee('<sf-input', false)->assertSee('<sf-dropdown', false)
            ->assertSee('<sf-checkbox', false)->assertSee('<sf-button', false)
            ->assertDontSee('<select', false);

        $this->withSession($admin)->post('/admin/docara/menus', [
            'name' => 'SF5 menu', 'code' => 'sf5', 'locale' => 'en', 'is_active' => '1',
        ])->assertRedirect('/admin/docara/menus/1/edit');

        $this->withSession($admin)->get('/admin/docara/menus')
            ->assertOk()->assertSee('<sf-table', false)->assertDontSee('<table', false);

        $this->withSession($admin)->get('/admin/docara/menus/1/edit')
            ->assertOk()->assertSee('<sf-input', false)->assertSee('<sf-dropdown', false)
            ->assertSee('<sf-checkbox', false)->assertSee('<sf-button', false)
            ->assertSee('docara.admin.menus.js', false)->assertDontSee('onsubmit=', false);

        $this->get('/larena/assets/docara/docara.admin.menus.js')
            ->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->assertSee("document.addEventListener('submit'", false);
    }

    public function testAdministratorBuildsPersistentFilteredNestedPublicNavigation(): void
    {
        $this->publishedPage('docara:page:home', 'home', 'Home', 'en');
        $this->publishedPage('docara:page:about', 'about', 'About', 'en');
        $this->publishedPage('docara:page:draft', 'draft', 'Draft', 'en', 'draft');

        $admin = $this->sessionFor('user:admin_identity:1');
        $this->withSession($admin)->post('/admin/docara/menus', [
            'name' => 'Main navigation', 'code' => 'main', 'locale' => 'en', 'is_active' => '1',
        ])->assertRedirect('/admin/docara/menus/1/edit');

        $this->withSession($admin)->post('/admin/docara/menus/1/items', [
            'page_ref' => 'docara:page:home', 'label' => 'Start', 'sort_order' => 20, 'is_active' => '1',
        ])->assertRedirect('/admin/docara/menus/1/edit');
        $this->withSession($admin)->post('/admin/docara/menus/1/items', [
            'page_ref' => 'docara:page:about', 'label' => 'Company', 'parent_id' => 1, 'sort_order' => 10, 'is_active' => '1',
        ])->assertRedirect('/admin/docara/menus/1/edit');

        $this->get('/docs/home')->assertOk()->assertSee('Site navigation')->assertSee('Start')->assertSee('Company')->assertSee('/docs/about?locale=en', false);
        $this->withSession($admin)->get('/admin/docara/menus/1/edit')->assertOk()->assertSee('Main navigation')->assertSee('Company');
        $this->withSession($admin)->from('/admin/docara/menus/1/edit')->put('/admin/docara/menus/1/items/1', [
            'label' => 'Start', 'parent_id' => 2, 'sort_order' => 20, 'is_active' => '1',
        ])->assertRedirect('/admin/docara/menus/1/edit')->assertSessionHasErrors('items.1');
        self::assertNull(DB::table('docara_menu_items')->where('id', 1)->value('parent_id'));

        $this->refreshApplication();
        $this->get('/docs/home')->assertOk()->assertSee('Start')->assertSee('Company');

        DB::table('docara_pages')->where('page_ref', 'docara:page:about')->update(['publication_status' => 'draft', 'published_at' => null]);
        $this->get('/docs/home')->assertOk()->assertSee('Start')->assertDontSee('Company');

        self::assertSame(3, DB::table('larena_audit_events')->where('category', 'navigation')->count());
        $payload = (string) DB::table('larena_audit_events')->where('category', 'navigation')->orderByDesc('id')->value('payload');
        self::assertStringNotContainsString('password', $payload);
        self::assertStringNotContainsString('body', $payload);
        self::assertStringNotContainsString('session', $payload);
    }

    public function testRoleMatrixAndDeniedDeleteAreEnforcedAndAudited(): void
    {
        $this->createRoleIdentity(2, 'editor@docara.test', SystemRolePresetSynchronizer::EDITOR);
        $this->createRoleIdentity(3, 'reader@docara.test', SystemRolePresetSynchronizer::READER);
        $this->publishedPage('docara:page:home', 'home', 'Home', 'en');

        $admin = $this->sessionFor('user:admin_identity:1');
        $editor = $this->sessionFor('user:admin_identity:2');
        $reader = $this->sessionFor('user:admin_identity:3');
        $this->withSession($admin)->post('/admin/docara/menus', ['name' => 'Main', 'code' => 'main', 'locale' => 'en', 'is_active' => '1'])->assertRedirect();

        $this->withSession($editor)->get('/admin/docara/menus')->assertOk()->assertSee('Create menu');
        $this->withSession($editor)->put('/admin/docara/menus/1', ['name' => 'Editor changed', 'is_active' => '1'])->assertRedirect();
        $this->withSession($editor)->delete('/admin/docara/menus/1')->assertForbidden();
        self::assertSame('Editor changed', DB::table('docara_menus')->where('id', 1)->value('name'));

        $denied = DB::table('larena_audit_events')->where('event_type', 'access.operation.denied')->orderByDesc('id')->first();
        self::assertNotNull($denied);
        $payload = json_decode((string) $denied->payload, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('docara.navigation.delete', $payload['operation']);
        self::assertSame('editor', $payload['role']);
        self::assertArrayNotHasKey('request', $payload);

        $this->withSession($reader)->get('/admin/docara/menus/1/edit')->assertOk()->assertDontSee('Delete menu');
        $this->withSession($reader)->put('/admin/docara/menus/1', ['name' => 'Reader changed'])->assertForbidden();
        self::assertSame('Editor changed', DB::table('docara_menus')->where('id', 1)->value('name'));

        $this->withSession($admin)->delete('/admin/docara/menus/1')->assertRedirect('/admin/docara/menus');
        self::assertNotNull(DB::table('docara_menus')->where('id', 1)->value('deleted_at'));
        $this->get('/docs/home')->assertOk()->assertDontSee('Editor changed');
    }

    public function testRussianMenuAndMissingOrInactiveTargetsStayOutOfProjection(): void
    {
        $admin = $this->sessionFor('user:admin_identity:1');
        $this->withSession($admin)->post('/admin/docara/pages', ['title' => 'Главная', 'slug' => 'glavnaya', 'body' => 'Русская страница.', 'status' => 'draft', 'locale' => 'ru'])->assertRedirect('/admin/docara/pages/glavnaya/edit?locale=ru');
        $this->withSession($admin)->post('/admin/docara/pages/glavnaya/publish?locale=ru')->assertRedirect('/admin/docara/pages/glavnaya/edit?locale=ru');
        $homeRef = (string) DB::table('docara_pages')->where('locale', 'ru')->where('slug', 'glavnaya')->value('page_ref');
        $this->publishedPage('docara:page:ru-about', 'o-nas', 'О нас', 'ru');
        $this->withSession($admin)->post('/admin/docara/menus', ['name' => 'Главное меню', 'code' => 'main', 'locale' => 'ru', 'is_active' => '1'])->assertRedirect();
        $this->withSession($admin)->post('/admin/docara/menus/1/items', ['page_ref' => $homeRef, 'label' => 'Главная', 'sort_order' => 10, 'is_active' => '1'])->assertRedirect();
        $this->withSession($admin)->post('/admin/docara/menus/1/items', ['page_ref' => 'docara:page:ru-about', 'label' => 'О компании', 'sort_order' => 20])->assertRedirect();

        $this->get('/docs/glavnaya?locale=ru')->assertOk()->assertSee('Навигация по сайту')->assertSee('Главная')->assertDontSee('О компании');
        DB::table('docara_pages')->where('page_ref', $homeRef)->delete();
        $this->get('/docs/glavnaya?locale=ru')->assertNotFound();
    }

    private function publishedPage(string $ref, string $slug, string $title, string $locale, string $status = 'published'): void
    {
        DB::table('docara_pages')->insert([
            'page_ref' => $ref, 'slug' => $slug, 'title' => $title, 'body' => 'Navigation test content.',
            'assets' => null, 'locale' => $locale, 'visibility' => 'public', 'publication_status' => $status,
            'version' => 1, 'published_at' => $status === 'published' ? now() : null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createRoleIdentity(int $id, string $email, string $role): void
    {
        DB::table('larena_admin_identities')->insert(['id' => $id, 'email' => $email, 'display_name' => ucfirst($role), 'password_hash' => password_hash('Test-only-password', PASSWORD_DEFAULT), 'status' => 'active', 'bootstrapped_at' => now(), 'disabled_at' => null, 'created_at' => now(), 'updated_at' => now()]);
        $this->app->make(RoleAssignmentService::class)->assign("user:admin_identity:{$id}", $role, 'user:admin_identity:1');
    }

    /** @return array<string,array<string,mixed>> */
    private function sessionFor(string $subjectRef): array
    {
        return ['larena.auth.entry_object' => ['type' => 'user', 'id' => $subjectRef, 'subject_ref' => $subjectRef, 'channel' => 'admin', 'assurance_level' => 'password_hash', 'trust_level' => 'trusted', 'constraints' => ['identity_owner' => 'larena/auth'], 'resolved_at' => '2026-07-10T00:00:00+00:00']];
    }
}
