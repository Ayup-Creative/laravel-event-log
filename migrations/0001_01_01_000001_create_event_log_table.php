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
        Schema::create('event_logs', function (Blueprint $table) {
            $this->primaryKey($table);

            $table->string('event');
            $table->morphs('subject');

            $userModel = config('event-log.user_model');

            if ((new $userModel)->getKeyType() === 'string') {
                $table->foreignUuid('causer_id')->nullable()->constrained((new $userModel)->getTable())->nullOnDelete();
            } else {
                $table->foreignId('causer_id')->nullable()->constrained((new $userModel)->getTable())->nullOnDelete();
            }

            $table->string('causer_type')->nullable(); // user | system | job | webhook
            $table->string('initiator_id')->nullable()->index();
            $table->uuid('correlation_id')->index();
            $table->uuid('transaction_id')->nullable()->index();
            $table->string('idempotency_key')->unique();
            $table->timestamps(precision: 3);
        });

        Schema::create('event_log_relations', function (Blueprint $table) {
            $this->primaryKey($table);
            $this->foreignKey($table, 'event_id', 'event_logs')->cascadeOnDelete();
            $table->morphs('related'); // organisation, mandate, user, etc
        });

        Schema::create('event_log_metadata', function (Blueprint $table) {
            $this->primaryKey($table);
            $this->foreignKey($table, 'event_id', 'event_logs')->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_logs');
        Schema::dropIfExists('event_log_relations');
        Schema::dropIfExists('event_log_metadata');
    }

    private function primaryKey(Blueprint $table): void
    {
        if ($this->usesUuidKeys()) {
            $table->uuid('id')->primary();
            return;
        }

        $table->id();
    }

    private function foreignKey(Blueprint $table, string $column, string $constrainedTable): ForeignKeyDefinition
    {
        if ($this->usesUuidKeys()) {
            return $table->foreignUuid($column)->constrained($constrainedTable);
        }

        return $table->foreignId($column)->constrained($constrainedTable);
    }

    private function usesUuidKeys(): bool
    {
        return (bool) EventLog::usesUuids();
    }
};
