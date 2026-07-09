<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docara_pages', static function (Blueprint $table): void {
            $table->id();
            $table->string('page_ref')->unique();
            $table->string('slug');
            $table->string('title');
            $table->longText('body');
            $table->string('locale', 16);
            $table->string('visibility', 32);
            $table->string('publication_status', 32);
            $table->string('version', 64);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'slug'], 'docara_pages_locale_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docara_pages');
    }
};
