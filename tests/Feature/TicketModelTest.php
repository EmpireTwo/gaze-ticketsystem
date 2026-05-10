<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Enums\Priority;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketComment;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;

beforeEach(function () {
    seedTicketDefaults();
});

test('ticket auto-generates ticket number on creation', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->ticket_number)
        ->toStartWith('TK-'.now()->format('Ym').'-')
        ->and(strlen($ticket->ticket_number))->toBe(15);
});

test('ticket numbers are sequential within same month', function () {
    $ticket1 = Ticket::factory()->create();
    $ticket2 = Ticket::factory()->create();

    $seq1 = (int) substr($ticket1->ticket_number, -5);
    $seq2 = (int) substr($ticket2->ticket_number, -5);

    expect($seq2)->toBe($seq1 + 1);
});

test('ticket belongs to status', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->status)->toBeInstanceOf(TicketStatus::class);
});

test('ticket belongs to type', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->type)->toBeInstanceOf(TicketType::class);
});

test('ticket belongs to creator', function () {
    $admin = ticketAdmin();
    $ticket = Ticket::factory()->create(['created_by' => $admin->id]);

    expect($ticket->creator->id)->toBe($admin->id);
});

test('ticket can have assignee', function () {
    $admin = ticketAdmin();
    $ticket = Ticket::factory()->withAssignee($admin)->create();

    expect($ticket->assignee->id)->toBe($admin->id);
});

test('ticket has many comments', function () {
    $ticket = Ticket::factory()->create();
    TicketComment::factory()->count(3)->create(['ticket_id' => $ticket->id]);

    expect($ticket->comments)->toHaveCount(3);
});

test('ticket casts priority to enum', function () {
    $ticket = Ticket::factory()->priority(Priority::URGENT)->create();

    expect($ticket->priority)->toBe(Priority::URGENT);
});

test('ticket isOverdue returns true when follow_up_at is past', function () {
    $ticket = Ticket::factory()->create([
        'follow_up_at' => now()->subDay(),
    ]);

    expect($ticket->isOverdue())->toBeTrue();
});

test('ticket isOverdue returns false when closed', function () {
    $ticket = Ticket::factory()->create([
        'follow_up_at' => now()->subDay(),
        'closed_at' => now(),
    ]);

    expect($ticket->isOverdue())->toBeFalse();
});

test('ticket status has ordered scope', function () {
    $statuses = TicketStatus::query()->ordered()->pluck('position')->toArray();

    expect($statuses)->toBe(collect($statuses)->sort()->values()->toArray());
});

test('ticket status has default scope', function () {
    $default = TicketStatus::query()->default()->first();

    expect($default)->not->toBeNull()
        ->and($default->slug)->toBe('open');
});

test('ticket comment belongs to ticket and author', function () {
    $admin = ticketAdmin();
    $comment = TicketComment::factory()->create(['user_id' => $admin->id]);

    expect($comment->ticket)->toBeInstanceOf(Ticket::class)
        ->and($comment->author?->id)->toBe($admin->id);
});

test('ticket comment can be marked as ai response', function () {
    $comment = TicketComment::factory()->aiResponse()->create();

    expect($comment->is_ai_response)->toBeTrue();
});

test('ticket source url returns null when no source', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->sourceUrl())->toBeNull();
});

test('ticket source url returns null when ghostwriter is not installed', function () {
    $ticket = Ticket::factory()->create([
        'source_type' => 'support_draft',
        'source_id' => 1,
    ]);

    // Without ghostwriter installed the resolver returns null.
    expect($ticket->sourceUrl())->toBeNull();
});
