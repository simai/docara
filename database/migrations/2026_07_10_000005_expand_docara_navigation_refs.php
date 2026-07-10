<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('docara_menus', static function (Blueprint $table): void {
            $table->string('menu_ref', 80)->change();
        });
        Schema::table('docara_menu_items', static function (Blueprint $table): void {
            $table->string('item_ref', 80)->change();
        });
    }

    public function down(): void
    {
        // Intentionally monotonic: prefixed UUID refs cannot safely shrink to
        // the legacy 36-character column. The preceding migration drops both
        // tables during full package rollback.
    }
};
