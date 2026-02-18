<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->datetime('post_date')->nullable();
            $table->datetime('post_date_gmt')->nullable();
            $table->longText('content')->nullable();
            $table->text('title')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status', 20)->default('publish')->index();
            $table->string('comment_status', 20)->default('open');
            $table->string('ping_status', 20)->default('open');
            $table->string('password', 255)->default('');
            $table->string('slug', 200)->index();
            $table->datetime('post_modified')->nullable();
            $table->datetime('post_modified_gmt')->nullable();
            $table->longText('content_filtered')->nullable();
            $table->unsignedBigInteger('parent_id')->default(0)->index();
            $table->string('guid', 255)->default('');
            $table->integer('menu_order')->default(0);
            $table->string('type', 20)->default('post')->index();
            $table->string('mime_type', 100)->default('');
            $table->bigInteger('comment_count')->default(0);

            // Composite indexes mirroring WP for efficient queries
            $table->index(['type', 'status', 'post_date', 'id'], 'type_status_date');
            $table->index(['type', 'status', 'author_id'], 'type_status_author');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
