<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->index(['offer_category_id', 'is_active', 'sort_order'], 'offers_category_active_sort_idx');
            $table->index(['is_active', 'sort_order'], 'offers_active_sort_idx');
        });

        Schema::table('offer_categories', function (Blueprint $table): void {
            $table->index(['branch_id', 'is_active', 'sort_order'], 'offer_categories_branch_active_sort_idx');
            $table->index(['is_active', 'sort_order'], 'offer_categories_active_sort_idx');
            $table->index(['is_active', 'start_date', 'end_date'], 'offer_categories_active_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropIndex('offers_category_active_sort_idx');
            $table->dropIndex('offers_active_sort_idx');
        });

        Schema::table('offer_categories', function (Blueprint $table): void {
            $table->dropIndex('offer_categories_branch_active_sort_idx');
            $table->dropIndex('offer_categories_active_sort_idx');
            $table->dropIndex('offer_categories_active_dates_idx');
        });
    }
};
