<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique();
            $table->longText('value')->nullable();
            $table->string('autoload', 20)->default('yes')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
