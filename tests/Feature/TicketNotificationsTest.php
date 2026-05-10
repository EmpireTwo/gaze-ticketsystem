<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Livewire\Admin\TicketShow;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Notifications\TicketAssignedNotification;
use Empire2\GazeTicketsystem\Notifications\TicketCommentAddedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    seedTicketDefaults();
});

test('assigning ticket sends notification to assignee', function () {
    Notification::fake();

    $admin = ticketAdmin();
    $assignee = ticketAdmin();
    $ticket = Ticket::factory()->create(['created_by' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->call('assignTo', $assignee->id);

    Notification::assertSentTo($assignee, TicketAssignedNotification::class);
});

test('assigning ticket to self does not send notification', function () {
    Notification::fake();

    $admin = ticketAdmin();
    $ticket = Ticket::factory()->create(['created_by' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->call('assignTo', $admin->id);

    Notification::assertNothingSent();
});

test('adding comment sends notification to assignee and creator', function () {
    Notification::fake();

    $creator = ticketAdmin();
    $assignee = ticketAdmin();
    $commenter = ticketAdmin();

    $ticket = Ticket::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    Livewire::actingAs($commenter)
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->set('commentBody', 'Test Kommentar')
        ->call('addComment');

    Notification::assertSentTo($creator, TicketCommentAddedNotification::class);
    Notification::assertSentTo($assignee, TicketCommentAddedNotification::class);
    Notification::assertNotSentTo($commenter, TicketCommentAddedNotification::class);
});

test('adding comment as creator does not notify self', function () {
    Notification::fake();

    $creator = ticketAdmin();
    $ticket = Ticket::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => null,
    ]);

    Livewire::actingAs($creator)
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->set('commentBody', 'Eigener Kommentar')
        ->call('addComment');

    Notification::assertNothingSent();
});
