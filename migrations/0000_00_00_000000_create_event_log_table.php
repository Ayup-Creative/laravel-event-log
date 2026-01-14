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
        Schema::create('event_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('event');

            $table->morphs('subject');

            $table->string('causer_id')
                ->nullable()
                ->index();

            $table->string('causer_type')->nullable(); // user | system | job | webhook

            $table->string('initiator_id')->nullable()->index();

            $table->uuid('correlation_id')->index();
            $table->uuid('transaction_id')->nullable()->index();

            $table->string('idempotency_key')->unique();

            $table->timestamps(precision: 3);
        });

        Schema::create('event_log_relations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_log_id')->constrained()->cascadeOnDelete();
            $table->morphs('related'); // organisation, mandate, user, etc
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_logs');
        Schema::dropIfExists('event_log_relations');
    }
};
