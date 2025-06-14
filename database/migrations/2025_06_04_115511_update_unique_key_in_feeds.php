<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->dropUnique('feeds_url_unique');
            $table->unique(['user_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('feeds_user_id_url_unique');
            $table->unique('url');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
