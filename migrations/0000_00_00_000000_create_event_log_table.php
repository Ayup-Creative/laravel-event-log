<?php

use AyupCreative\EventLog\Facades\EventLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Event log table creation has been migrated to a single migration file.
        // This migration is here to ensure rollbacks on previous installations do not fail.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Event log table creation has been migrated to a single migration file.
        // This migration is here to ensure rollbacks on previous installations do not fail.
        Schema::dropIfExists('event_logs');
        Schema::dropIfExists('event_log_relations');
        Schema::dropIfExists('event_log_metadata');
    }
};
