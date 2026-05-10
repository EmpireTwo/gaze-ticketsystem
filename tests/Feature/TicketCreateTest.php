<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Livewire\Admin\TicketCreate;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketType;
use Livewire\Livewire;

beforeEach(function () {
    seedTicketDefaults();
});

test('create form renders', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketCreate::class)
        ->assertSee('Neues Ticket');
});

test('can create a ticket', function () {
    $type = TicketType::query()->first();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketCreate::class)
        ->set('title', 'Neues Test-Ticket')
        ->set('body', 'Beschreibung des Tickets')
        ->set('contactName', 'Max Mustermann')
        ->set('contactEmail', 'max@example.com')
        ->set('typeId', (string) $type->id)
        ->set('priority', 'high')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Ticket::query()->where('title', 'Neues Test-Ticket')->exists())->toBeTrue();
});

test('validation requires title and contact', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketCreate::class)
        ->set('title', '')
        ->set('contactName', '')
        ->set('contactEmail', '')
        ->call('save')
        ->assertHasErrors(['title', 'contactName', 'contactEmail']);
});

test('validation requires valid email', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketCreate::class)
        ->set('contactEmail', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['contactEmail']);
});
