<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_meta', function (Blueprint $table) {
            $table->id('meta_id');
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('meta_key', 255)->nullable()->index();
            $table->longText('meta_value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_meta');
    }
};
