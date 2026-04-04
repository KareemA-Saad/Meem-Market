<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $table): void {
            $table->string('media_type', 20)->default('image')->after('image');
            $table->index(['media_type', 'is_active', 'sort_order'], 'sliders_media_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table): void {
            $table->dropIndex('sliders_media_active_sort_idx');
            $table->dropColumn('media_type');
        });
    }
};
