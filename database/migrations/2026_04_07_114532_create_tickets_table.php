<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->string('title', 255);
            $table->longText('body');
            $table->string('contact_name', 255);
            $table->string('contact_email', 255)->index();

            // Customer FK: only constrained when the host configured a customer model
            // *and* its table exists. Otherwise we keep the column nullable and
            // unconstrained so this migration succeeds even in stand-alone setups.
            $table->foreignId('customer_id')->nullable();

            // User FKs: only constrained when the resolved user model's table
            // exists. Stand-alone test runs (no app User model) get an
            // unconstrained nullable column.
            $table->foreignId('assigned_to')->nullable();
            $table->foreignId('created_by')->nullable();

            $table->foreignId('status_id')->constrained('ticket_statuses');
            $table->foreignId('type_id')->constrained('ticket_types');
            $table->string('priority', 10)->default('medium');
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->timestamp('follow_up_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });

        $this->applyOptionalForeignKeys();
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }

    private function applyOptionalForeignKeys(): void
    {
        $userTable = $this->resolveTable('gaze-ticketsystem.user_model');
        $customerTable = $this->resolveTable('gaze-ticketsystem.customer_model');

        if ($userTable !== null && Schema::hasTable($userTable)) {
            try {
                Schema::table('tickets', function (Blueprint $table) use ($userTable): void {
                    $table->foreign('assigned_to')->references('id')->on($userTable)->nullOnDelete();
                    $table->foreign('created_by')->references('id')->on($userTable)->nullOnDelete();
                });
            } catch (\Throwable) {
                // Some DB drivers (e.g. SQLite in older Laravel) reject FK alters
                // after table creation. Silently skip; the column stays nullable.
            }
        }

        if ($customerTable !== null && Schema::hasTable($customerTable)) {
            try {
                Schema::table('tickets', function (Blueprint $table) use ($customerTable): void {
                    $table->foreign('customer_id')->references('id')->on($customerTable)->nullOnDelete();
                });
            } catch (\Throwable) {
                // see above
            }
        }
    }

    private function resolveTable(string $configKey): ?string
    {
        $class = config($configKey);

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return null;
        }

        if (! is_subclass_of($class, Model::class)) {
            return null;
        }

        /** @var Model $instance */
        $instance = new $class;

        return $instance->getTable();
    }
};
