<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_taxonomy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('taxonomy', 32)->index();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('parent')->default(0);
            $table->bigInteger('count')->default(0);

            $table->unique(['term_id', 'taxonomy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_taxonomy');
    }
};
