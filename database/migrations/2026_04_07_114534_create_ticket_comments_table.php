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
        Schema::create('ticket_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->longText('body');
            $table->boolean('is_ai_response')->default(false);
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        $userTable = $this->resolveTable('gaze-ticketsystem.user_model');

        if ($userTable !== null && Schema::hasTable($userTable)) {
            try {
                Schema::table('ticket_comments', function (Blueprint $table) use ($userTable): void {
                    $table->foreign('user_id')->references('id')->on($userTable)->nullOnDelete();
                });
            } catch (\Throwable) {
                // Some DB drivers (e.g. SQLite in older Laravel) reject FK alters
                // after table creation. Column stays nullable, no FK enforced.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
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
