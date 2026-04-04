<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            $table->index(['is_active', 'sort_order'], 'sections_active_sort_idx');
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->index(['is_active', 'sort_order'], 'partners_active_sort_idx');
        });

        Schema::table('competitive_features', function (Blueprint $table): void {
            $table->index(['is_active', 'sort_order'], 'features_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            $table->dropIndex('sections_active_sort_idx');
        });

        Schema::table('partners', function (Blueprint $table): void {
            $table->dropIndex('partners_active_sort_idx');
        });

        Schema::table('competitive_features', function (Blueprint $table): void {
            $table->dropIndex('features_active_sort_idx');
        });
    }
};
