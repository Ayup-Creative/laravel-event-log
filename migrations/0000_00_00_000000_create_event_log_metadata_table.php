<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_log_metadata', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('event_id');

            $table->string('key');
            $table->longText('value')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_log_metadata');
    }
};
