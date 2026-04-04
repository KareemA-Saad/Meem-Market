<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitive_features', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('competitive_features', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
