<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Larena\Docara\Tests\TestCase;

final class DocumentationPageDeniedMutationAuditTest extends TestCase
{
    public function testForbiddenActorCannotUpdatePageAndDenialIsAudited(): void
    {
        $adminSession = $this->sessionFor('user:admin_identity:1');
        $this->withSession($adminSession)
            ->post('/admin/docara/pages', $this->input())
            ->assertRedirect('/admin/docara/pages/secured-page/edit');

        $before = (array) DB::table('docara_pages')->where('slug', 'secured-page')->first();
        self::assertNotSame([], $before);
        self::assertSame(1, DB::table('larena_audit_events')->count());

        $this->withSession($this->sessionFor('user:forbidden'))
            ->put('/admin/docara/pages/secured-page', $this->input(
                title: 'Forbidden title',
                body: 'FORBIDDEN BODY MUST NOT PERSIST OR ENTER AUDIT',
            ))
            ->assertForbidden();

        $after = (array) DB::table('docara_pages')->where('slug', 'secured-page')->first();
        self::assertSame($before, $after);
        self::assertSame(2, DB::table('larena_audit_events')->count());

        $event = DB::table('larena_audit_events')->orderByDesc('id')->first();
        self::assertNotNull($event);
        self::assertSame('larena/docara', $event->source_package);
        self::assertSame('content_authoring', $event->category);
        self::assertSame('docara_page_update_denied', $event->event_type);
        self::assertSame('user:forbidden', $event->actor);
        self::assertSame('docara:page_slug:secured-page', $event->subject);

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $event->payload, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([
            'operation' => 'update_denied',
            'slug' => 'secured-page',
            'status' => 'denied',
            'reason' => 'permission_denied',
        ], $payload);
        self::assertArrayNotHasKey('body', $payload);

        $this->withSession($adminSession)
            ->get('/admin/audit')
            ->assertOk()
            ->assertSee('Permission denied')
            ->assertSee('/secured-page')
            ->assertSee('user:forbidden')
            ->assertSee('denied')
            ->assertDontSee('FORBIDDEN BODY MUST NOT PERSIST OR ENTER AUDIT');
    }

    /** @return array{title:string, slug:string, body:string, status:string} */
    private function input(
        string $title = 'Secured page',
        string $body = 'Original body remains unchanged.',
    ): array {
        return [
            'title' => $title,
            'slug' => 'secured-page',
            'body' => $body,
            'status' => 'draft',
        ];
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
            'resolved_at' => '2026-07-10T00:00:00+00:00',
        ]];
    }
}
