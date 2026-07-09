<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Docara\Contracts\DocumentationPage;
use Larena\Docara\Contracts\DocumentationPageRepository;
use Larena\Docara\Contracts\PublicationState;
use Larena\Docara\Enums\DocumentationVisibility;
use Larena\Docara\Enums\PublicationStatus;
use Larena\Docara\Tests\TestCase;

final class DocumentationPageAuthoringTest extends TestCase
{
    public function testUnauthenticatedWriteReturns401(): void
    {
        $this->postJson('/admin/docara/pages', $this->input())
            ->assertUnauthorized()
            ->assertJsonPath('reason', 'larena_admin_auth_required');

        self::assertSame(0, DB::table('docara_pages')->count());
        self::assertSame(0, DB::table('larena_audit_events')->count());
    }

    public function testForbiddenActorReceives403AndPageIsUnchanged(): void
    {
        $this->repository()->save($this->draftPage());

        $this->withSession($this->sessionPayload('user:forbidden'))
            ->putJson('/admin/docara/pages/welcome', $this->input(title: 'Forbidden change'))
            ->assertForbidden()
            ->assertJsonPath('reason', 'grant_missing');

        $page = $this->repository()->findByLocaleAndSlug('en', 'welcome');
        self::assertNotNull($page);
        self::assertSame('Welcome', $page->title);
        self::assertSame(0, DB::table('larena_audit_events')->count());
    }

    public function testPersistentAdminCreatesEditsPublishesAndAuditSurvivesReconnect(): void
    {
        $session = $this->sessionPayload('user:admin_identity:1');

        $this->withSession($session)
            ->post('/admin/docara/pages', $this->input())
            ->assertRedirect('/admin/docara/pages/welcome/edit');

        $this->withSession($session)
            ->put('/admin/docara/pages/welcome', $this->input(
                title: 'Welcome updated',
                slug: 'welcome-updated',
                body: 'Persisted body after edit.',
            ))
            ->assertRedirect('/admin/docara/pages/welcome-updated/edit');

        $this->withSession($session)
            ->post('/admin/docara/pages/welcome-updated/publish')
            ->assertRedirect('/admin/docara/pages/welcome-updated/edit');

        self::assertSame(1, DB::table('docara_pages')->count());
        self::assertSame(3, DB::table('larena_audit_events')->count());
        self::assertSame(
            ['docara_page_created', 'docara_page_updated', 'docara_page_published'],
            DB::table('larena_audit_events')->orderBy('id')->pluck('event_type')->all(),
        );

        $auditPayload = (string) DB::table('larena_audit_events')->orderByDesc('id')->value('payload');
        self::assertStringNotContainsString('Persisted body after edit.', $auditPayload);

        $this->refreshApplication();

        $page = $this->repository()->findPublishedByLocaleAndSlug('en', 'welcome-updated');
        self::assertNotNull($page);
        self::assertSame('Welcome updated', $page->title);
        self::assertSame('Persisted body after edit.', $page->body);
        self::assertSame(PublicationStatus::Published, $page->publication->status);
        self::assertSame(3, DB::table('larena_audit_events')->count());
    }

    /** @return array{title:string, slug:string, body:string, status:string} */
    private function input(
        string $title = 'Welcome',
        string $slug = 'welcome',
        string $body = 'Stored from HTTP authoring.',
    ): array {
        return ['title' => $title, 'slug' => $slug, 'body' => $body, 'status' => 'draft'];
    }

    /** @return array<string, array<string, mixed>> */
    private function sessionPayload(string $subjectRef): array
    {
        return [
            'larena.auth.entry_object' => [
                'type' => 'user',
                'id' => $subjectRef,
                'subject_ref' => $subjectRef,
                'channel' => 'admin',
                'assurance_level' => 'password_hash',
                'trust_level' => 'trusted',
                'constraints' => ['identity_owner' => 'larena/auth'],
                'resolved_at' => '2026-07-09T12:00:00+00:00',
            ],
        ];
    }

    private function draftPage(): DocumentationPage
    {
        return new DocumentationPage(
            pageRef: 'docara:page:welcome',
            slug: 'welcome',
            locale: 'en',
            visibility: DocumentationVisibility::Public,
            publication: new PublicationState(PublicationStatus::Draft, '1', false),
            title: 'Welcome',
            body: 'Original body.',
        );
    }

    private function repository(): DocumentationPageRepository
    {
        return $this->app->make(DocumentationPageRepository::class);
    }
}
