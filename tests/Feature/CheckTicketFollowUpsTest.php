<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Notifications\TicketFollowUpDueNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    seedTicketDefaults();
});

test('command sends notifications for due follow-ups', function () {
    Notification::fake();

    $admin = ticketAdmin();
    $ticket = Ticket::factory()->create([
        'assigned_to' => $admin->id,
        'follow_up_at' => now()->subHour(),
        'follow_up_notified_at' => null,
    ]);

    $this->artisan('gaze-ticketsystem:check-follow-ups')->assertSuccessful();

    Notification::assertSentTo($admin, TicketFollowUpDueNotification::class);

    $ticket->refresh();
    expect($ticket->follow_up_notified_at)->not->toBeNull();
});

test('command does not re-notify tickets already notified today', function () {
    Notification::fake();

    $admin = ticketAdmin();
    Ticket::factory()->create([
        'assigned_to' => $admin->id,
        'follow_up_at' => now()->subHour(),
        'follow_up_notified_at' => now(),
    ]);

    $this->artisan('gaze-ticketsystem:check-follow-ups')->assertSuccessful();

    Notification::assertNothingSent();
});

test('command does not notify for closed tickets', function () {
    Notification::fake();

    $admin = ticketAdmin();
    $closedStatus = TicketStatus::query()->where('slug', 'closed')->first();

    Ticket::factory()->create([
        'assigned_to' => $admin->id,
        'status_id' => $closedStatus->id,
        'follow_up_at' => now()->subHour(),
        'follow_up_notified_at' => null,
        'closed_at' => now(),
    ]);

    $this->artisan('gaze-ticketsystem:check-follow-ups')->assertSuccessful();

    Notification::assertNothingSent();
});

test('command notifies creator when no assignee', function () {
    Notification::fake();

    $creator = ticketAdmin();
    Ticket::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => null,
        'follow_up_at' => now()->subHour(),
        'follow_up_notified_at' => null,
    ]);

    $this->artisan('gaze-ticketsystem:check-follow-ups')->assertSuccessful();

    Notification::assertSentTo($creator, TicketFollowUpDueNotification::class);
});

test('command alias still works', function () {
    Notification::fake();

    $admin = ticketAdmin();
    Ticket::factory()->create([
        'assigned_to' => $admin->id,
        'follow_up_at' => now()->subHour(),
        'follow_up_notified_at' => null,
    ]);

    $this->artisan('ticket:check-follow-ups')->assertSuccessful();
});
