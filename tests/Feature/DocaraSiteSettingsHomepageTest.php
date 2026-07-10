<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Access\Runtime\RoleAssignmentService;
use Larena\Access\Runtime\SystemRolePresetSynchronizer;
use Larena\Docara\Tests\TestCase;

final class DocaraSiteSettingsHomepageTest extends TestCase
{
    public function testAdministratorConfiguresLocalizedPersistentHomepageAndBranding(): void
    {
        $this->publishedPage('docara:page:home-en', 'home', 'English home', 'en');
        $this->publishedPage('docara:page:home-ru', 'glavnaya', 'Русская главная', 'ru');
        $this->publicImage('11111111-1111-4111-8111-111111111111', '21111111-1111-4111-8111-111111111111', 'Larena logo');
        $this->publicImage('12222222-2222-4222-8222-222222222222', '22222222-2222-4222-8222-222222222222', 'Larena favicon');

        $session = $this->sessionFor('user:admin_identity:1');
        $this->withSession($session)->put('/admin/docara/site-settings', [
            'name_en' => 'Larena Docs', 'name_ru' => 'Ларена Документы',
            'description_en' => 'Developer documentation', 'description_ru' => 'Документация разработчика',
            'default_locale' => 'ru',
            'logo_file_ref' => '11111111-1111-4111-8111-111111111111',
            'favicon_file_ref' => '12222222-2222-4222-8222-222222222222',
            'homepage_page_ref_en' => 'docara:page:home-en',
            'homepage_page_ref_ru' => 'docara:page:home-ru',
        ])->assertRedirect('/admin/docara/site-settings');

        self::assertSame(9, DB::table('larena_setting_values')->count());
        $this->get('/')->assertOk()->assertSee('Русская главная')->assertSee('Ларена Документы')->assertSee('Документация разработчика')->assertSee('rel="icon"', false)->assertSee('/media/', false);
        $this->get('/?locale=en')->assertOk()->assertSee('English home')->assertSee('Larena Docs')->assertSee('Developer documentation');

        $this->refreshApplication();
        $this->get('/')->assertOk()->assertSee('Русская главная')->assertSee('Ларена Документы');

        $audit = DB::table('larena_audit_events')->where('event_type', 'site_settings_updated')->first();
        self::assertNotNull($audit);
        $payload = json_decode((string) $audit->payload, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(9, $payload['changed_count']);
        self::assertArrayNotHasKey('values', $payload);
        self::assertStringNotContainsString('Developer documentation', (string) $audit->payload);

        DB::table('docara_pages')->where('page_ref', 'docara:page:home-ru')->update(['publication_status' => 'draft', 'published_at' => null]);
        $this->get('/')->assertNotFound();
    }

    public function testReaderAndEditorAreReadOnlyAndDeniedWritesAreAudited(): void
    {
        $this->publishedPage('docara:page:home-en', 'home', 'English home', 'en');
        $this->createRoleIdentity(2, 'editor@docara.test', SystemRolePresetSynchronizer::EDITOR);
        $this->createRoleIdentity(3, 'reader@docara.test', SystemRolePresetSynchronizer::READER);

        foreach ([2 => 'editor', 3 => 'reader'] as $id => $role) {
            $session = $this->sessionFor('user:admin_identity:' . $id);
            $this->withSession($session)->get('/admin/docara/site-settings')->assertOk()->assertSee('cannot change');
            $this->withSession($session)->put('/admin/docara/site-settings', [
                'name_en' => 'Forbidden ' . $role, 'name_ru' => 'Запрещено', 'default_locale' => 'en',
                'homepage_page_ref_en' => 'docara:page:home-en',
            ])->assertForbidden();
        }

        self::assertSame(0, DB::table('larena_setting_values')->count());
        self::assertSame(2, DB::table('larena_audit_events')->where('event_type', 'site_settings_update_denied')->count());
        $payloads = DB::table('larena_audit_events')->where('event_type', 'site_settings_update_denied')->pluck('payload')->implode(' ');
        self::assertStringNotContainsString('Forbidden editor', $payloads);
        self::assertStringNotContainsString('request', $payloads);
    }

    public function testInvalidHomepageAndPrivateImageAreRejected(): void
    {
        $this->publishedPage('docara:page:draft-en', 'draft', 'Draft', 'en', 'draft');
        $this->privateImage('13333333-3333-4333-8333-333333333333', '23333333-3333-4333-8333-333333333333', 'Private');

        $this->withSession($this->sessionFor('user:admin_identity:1'))->from('/admin/docara/site-settings')->put('/admin/docara/site-settings', [
            'name_en' => 'Larena', 'name_ru' => 'Ларена', 'default_locale' => 'en',
            'logo_file_ref' => '13333333-3333-4333-8333-333333333333',
            'homepage_page_ref_en' => 'docara:page:draft-en',
        ])->assertRedirect('/admin/docara/site-settings')->assertSessionHasErrors(['logo_file_ref', 'homepage_page_ref_en']);

        self::assertSame(0, DB::table('larena_setting_values')->count());
        $this->get('/')->assertNotFound();
    }

    private function publishedPage(string $ref, string $slug, string $title, string $locale, string $status = 'published'): void
    {
        DB::table('docara_pages')->insert(['page_ref' => $ref, 'slug' => $slug, 'title' => $title, 'body' => $title . ' body', 'assets' => null, 'locale' => $locale, 'visibility' => 'public', 'publication_status' => $status, 'version' => 1, 'published_at' => $status === 'published' ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function publicImage(string $logicalRef, string $publicId, string $name): void
    {
        $this->image($logicalRef, $publicId, $name, 'public');
    }

    private function privateImage(string $logicalRef, string $publicId, string $name): void
    {
        $this->image($logicalRef, $publicId, $name, 'private');
    }

    private function image(string $logicalRef, string $publicId, string $name, string $visibility): void
    {
        DB::table('larena_files')->insert(['logical_ref' => $logicalRef, 'public_id' => $publicId, 'display_name' => $name, 'original_name' => 'image.png', 'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 68, 'sha256' => str_repeat('a', 64), 'storage_disk' => 'local', 'storage_key' => 'larena/media/blobs/aa/' . str_repeat('a', 64), 'visibility' => $visibility, 'alt_text' => null, 'created_by' => 1, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()]);
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
