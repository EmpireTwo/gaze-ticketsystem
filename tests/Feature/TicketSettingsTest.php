<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Livewire\Admin\TicketSettings;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;
use Livewire\Livewire;

beforeEach(function () {
    seedTicketDefaults();
});

test('settings page renders', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketSettings::class)
        ->assertSee('Ticket-Einstellungen')
        ->assertSee('Open')
        ->assertSee('General');
});

test('can add new status', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketSettings::class)
        ->set('newStatusName', 'Blocked')
        ->set('newStatusColor', 'red')
        ->call('addStatus')
        ->assertHasNoErrors();

    expect(TicketStatus::query()->where('slug', 'blocked')->exists())->toBeTrue();
});

test('can delete status without tickets', function () {
    $status = TicketStatus::factory()->create();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketSettings::class)
        ->call('deleteStatus', $status->id);

    expect(TicketStatus::query()->find($status->id))->toBeNull();
});

test('cannot delete default status', function () {
    $defaultStatus = TicketStatus::query()->where('is_default', true)->first();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketSettings::class)
        ->call('deleteStatus', $defaultStatus->id);

    expect(TicketStatus::query()->find($defaultStatus->id))->not->toBeNull();
});

test('can add new type', function () {
    Livewire::actingAs(ticketAdmin())
        ->test(TicketSettings::class)
        ->set('newTypeName', 'Bug Report')
        ->set('newTypeColor', 'red')
        ->set('newTypeIcon', 'bug-ant')
        ->call('addType')
        ->assertHasNoErrors();

    expect(TicketType::query()->where('slug', 'bug-report')->exists())->toBeTrue();
});

test('can edit type', function () {
    $type = TicketType::query()->first();

    Livewire::actingAs(ticketAdmin())
        ->test(TicketSettings::class)
        ->call('editType', $type->id)
        ->set('editingTypeName', 'Updated Name')
        ->set('editingTypeColor', 'green')
        ->set('editingTypeIcon', 'star')
        ->call('saveType')
        ->assertHasNoErrors();

    $type->refresh();
    expect($type->name)->toBe('Updated Name');
});
