<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Database\Factories;

use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketComment> */
class TicketCommentFactory extends Factory
{
    protected $model = TicketComment::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => null,
            'body' => fake()->paragraphs(1, true),
            'is_ai_response' => false,
            'is_internal' => false,
        ];
    }

    public function aiResponse(): static
    {
        return $this->state(fn () => [
            'is_ai_response' => true,
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn () => [
            'is_internal' => true,
        ]);
    }
}
