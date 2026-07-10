<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('docara_menus', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('menu_ref')->unique();
            $table->string('code', 80);
            $table->string('name');
            $table->string('locale', 8);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['code', 'locale'], 'docara_menus_code_locale_unique');
        });

        Schema::create('docara_menu_items', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('item_ref')->unique();
            $table->foreignId('menu_id')->constrained('docara_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('docara_menu_items')->nullOnDelete();
            $table->string('page_ref');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'sort_order'], 'docara_menu_items_tree_index');
            $table->index('page_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docara_menu_items');
        Schema::dropIfExists('docara_menus');
    }
};
