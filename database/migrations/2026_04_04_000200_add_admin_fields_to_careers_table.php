<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('careers', function (Blueprint $table): void {
            $table->integer('sort_order')->default(0)->after('is_active');
            $table->index(['is_active', 'sort_order'], 'careers_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('careers', function (Blueprint $table): void {
            $table->dropIndex('careers_active_sort_idx');
            $table->dropColumn('sort_order');
        });
    }
};
