<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 200)->index();
            $table->bigInteger('term_group')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
