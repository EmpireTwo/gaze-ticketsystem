<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Database\Factories;

use Empire2\GazeTicketsystem\Enums\Priority;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'created_by' => null,
            'status_id' => fn () => TicketStatus::query()->default()->first()?->id
                ?? TicketStatus::factory()->default()->create()->id,
            'type_id' => fn () => TicketType::query()->first()?->id
                ?? TicketType::factory()->create()->id,
            'priority' => fake()->randomElement(Priority::cases()),
        ];
    }

    public function withAssignee(?Authenticatable $user = null): static
    {
        return $this->state(fn () => [
            'assigned_to' => $user?->getAuthIdentifier(),
        ]);
    }

    public function withFollowUp(?Carbon $date = null): static
    {
        return $this->state(fn () => [
            'follow_up_at' => $date ?? now()->addDays(3),
        ]);
    }

    public function priority(Priority $priority): static
    {
        return $this->state(fn () => [
            'priority' => $priority,
        ]);
    }
}
