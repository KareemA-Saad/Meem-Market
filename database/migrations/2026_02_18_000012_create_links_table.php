<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('url', 255)->default('');
            $table->string('name', 255)->default('');
            $table->string('image', 255)->default('');
            $table->string('target', 25)->default('');
            $table->string('description', 255)->default('');
            $table->string('visible', 20)->default('Y');
            $table->unsignedBigInteger('owner_id')->default(1);
            $table->integer('rating')->default(0);
            $table->datetime('updated_at')->nullable();
            $table->string('rel', 255)->default('');
            $table->mediumText('notes')->nullable();
            $table->string('rss', 255)->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
