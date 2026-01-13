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
            $table->id();

            $table->string('event');

            $table->morphs('subject');

            $table->foreignId('causer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('causer_type')->nullable(); // user | system | job | webhook

            $table->foreignId('initiator_id')->nullable()->constrained('users');

            $table->uuid('correlation_id')->index();
            $table->uuid('transaction_id')->nullable()->index();

            $table->string('idempotency_key')->unique();

            $table->timestamps();
        });

        Schema::create('event_log_relations', function (Blueprint $table) {
            $table->id();
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
