<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Sources\GhostwriterSourceResolver;

return [
    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Set to false to disable route registration and the Livewire component
    | aliases. Migrations and console commands stay registered so the host
    | app can still rely on them in seed/teardown flows.
    |
    */
    'enabled' => env('GAZE_TICKETSYSTEM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The Authenticatable model used for assignees, creators and comment
    | authors. The host can swap this for any model implementing
    | \Illuminate\Contracts\Auth\Authenticatable.
    |
    */
    'user_model' => env('GAZE_TICKETSYSTEM_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Customer model (optional)
    |--------------------------------------------------------------------------
    |
    | Set this to your billing customer model class to enable the customer
    | search box on ticket creation. When null, the customer relation on the
    | Ticket model returns null and the customer search UI is hidden.
    |
    */
    'customer_model' => env('GAZE_TICKETSYSTEM_CUSTOMER_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Admin resolver
    |--------------------------------------------------------------------------
    |
    | Optional callable returning a Collection<Authenticatable>. Used to fill
    | the "assign to" dropdowns and to discover follow-up notification
    | recipients. When null the package falls back to the user_model and
    | calls a `admins()` scope if it exists, otherwise returns all users.
    |
    | Contract: function (): \Illuminate\Support\Collection
    |
    */
    'admin_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Layout used by Livewire admin components
    |--------------------------------------------------------------------------
    */
    'layout' => env('GAZE_TICKETSYSTEM_LAYOUT', 'components.layouts.app'),

    /*
    |--------------------------------------------------------------------------
    | Route registration
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web', 'auth'],
    'route_prefix' => 'tickets',

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    |
    | When true the package will register its hourly follow-up check command
    | with Laravel's scheduler (production environment only). Set to false if
    | you prefer to wire the command up yourself in routes/console.php.
    |
    */
    'schedule_follow_ups' => env('GAZE_TICKETSYSTEM_SCHEDULE_FOLLOW_UPS', true),

    /*
    |--------------------------------------------------------------------------
    | Source resolvers
    |--------------------------------------------------------------------------
    |
    | Map of `source_type => resolver class-string`. Each resolver implements
    | \Empire2\GazeTicketsystem\Contracts\TicketSourceResolver and is used to
    | (a) prefill ticket creation forms from external sources and
    | (b) build the "view source" link shown on a ticket.
    |
    | The bundled GhostwriterSourceResolver only activates if
    | `empire2/gaze-ghostwriter` is installed; otherwise it returns null.
    |
    */
    'source_resolvers' => [
        'support_draft' => GhostwriterSourceResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI integration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => env('GAZE_TICKETSYSTEM_AI_ENABLED', true),
        'analysis_model' => env('GAZE_TICKETSYSTEM_AI_MODEL', 'gpt-4o-mini'),
        'gaze_enabled' => env('GAZE_TICKETSYSTEM_GAZE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'follow_up_due_after_hours' => (int) env('GAZE_TICKETSYSTEM_FOLLOW_UP_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk' => env('GAZE_TICKETSYSTEM_MEDIA_DISK', 'public'),
    ],
];
