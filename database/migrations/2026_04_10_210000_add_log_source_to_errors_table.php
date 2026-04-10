<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('errors', function (Blueprint $table) {
            $table->string('log_source', 32)->default('server')->after('server_name');
            $table->index('log_source');
        });
    }

    public function down(): void
    {
        Schema::table('errors', function (Blueprint $table) {
            $table->dropIndex(['log_source']);
            $table->dropColumn('log_source');
        });
    }
};
