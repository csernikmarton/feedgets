<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('uuid')->constrained()->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('feeds', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
