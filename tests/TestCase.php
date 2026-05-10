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
    protected function setUp(): void
    {
        parent::setUp();

        $this->createUsersTable();
    }

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
        $this->loadLaravelMigrations(); // sets up notifications etc.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate', ['--database' => 'testing'])->run();
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
}
