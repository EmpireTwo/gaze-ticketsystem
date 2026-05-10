<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Livewire\Admin\TicketShow;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketComment;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    seedTicketDefaults();
});

test('ticket detail page renders', function () {
    $ticket = Ticket::factory()->create(['title' => 'Detail-Test Ticket']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->assertSee('Detail-Test Ticket');
});

test('can add comment to ticket', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->set('commentBody', 'Ein Testkommentar')
        ->call('addComment')
        ->assertHasNoErrors();

    expect($ticket->comments()->count())->toBe(1)
        ->and($ticket->comments()->first()->body)->toBe('Ein Testkommentar');
});

test('can delete comment', function () {
    $ticket = Ticket::factory()->create();
    $admin = ticketAdmin();
    $comment = TicketComment::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->call('deleteComment', $comment->id);

    expect(TicketComment::query()->find($comment->id))->toBeNull();
});

test('can update ticket status', function () {
    $ticket = Ticket::factory()->create();
    $resolvedStatus = TicketStatus::query()->where('slug', 'resolved')->first();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->call('updateStatus', $resolvedStatus->id);

    $ticket->refresh();
    expect($ticket->status_id)->toBe($resolvedStatus->id)
        ->and($ticket->resolved_at)->not->toBeNull();
});

test('can assign ticket to user', function () {
    $ticket = Ticket::factory()->create();
    $admin = ticketAdmin();

    Livewire::actingAs($admin)
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->call('assignTo', $admin->id);

    $ticket->refresh();
    expect($ticket->assigned_to)->toBe($admin->id);
});

test('can set follow-up date', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->set('followUpDate', '2026-04-15')
        ->call('setFollowUp');

    $ticket->refresh();
    expect($ticket->follow_up_at->format('Y-m-d'))->toBe('2026-04-15');
});

test('can update priority', function () {
    $ticket = Ticket::factory()->create(['priority' => 'low']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketShow::class, ['ticket' => $ticket])
        ->call('updatePriority', 'urgent');

    $ticket->refresh();
    expect($ticket->priority->value)->toBe('urgent');
});
