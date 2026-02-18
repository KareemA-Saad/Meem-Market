<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_relationships', function (Blueprint $table) {
            $table->unsignedBigInteger('object_id');
            $table->foreignId('term_taxonomy_id')->constrained('term_taxonomy')->cascadeOnDelete();
            $table->integer('term_order')->default(0);

            $table->primary(['object_id', 'term_taxonomy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_relationships');
    }
};
