<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->tinyText('author_name');
            $table->string('author_email', 100)->default('');
            $table->string('author_url', 200)->default('');
            $table->string('author_ip', 100)->default('');
            $table->datetime('comment_date')->nullable();
            $table->datetime('comment_date_gmt')->nullable();
            $table->text('content');
            $table->integer('karma')->default(0);
            $table->string('approved', 20)->default('1');
            $table->string('agent', 255)->default('');
            $table->string('type', 20)->default('comment');
            $table->unsignedBigInteger('parent_id')->default(0)->index();
            $table->unsignedBigInteger('user_id')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
