<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Larena\Docara\Tests\TestCase;

final class DocumentationPageAdminShellTest extends TestCase
{
    public function testAnonymousPageListRemainsProtected(): void
    {
        $this->get('/admin/docara/pages')->assertRedirect('/admin/login');
    }

    public function testAdministratorSeesPageListInsidePackageOwnedShell(): void
    {
        $this->withSession($this->adminSession())
            ->get('/admin/docara/pages')
            ->assertOk()
            ->assertSee('data-larena-admin-shell="developer-beta"', false)
            ->assertSee('data-larena-owner-package="larena/admin"', false)
            ->assertSee('Pages')
            ->assertSee('Create page')
            ->assertSee('/vendor/larena-admin/admin-shell.css', false)
            ->assertSee('Not production ready');
    }

    public function testAdministratorSeesCreateFormInsideSameShell(): void
    {
        $this->withSession($this->adminSession())
            ->get('/admin/docara/pages/create')
            ->assertOk()
            ->assertSee('data-larena-admin-shell="developer-beta"', false)
            ->assertSee('Create page')
            ->assertSee('Back to pages')
            ->assertSee('page-title', false)
            ->assertSee('page-slug', false)
            ->assertSee('page-body', false)
            ->assertSee('Save page');
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
