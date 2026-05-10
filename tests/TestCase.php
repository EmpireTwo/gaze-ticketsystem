<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Tests;

use Empire2\GazeTicketsystem\GazeTicketsystemServiceProvider;
use Empire2\GazeTicketsystem\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Naoray\GazeLaravel\GazeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ActivitylogServiceProvider::class,
            MediaLibraryServiceProvider::class,
            GazeServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            GazeTicketsystemServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Point the package at the in-package fixture User so tests have a
        // concrete Authenticatable to rely on.
        $app['config']->set('gaze-ticketsystem.user_model', User::class);
        $app['config']->set('auth.providers.users.model', User::class);

        // Spatie media library demands a disk; pick the local public disk.
        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => 'http://localhost/storage',
            'visibility' => 'public',
        ]);
        $app['config']->set('media-library.disk_name', 'public');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->createUsersTable();
        $this->createNotificationsTable();
        $this->createActivityLogTable();
        $this->createMediaTable();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    private function createActivityLogTable(): void
    {
        if (Schema::hasTable('activity_log')) {
            return;
        }

        Schema::create('activity_log', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    private function createMediaTable(): void
    {
        if (Schema::hasTable('media')) {
            return;
        }

        Schema::create('media', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->timestamps();
        });
    }

    private function createUsersTable(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    private function createNotificationsTable(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
