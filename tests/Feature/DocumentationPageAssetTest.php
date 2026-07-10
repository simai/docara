<?php
declare(strict_types=1);
namespace Larena\Docara\Tests\Feature;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larena\Docara\Tests\TestCase;
use Larena\Filesystem\Services\SafeFileService;
final class DocumentationPageAssetTest extends TestCase
{
    public function test_public_image_can_be_attached_audited_rendered_and_restored(): void
    {
        Storage::fake('local');
        $file=$this->app->make(SafeFileService::class)->store(UploadedFile::fake()->image('hero.png',80,50),'Hero','public','Hero alternative',null);
        $session=['larena.auth.entry_object'=>['type'=>'user','id'=>'user:admin_identity:1','subject_ref'=>'user:admin_identity:1','channel'=>'admin','assurance_level'=>'password_hash','trust_level'=>'trusted','constraints'=>['identity_owner'=>'larena/auth'],'resolved_at'=>'2026-07-10T12:00:00+00:00']];
        $this->withSession($session)->post('/admin/docara/pages',['title'=>'Media page','slug'=>'media-page','body'=>'Page with image.','status'=>'draft','hero_file_ref'=>$file->getAttribute('logical_ref')])->assertRedirect();
        $this->withSession($session)->post('/admin/docara/pages/media-page/publish')->assertRedirect();
        $this->get('/docs/media-page')->assertOk()->assertSee('Hero alternative')->assertSee('/media/'.$file->getAttribute('public_id'),false);
        self::assertSame('file_used',DB::table('larena_audit_events')->where('event_type','file_used')->value('event_type'));
        $this->refreshApplication();
        $this->get('/docs/media-page')->assertOk()->assertSee('/media/'.$file->getAttribute('public_id'),false);
    }
}
