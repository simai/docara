<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Access\Runtime\RoleAssignmentService;
use Larena\Access\Runtime\SystemRolePresetSynchronizer;
use Larena\Docara\Tests\TestCase;

final class DocumentationPageCompositionTest extends TestCase
{
    public function testAdministratorSavesDraftPreviewsPublishesAndPersistsFiveBlocks(): void
    {
        $this->page('docara:page:blocks-en', 'blocks-en', 'Blocks page', 'en', 'published', 'Legacy body');
        $this->image('11111111-1111-4111-8111-111111111111', '21111111-1111-4111-8111-111111111111', 'Blocks image', 'public');
        $session = $this->sessionFor('user:admin_identity:1');

        $this->withSession($session)->get('/admin/docara/pages/blocks-en/blocks')->assertOk()
            ->assertSee('Text')->assertSee('Image')->assertSee('Hero')->assertSee('Two columns')->assertSee('Call to action')
            ->assertSee('docara.text')->assertSee('docara.cta')
            ->assertSee('<sf-dropdown', false)->assertSee('<sf-checkbox', false)
            ->assertSee('<sf-button', false)->assertDontSee('<select', false)
            ->assertDontSee('<textarea', false);
        $this->withSession($session)->put('/admin/docara/pages/blocks-en/blocks', ['locale' => 'en', 'blocks' => $this->fiveBlocks()])
            ->assertRedirect('/admin/docara/pages/blocks-en/blocks');

        $this->get('/docs/blocks-en')->assertOk()->assertSee('Legacy body')->assertDontSee('Draft hero');
        $this->withSession($session)->get('/admin/docara/pages/blocks-en/preview')->assertOk()
            ->assertSeeInOrder(['Draft hero', 'Draft text', 'Blocks image alt', 'Left column', 'Open blocks']);

        $this->withSession($session)->post('/admin/docara/pages/blocks-en/publish', ['locale' => 'en'])
            ->assertRedirect('/admin/docara/pages/blocks-en/edit');
        $this->get('/docs/blocks-en')->assertOk()->assertDontSee('Legacy body')
            ->assertSeeInOrder(['Draft hero', 'Draft text', 'Blocks image alt', 'Left column', 'Open blocks'])
            ->assertSee('data-smart-view="docara.hero"', false)->assertSee('/media/', false)
            ->assertSee('docara.public.page.css?v=page-blocks-v5', false);

        self::assertSame(1, DB::table('docara_page_compositions')->count());
        self::assertSame(1, DB::table('docara_page_composition_versions')->count());
        self::assertSame(2, DB::table('larena_audit_events')->whereIn('event_type', ['docara_page_blocks_updated', 'docara_page_blocks_published'])->count());
        $audit = DB::table('larena_audit_events')->where('event_type', 'docara_page_blocks_updated')->value('payload');
        self::assertIsString($audit);
        self::assertStringNotContainsString('Draft hero', $audit);
        self::assertStringNotContainsString('Open blocks', $audit);
        self::assertStringNotContainsString('11111111-1111-4111-8111-111111111111', $audit);

        $this->refreshApplication();
        $this->get('/docs/blocks-en')->assertOk()->assertSee('Draft hero')->assertSee('Open blocks');
    }

    public function testEditorCanDraftReaderIsReadOnlyAndPublishAndWritesAreDeniedAndAudited(): void
    {
        $this->page('docara:page:roles', 'roles-blocks', 'Role blocks', 'en', 'published', 'Published fallback');
        $this->identity(2, 'editor.blocks@docara.test', SystemRolePresetSynchronizer::EDITOR);
        $this->identity(3, 'reader.blocks@docara.test', SystemRolePresetSynchronizer::READER);
        $editor = $this->sessionFor('user:admin_identity:2');
        $reader = $this->sessionFor('user:admin_identity:3');
        $draft = [['instance_id' => 'block_text_roles', 'type' => 'text', 'enabled' => '1', 'sort' => 100, 'settings' => ['heading' => 'Editor draft', 'body' => 'Not public yet', 'alignment' => 'left']]];

        $this->withSession($editor)->put('/admin/docara/pages/roles-blocks/blocks', ['locale' => 'en', 'blocks' => $draft])->assertRedirect();
        $this->withSession($editor)->post('/admin/docara/pages/roles-blocks/publish', ['locale' => 'en'])->assertForbidden();
        $this->withSession($reader)->get('/admin/docara/pages/roles-blocks/blocks')->assertOk()->assertSee('cannot change or publish')->assertDontSee('Save draft');
        $this->withSession($reader)->put('/admin/docara/pages/roles-blocks/blocks', ['locale' => 'en', 'blocks' => $draft])->assertForbidden();
        $this->get('/docs/roles-blocks')->assertOk()->assertSee('Published fallback')->assertDontSee('Editor draft');

        self::assertSame(1, DB::table('larena_audit_events')->where('event_type', 'docara_page_blocks_publish_denied')->count());
        self::assertSame(1, DB::table('larena_audit_events')->where('event_type', 'docara_page_blocks_update_denied')->count());
        $payloads = DB::table('larena_audit_events')->whereIn('event_type', ['docara_page_blocks_publish_denied', 'docara_page_blocks_update_denied'])->pluck('payload')->implode(' ');
        self::assertStringNotContainsString('Editor draft', $payloads);
        self::assertStringNotContainsString('Not public yet', $payloads);
    }

    public function testInvalidUrlAndPrivateImageFailClosedAndAssetsHaveCorrectTypes(): void
    {
        $this->page('docara:page:invalid', 'invalid-blocks', 'Invalid blocks', 'en', 'published', 'Fallback remains');
        $this->image('12222222-2222-4222-8222-222222222222', '22222222-2222-4222-8222-222222222222', 'Private image', 'private');
        $session = $this->sessionFor('user:admin_identity:1');
        $unsafe = [['instance_id' => 'block_cta_unsafe', 'type' => 'cta', 'enabled' => '1', 'sort' => 100, 'settings' => ['title' => 'Unsafe', 'body' => '', 'label' => 'Run', 'url' => 'javascript:alert(1)', 'style' => 'primary']]];
        $private = [['instance_id' => 'block_image_private', 'type' => 'image', 'enabled' => '1', 'sort' => 100, 'settings' => ['file_ref' => '12222222-2222-4222-8222-222222222222', 'alt' => 'Private', 'caption' => '']]];

        $this->withSession($session)->from('/admin/docara/pages/invalid-blocks/blocks')->put('/admin/docara/pages/invalid-blocks/blocks', ['locale' => 'en', 'blocks' => $unsafe])->assertRedirect('/admin/docara/pages/invalid-blocks/blocks')->assertSessionHasErrors('blocks');
        $this->withSession($session)->from('/admin/docara/pages/invalid-blocks/blocks')->put('/admin/docara/pages/invalid-blocks/blocks', ['locale' => 'en', 'blocks' => $private])->assertRedirect('/admin/docara/pages/invalid-blocks/blocks')->assertSessionHasErrors('blocks');
        self::assertSame(0, DB::table('docara_page_compositions')->count());
        $this->get('/docs/invalid-blocks')->assertOk()->assertSee('Fallback remains');
        $this->get('/larena/assets/docara/docara.admin.blocks.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $this->get('/larena/assets/docara/docara.admin.blocks.js')->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $css = $this->get('/larena/assets/docara/docara.public.page.css')->assertOk()->getContent();
        self::assertStringContainsString('.larena-page-block--image img{display:block;width:100%;height:auto', $css);
        self::assertStringContainsString('.larena-page-block--hero,.larena-page-block--columns{grid-template-columns:1fr}', $css);
    }

    public function testSf5NoImageSentinelNormalizesAtCompositionBoundary(): void
    {
        $this->page('docara:page:sentinel', 'sentinel-blocks', 'Sentinel blocks', 'en', 'draft', 'Fallback');
        $session = $this->sessionFor('user:admin_identity:1');
        $blocks = [[
            'instance_id' => 'block_hero_sentinel', 'type' => 'hero', 'enabled' => '1', 'sort' => 100,
            'settings' => ['eyebrow' => '', 'title' => 'No image hero', 'body' => '', 'image_file_ref' => '__none__', 'cta_label' => '', 'cta_url' => '', 'style' => 'default'],
        ]];

        $this->withSession($session)->put('/admin/docara/pages/sentinel-blocks/blocks', ['locale' => 'en', 'blocks' => $blocks])
            ->assertRedirect('/admin/docara/pages/sentinel-blocks/blocks');
        $stored = json_decode((string) DB::table('docara_page_compositions')->value('draft_blocks'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('', $stored['blocks'][0]['settings']['image_file_ref']);
    }

    /** @return list<array<string,mixed>> */
    private function fiveBlocks(): array
    {
        return [
            ['instance_id' => 'block_hero_01', 'type' => 'hero', 'enabled' => '1', 'sort' => 100, 'settings' => ['eyebrow' => 'Developer slice', 'title' => 'Draft hero', 'body' => 'Hero body', 'image_file_ref' => '11111111-1111-4111-8111-111111111111', 'cta_label' => 'Read more', 'cta_url' => '/docs/blocks-en', 'style' => 'accent']],
            ['instance_id' => 'block_text_01', 'type' => 'text', 'enabled' => '1', 'sort' => 200, 'settings' => ['heading' => 'Draft text', 'body' => 'Typed text body', 'alignment' => 'left']],
            ['instance_id' => 'block_image_01', 'type' => 'image', 'enabled' => '1', 'sort' => 300, 'settings' => ['file_ref' => '11111111-1111-4111-8111-111111111111', 'alt' => 'Blocks image alt', 'caption' => 'Image caption']],
            ['instance_id' => 'block_columns_01', 'type' => 'columns', 'enabled' => '1', 'sort' => 400, 'settings' => ['left_title' => 'Left column', 'left_body' => 'Left body', 'right_title' => 'Right column', 'right_body' => 'Right body']],
            ['instance_id' => 'block_cta_01', 'type' => 'cta', 'enabled' => '1', 'sort' => 500, 'settings' => ['title' => 'Open blocks', 'body' => 'CTA body', 'label' => 'Open', 'url' => 'https://example.test/blocks', 'style' => 'primary']],
        ];
    }

    private function page(string $ref, string $slug, string $title, string $locale, string $status, string $body): void
    {
        DB::table('docara_pages')->insert(['page_ref' => $ref, 'slug' => $slug, 'title' => $title, 'body' => $body, 'assets' => null, 'locale' => $locale, 'visibility' => 'public', 'publication_status' => $status, 'version' => 1, 'published_at' => $status === 'published' ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function image(string $logicalRef, string $publicId, string $name, string $visibility): void
    {
        DB::table('larena_files')->insert(['logical_ref' => $logicalRef, 'public_id' => $publicId, 'display_name' => $name, 'original_name' => 'image.png', 'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 68, 'sha256' => str_repeat('b', 64), 'storage_disk' => 'local', 'storage_key' => 'larena/media/blobs/bb/' . str_repeat('b', 64), 'visibility' => $visibility, 'alt_text' => null, 'created_by' => 1, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function identity(int $id, string $email, string $role): void
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
