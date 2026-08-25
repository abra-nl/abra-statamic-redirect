<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHostToRedirectsTable extends Migration
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        /** @var string $table */
        $table = config('redirects.table', 'redirects');

        Schema::table($table, function (Blueprint $table) {
            $table->string('host')->default('')->after('id');
        });

        Schema::table($table, function (Blueprint $table) {
            $table->dropUnique(['source']);
            $table->unique(['host', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @var string $table */
        $table = config('redirects.table', 'redirects');

        Schema::table($table, function (Blueprint $table) {
            $table->dropUnique(['host', 'source']);
            $table->unique('source');
        });

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('host');
        });
    }
}
