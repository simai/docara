<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Docara\Tests\TestCase;

final class DocumentationPagePublicationLifecycleTest extends TestCase
{
    public function testProtectedPreviewRendersEscapedDraftWithoutWriting(): void
    {
        $this->get('/admin/docara/pages/welcome/preview')
            ->assertRedirect('/admin/login');

        $session = $this->adminSession();
        $this->withSession($session)
            ->post('/admin/docara/pages', $this->input(
                title: '<Draft preview>',
                body: '<script>alert(1)</script>'.PHP_EOL.'Second line.',
            ));

        $pageCount = DB::table('docara_pages')->count();
        $auditCount = DB::table('larena_audit_events')->count();

        $this->withSession($session)
            ->get('/admin/docara/pages/welcome/preview')
            ->assertOk()
            ->assertSee('Protected preview')
            ->assertSee('Current status:')
            ->assertSee('draft')
            ->assertSee('&lt;Draft preview&gt;', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Back to edit')
            ->assertSee('data-larena-page-preview="protected"', false);

        self::assertSame($pageCount, DB::table('docara_pages')->count());
        self::assertSame($auditCount, DB::table('larena_audit_events')->count());
        $this->get('/docs/welcome')->assertNotFound();
    }

    public function testPublishAndUnpublishControlAnonymousVisibilityAndAudit(): void
    {
        $session = $this->adminSession();
        $this->withSession($session)
            ->post('/admin/docara/pages', $this->input())
            ->assertRedirect('/admin/docara/pages/welcome/edit');

        $published = $this->withSession($session)
            ->post('/admin/docara/pages/welcome/publish');
        $published->assertRedirect('/admin/docara/pages/welcome/edit')
            ->assertSessionHas('status', 'Page published.');

        $this->followRedirects($published)
            ->assertSee('Current status:')
            ->assertSee('published')
            ->assertSee('View live page')
            ->assertSee('Unpublish page');
        $this->get('/docs/welcome')->assertOk()->assertSee('Welcome');

        $publishedRow = DB::table('docara_pages')->where('slug', 'welcome')->first();
        self::assertNotNull($publishedRow);
        self::assertSame('published', $publishedRow->publication_status);
        self::assertNotNull($publishedRow->published_at);
        self::assertSame(2, DB::table('larena_audit_events')->count());

        $this->withSession($session)
            ->post('/admin/docara/pages/welcome/publish')
            ->assertSessionHas('status', 'Page published.');
        self::assertSame(2, DB::table('larena_audit_events')->count());

        $this->withSession($session)
            ->put('/admin/docara/pages/welcome', array_replace($this->input(
                title: 'Welcome while published',
                body: 'Published content remains live after edit.',
            ), ['status' => 'published']))
            ->assertRedirect('/admin/docara/pages/welcome/edit')
            ->assertSessionHas('status', 'Page updated.');
        $this->get('/docs/welcome')
            ->assertOk()
            ->assertSee('Welcome while published')
            ->assertSee('Published content remains live after edit.');
        self::assertSame('published', DB::table('docara_pages')->where('slug', 'welcome')->value('publication_status'));
        self::assertSame(3, DB::table('larena_audit_events')->count());

        $unpublished = $this->withSession($session)
            ->post('/admin/docara/pages/welcome/unpublish');
        $unpublished->assertRedirect('/admin/docara/pages/welcome/edit')
            ->assertSessionHas('status', 'Page unpublished.');

        $this->followRedirects($unpublished)
            ->assertSee('Current status:')
            ->assertSee('draft')
            ->assertSee('Publish page')
            ->assertDontSee('View live page');
        $this->get('/docs/welcome')->assertNotFound();

        $unpublishedRow = DB::table('docara_pages')->where('slug', 'welcome')->first();
        self::assertNotNull($unpublishedRow);
        self::assertSame('draft', $unpublishedRow->publication_status);
        self::assertNull($unpublishedRow->published_at);
        self::assertSame(
            ['docara_page_created', 'docara_page_published', 'docara_page_updated', 'docara_page_unpublished'],
            DB::table('larena_audit_events')->orderBy('id')->pluck('event_type')->all(),
        );

        $this->withSession($session)
            ->post('/admin/docara/pages/welcome/unpublish')
            ->assertSessionHas('status', 'Page unpublished.');
        self::assertSame(4, DB::table('larena_audit_events')->count());
    }

    /** @return array{title:string, slug:string, body:string, status:string} */
    private function input(
        string $title = 'Welcome',
        string $slug = 'welcome',
        string $body = 'Publication lifecycle body.',
    ): array {
        return ['title' => $title, 'slug' => $slug, 'body' => $body, 'status' => 'draft'];
    }

    /** @return array<string, array<string, mixed>> */
    private function adminSession(): array
    {
        $subjectRef = 'user:admin_identity:1';

        return ['larena.auth.entry_object' => [
            'type' => 'user',
            'id' => $subjectRef,
            'subject_ref' => $subjectRef,
            'channel' => 'admin',
            'assurance_level' => 'password_hash',
            'trust_level' => 'trusted',
            'constraints' => ['identity_owner' => 'larena/auth'],
            'resolved_at' => '2026-07-10T00:00:00+00:00',
        ]];
    }
}
