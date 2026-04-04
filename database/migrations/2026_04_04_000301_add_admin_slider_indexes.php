<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sliders', function (Blueprint $table): void {
            $table->index(['is_active', 'sort_order'], 'sliders_active_sort_idx');
            $table->index(['sort_order'], 'sliders_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table): void {
            $table->dropIndex('sliders_active_sort_idx');
            $table->dropIndex('sliders_sort_idx');
        });
    }
};
