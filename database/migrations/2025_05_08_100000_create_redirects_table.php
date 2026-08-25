<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRedirectsTable extends Migration
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        /** @var string $table */
        $table = config('redirects.table', 'redirects');

        Schema::create($table, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('host')->default('');
            $table->string('source');
            $table->string('destination');
            $table->integer('status_code')->default(301);
            $table->timestamps();
            $table->unique(['host', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = config('redirects.table', 'redirects');
        Schema::dropIfExists($table);
    }
}
