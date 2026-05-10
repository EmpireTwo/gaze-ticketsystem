<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem;

use Empire2\GazeTicketsystem\Console\Commands\CheckTicketFollowUpsCommand;
use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketBoard;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketCreate;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketCreateModal;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketKanban;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketSettings;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketShow;
use Empire2\GazeTicketsystem\Livewire\Toast;
use Empire2\GazeTicketsystem\Sources\ChainedTicketSourceResolver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class GazeTicketsystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gaze-ticketsystem.php', 'gaze-ticketsystem');

        $this->app->singleton(TicketSourceResolver::class, function (Container $app): TicketSourceResolver {
            /** @var array<string, class-string<TicketSourceResolver>> $map */
            $map = (array) config('gaze-ticketsystem.source_resolvers', []);

            return new ChainedTicketSourceResolver($app, $map);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'gaze-ticketsystem');

        if ((bool) config('gaze-ticketsystem.enabled', true)) {
            $this->registerRoutes();
            $this->registerLivewireComponents();
        }

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
            $this->commands([
                CheckTicketFollowUpsCommand::class,
            ]);

            $this->registerSchedule();
        }
    }

    private function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('gaze-ticketsystem.route_prefix', 'tickets'),
            'middleware' => config('gaze-ticketsystem.middleware', ['web', 'auth']),
            'as' => 'gaze-ticketsystem.',
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    private function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component('gaze-ticketsystem.ticket-board', TicketBoard::class);
        Livewire::component('gaze-ticketsystem.ticket-kanban', TicketKanban::class);
        Livewire::component('gaze-ticketsystem.ticket-create', TicketCreate::class);
        Livewire::component('gaze-ticketsystem.ticket-create-modal', TicketCreateModal::class);
        Livewire::component('gaze-ticketsystem.ticket-show', TicketShow::class);
        Livewire::component('gaze-ticketsystem.ticket-settings', TicketSettings::class);
        Livewire::component('gaze-ticketsystem.toast', Toast::class);
    }

    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/gaze-ticketsystem.php' => config_path('gaze-ticketsystem.php'),
        ], 'gaze-ticketsystem-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'gaze-ticketsystem-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/gaze-ticketsystem'),
        ], 'gaze-ticketsystem-views');

        $this->publishes([
            __DIR__.'/../database/seeders' => database_path('seeders'),
        ], 'gaze-ticketsystem-seeders');
    }

    private function registerSchedule(): void
    {
        if (! (bool) config('gaze-ticketsystem.schedule_follow_ups', true)) {
            return;
        }

        if (! $this->app->bound(Schedule::class)) {
            return;
        }

        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command(CheckTicketFollowUpsCommand::class)
                ->hourly()
                ->environments(['production']);
        });
    }
}
