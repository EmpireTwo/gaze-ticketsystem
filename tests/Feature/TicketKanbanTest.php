<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Livewire\Admin\TicketKanban;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Livewire\Livewire;

beforeEach(function () {
    seedTicketDefaults();
});

test('kanban board renders with status columns', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketKanban::class)
        ->assertSee('Open')
        ->assertSee('In Progress')
        ->assertSee('Waiting')
        ->assertSee('Resolved')
        ->assertSee('Closed');
});

test('kanban board shows tickets in correct columns', function () {
    $openStatus = TicketStatus::query()->where('slug', 'open')->first();
    Ticket::factory()->create(['status_id' => $openStatus->id, 'title' => 'Test Bug Report']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketKanban::class)
        ->assertSee('Test Bug Report');
});

test('kanban can update ticket status via drag', function () {
    $openStatus = TicketStatus::query()->where('slug', 'open')->first();
    $resolvedStatus = TicketStatus::query()->where('slug', 'resolved')->first();
    $ticket = Ticket::factory()->create(['status_id' => $openStatus->id]);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketKanban::class)
        ->call('updateTicketStatus', $ticket->id, $resolvedStatus->id);

    $ticket->refresh();
    expect($ticket->status_id)->toBe($resolvedStatus->id)
        ->and($ticket->resolved_at)->not->toBeNull();
});

test('kanban filters by search', function () {
    Ticket::factory()->create(['title' => 'Login Error']);
    Ticket::factory()->create(['title' => 'Payment Issue']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketKanban::class)
        ->set('search', 'Login')
        ->assertSee('Login Error')
        ->assertDontSee('Payment Issue');
});
