<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Docara\Tests\TestCase;

final class DocumentationPageAuthoringValidationTest extends TestCase
{
    public function testInvalidCreateShowsSummaryAndFieldErrorsWhilePreservingInput(): void
    {
        $response = $this->withSession($this->adminSession())
            ->from('/admin/docara/pages/create')
            ->post('/admin/docara/pages', [
                'title' => 'Preserved title',
                'slug' => 'Invalid Slug',
                'body' => '',
                'status' => 'published',
            ]);

        $response->assertRedirect('/admin/docara/pages/create')
            ->assertSessionHasErrors(['slug', 'body', 'status'])
            ->assertSessionHasInput('title', 'Preserved title');

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Please correct the highlighted fields.')
            ->assertSee('The slug may contain lowercase letters, numbers and single hyphens only.')
            ->assertSee('Preserved title', false)
            ->assertSee('aria-invalid="true"', false);

        self::assertSame(0, DB::table('docara_pages')->count());
        self::assertSame(0, DB::table('larena_audit_events')->count());
    }

    public function testDuplicateSlugReturnsValidationFeedbackWithoutExtraWriteOrAudit(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->post('/admin/docara/pages', $this->input())
            ->assertRedirect('/admin/docara/pages/welcome/edit');

        $response = $this->withSession($session)
            ->from('/admin/docara/pages/create')
            ->post('/admin/docara/pages', $this->input(title: 'Second page'));

        $response->assertRedirect('/admin/docara/pages/create')
            ->assertSessionHasErrors(['slug']);

        $this->followRedirects($response)
            ->assertSee('A page with this slug already exists.');

        self::assertSame(1, DB::table('docara_pages')->count());
        self::assertSame(1, DB::table('larena_audit_events')->count());
    }

    public function testEditCannotTakeAnotherPagesSlug(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->post('/admin/docara/pages', $this->input())
            ->assertRedirect('/admin/docara/pages/welcome/edit');
        $this->withSession($session)
            ->post('/admin/docara/pages', $this->input(
                title: 'About',
                slug: 'about',
                body: 'About page body.',
            ))
            ->assertRedirect('/admin/docara/pages/about/edit');

        $response = $this->withSession($session)
            ->from('/admin/docara/pages/about/edit')
            ->put('/admin/docara/pages/about', $this->input(
                title: 'About changed',
                slug: 'welcome',
                body: 'Should not be saved.',
            ));

        $response->assertRedirect('/admin/docara/pages/about/edit')
            ->assertSessionHasErrors(['slug']);
        $this->followRedirects($response)
            ->assertSee('A page with this slug already exists.');

        self::assertSame('About', DB::table('docara_pages')->where('slug', 'about')->value('title'));
        self::assertSame(2, DB::table('docara_pages')->count());
        self::assertSame(2, DB::table('larena_audit_events')->count());
    }

    public function testCreateListEditAndUpdateExposeValuesStatusAndConfirmations(): void
    {
        $session = $this->adminSession();

        $created = $this->withSession($session)
            ->post('/admin/docara/pages', $this->input());

        $this->followRedirects($created)
            ->assertOk()
            ->assertSee('Page created.')
            ->assertSee('value="Welcome"', false)
            ->assertSee('value="welcome"', false)
            ->assertSee('Stored from the authoring form.');

        $this->withSession($session)
            ->get('/admin/docara/pages')
            ->assertOk()
            ->assertSee('Welcome')
            ->assertSee('/welcome')
            ->assertSee('draft')
            ->assertSee('/admin/docara/pages/welcome/edit', false);

        $updated = $this->withSession($session)
            ->put('/admin/docara/pages/welcome', $this->input(
                title: 'Welcome updated',
                slug: 'welcome-updated',
                body: 'Updated form body.',
            ));

        $this->followRedirects($updated)
            ->assertOk()
            ->assertSee('Page updated.')
            ->assertSee('value="Welcome updated"', false)
            ->assertSee('value="welcome-updated"', false)
            ->assertSee('Updated form body.');

        self::assertSame(1, DB::table('docara_pages')->count());
        self::assertSame(2, DB::table('larena_audit_events')->count());
    }

    /** @return array{title:string, slug:string, body:string, status:string} */
    private function input(
        string $title = 'Welcome',
        string $slug = 'welcome',
        string $body = 'Stored from the authoring form.',
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
