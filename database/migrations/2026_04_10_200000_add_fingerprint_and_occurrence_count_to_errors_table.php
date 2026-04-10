<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('errors', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->after('id');
            $table->unsignedInteger('occurrence_count')->default(1)->after('solution');
            $table->index('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::table('errors', function (Blueprint $table) {
            $table->dropIndex(['fingerprint']);
            $table->dropColumn(['fingerprint', 'occurrence_count']);
        });
    }
};
