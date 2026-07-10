<?php

declare(strict_types=1);

namespace Larena\Docara\Tests\Feature;

use Larena\Docara\Tests\TestCase;

final class PostBetaContentSiteAssemblyTest extends TestCase
{
    public function testReaderCanSeePagesButCannotCreateOrPublishThem(): void
    {
        $reader = $this->sessionFor('user:reader');

        $this->withSession($reader)
            ->get('/admin/docara/pages')
            ->assertOk()
            ->assertSee('Pages')
            ->assertDontSee('Create page');

        $this->withSession($reader)
            ->get('/admin/docara/pages/create')
            ->assertForbidden();

        $this->withSession($reader)
            ->post('/admin/docara/pages/example/publish')
            ->assertForbidden();
    }

    public function testForbiddenActorCannotReadPageManagement(): void
    {
        $this->withSession($this->sessionFor('user:forbidden'))
            ->get('/admin/docara/pages')
            ->assertForbidden();
    }

    public function testRussianInterfacePersistsInSession(): void
    {
        $session = $this->sessionFor('user:admin_identity:1');

        $this->withSession($session)
            ->get('/admin/docara/pages?locale=ru')
            ->assertOk()
            ->assertSee('<html lang="ru">', false)
            ->assertSee('Страницы')
            ->assertSessionHas('larena.admin.locale', 'ru');

        $this->get('/admin/docara/pages')
            ->assertOk()
            ->assertSee('<html lang="ru">', false)
            ->assertSee('Создать страницу');
    }

    public function testPublicPageUsesPackageAssetThroughCoreActivationContract(): void
    {
        $this->withSession($this->sessionFor('user:admin_identity:1'))
            ->post('/admin/docara/pages', [
                'title' => 'Public layout',
                'slug' => 'public-layout',
                'body' => "Readable body\nwith a second line.",
                'status' => 'draft',
            ])
            ->assertRedirect('/admin/docara/pages/public-layout/edit');

        $this->withSession($this->sessionFor('user:admin_identity:1'))
            ->post('/admin/docara/pages/public-layout/publish')
            ->assertRedirect('/admin/docara/pages/public-layout/edit');

        $this->get('/docs/public-layout')
            ->assertOk()
            ->assertSee('<main id="content"', false)
            ->assertSee('data-larena-asset-key="docara.public.page.css"', false)
            ->assertSee('data-larena-asset-owner="larena/core:core.assets"', false)
            ->assertSee('Readable body')
            ->assertDontSee('<script', false);

        $this->get('/larena/assets/docara/docara.public.page.css')
            ->assertOk()
            ->assertHeader('X-Larena-Owner', 'larena/docara')
            ->assertHeader('X-Larena-Asset-Activation-Owner', 'larena/core:core.assets')
            ->assertHeader('X-Larena-Root-Copy', 'false');

        $this->get('/larena/assets/docara/unknown.css')->assertNotFound();
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
