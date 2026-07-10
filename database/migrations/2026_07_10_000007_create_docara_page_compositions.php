<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docara_page_compositions', static function (Blueprint $table): void {
            $table->id();
            $table->string('page_ref', 191)->unique();
            $table->json('draft_blocks');
            $table->json('published_blocks')->nullable();
            $table->unsignedInteger('draft_version')->default(1);
            $table->unsignedInteger('published_version')->nullable();
            $table->string('draft_actor', 191);
            $table->string('published_actor', 191)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('docara_page_composition_versions', static function (Blueprint $table): void {
            $table->id();
            $table->string('page_ref', 191);
            $table->unsignedInteger('version');
            $table->json('blocks');
            $table->string('actor', 191);
            $table->timestamp('published_at');
            $table->timestamps();
            $table->unique(['page_ref', 'version'], 'docara_page_composition_version_unique');
            $table->index('page_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docara_page_composition_versions');
        Schema::dropIfExists('docara_page_compositions');
    }
};
