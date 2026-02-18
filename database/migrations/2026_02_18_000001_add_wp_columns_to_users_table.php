<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login', 60)->unique()->nullable()->after('name');
            $table->string('nicename', 50)->nullable()->after('login');
            $table->string('url', 100)->default('')->after('email');
            $table->datetime('registered_at')->nullable()->after('url');
            $table->string('activation_key', 255)->default('')->after('registered_at');
            $table->tinyInteger('status')->default(0)->after('activation_key');
            $table->string('display_name', 250)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn([
                'login',
                'nicename',
                'url',
                'registered_at',
                'activation_key',
                'status',
                'display_name',
            ]);
        });
    }
};
