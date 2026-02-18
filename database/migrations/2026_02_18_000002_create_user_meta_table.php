<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_meta', function (Blueprint $table) {
            $table->id('umeta_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('meta_key', 255)->nullable()->index();
            $table->longText('meta_value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_meta');
    }
};
