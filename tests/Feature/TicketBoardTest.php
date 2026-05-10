<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Enums\Priority;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketBoard;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Livewire\Livewire;

beforeEach(function () {
    seedTicketDefaults();
});

test('ticket board renders in kanban mode by default', function () {
    Ticket::factory()->create(['title' => 'Default Board Ticket']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->assertSee('Default Board Ticket')
        ->assertSet('viewMode', 'kanban')
        ->assertSet('selectedTicketId', null);
});

test('selecting a ticket sets selectedTicketId', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->call('selectTicket', $ticket->id)
        ->assertSet('selectedTicketId', $ticket->id);
});

test('closing detail resets selectedTicketId', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->call('selectTicket', $ticket->id)
        ->assertSet('selectedTicketId', $ticket->id)
        ->call('closeDetail')
        ->assertSet('selectedTicketId', null);
});

test('navigate to next and previous ticket within status group', function () {
    $openStatus = TicketStatus::query()->where('slug', 'open')->first();

    $ticket1 = Ticket::factory()->create([
        'status_id' => $openStatus->id,
        'title' => 'First Ticket',
        'created_at' => now()->subMinutes(2),
    ]);
    $ticket2 = Ticket::factory()->create([
        'status_id' => $openStatus->id,
        'title' => 'Second Ticket',
        'created_at' => now()->subMinute(),
    ]);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->call('selectTicket', $ticket2->id)
        ->assertSet('selectedTicketId', $ticket2->id)
        ->call('navigateTicket', 'next')
        ->assertSet('selectedTicketId', $ticket1->id)
        ->call('navigateTicket', 'previous')
        ->assertSet('selectedTicketId', $ticket2->id);
});

test('filters tickets by search', function () {
    Ticket::factory()->create(['title' => 'Login Error']);
    Ticket::factory()->create(['title' => 'Payment Issue']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->set('search', 'Login')
        ->assertSee('Login Error')
        ->assertDontSee('Payment Issue');
});

test('filters tickets by priority', function () {
    Ticket::factory()->create([
        'title' => 'Urgent Task',
        'priority' => Priority::URGENT,
    ]);
    Ticket::factory()->create([
        'title' => 'Low Task',
        'priority' => Priority::LOW,
    ]);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->set('priorityFilter', 'urgent')
        ->assertSee('Urgent Task')
        ->assertDontSee('Low Task');
});

test('clear filter resets filter value', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->set('search', 'something')
        ->assertSet('search', 'something')
        ->call('clearFilter', 'search')
        ->assertSet('search', '');
});

test('sort table toggles direction', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc')
        ->call('sortTable', 'created_at')
        ->assertSet('sortDirection', 'asc')
        ->call('sortTable', 'created_at')
        ->assertSet('sortDirection', 'desc')
        ->call('sortTable', 'title')
        ->assertSet('sortBy', 'title')
        ->assertSet('sortDirection', 'asc');
});

test('ticket-created event selects the new ticket', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->assertSet('selectedTicketId', null)
        ->dispatch('ticket-created', ticketId: $ticket->id)
        ->assertSet('selectedTicketId', $ticket->id);
});

test('drag-and-drop updates ticket status', function () {
    $openStatus = TicketStatus::query()->where('slug', 'open')->first();
    $resolvedStatus = TicketStatus::query()->where('slug', 'resolved')->first();
    $ticket = Ticket::factory()->create(['status_id' => $openStatus->id]);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->call('updateTicketStatus', $ticket->id, $resolvedStatus->id);

    $ticket->refresh();
    expect($ticket->status_id)->toBe($resolvedStatus->id)
        ->and($ticket->resolved_at)->not->toBeNull();
});

test('mounts with pre-selected ticket from route', function () {
    $ticket = Ticket::factory()->create(['title' => 'Pre-Selected Ticket']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class, ['ticket' => $ticket])
        ->assertSet('selectedTicketId', $ticket->id);
});

test('view mode can be set to list', function () {
    Ticket::factory()->create(['title' => 'List View Ticket']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->set('viewMode', 'list')
        ->assertSet('viewMode', 'list')
        ->assertSee('List View Ticket');
});

test('toolbar shows filter chip when filter is active', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->set('priorityFilter', 'urgent')
        ->assertSee('Priorität: Dringend');
});

test('clearing a filter chip removes it', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->set('priorityFilter', 'urgent')
        ->assertSee('Priorität: Dringend')
        ->call('clearFilter', 'priorityFilter')
        ->assertDontSee('Priorität: Dringend');
});

test('kanban view shows status columns', function () {
    $status = TicketStatus::query()->where('slug', 'open')->first();
    Ticket::factory()->create(['status_id' => $status->id, 'title' => 'Kanban Card Ticket']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->assertSet('viewMode', 'kanban')
        ->assertSee($status->name)
        ->assertSee('Kanban Card Ticket');
});

test('list view shows ticket data in table', function () {
    $ticket = Ticket::factory()->create(['title' => 'Table View Ticket']);

    Livewire::actingAs(ticketAdmin())
        ->test(TicketBoard::class)
        ->set('viewMode', 'list')
        ->assertSee('Table View Ticket')
        ->assertSee($ticket->ticket_number);
});
