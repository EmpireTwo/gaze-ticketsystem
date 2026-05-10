<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Database\Factories;

use Empire2\GazeTicketsystem\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TicketStatus> */
class TicketStatusFactory extends Factory
{
    protected $model = TicketStatus::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'color' => fake()->randomElement(['blue', 'violet', 'amber', 'emerald', 'zinc', 'red']),
            'position' => fake()->numberBetween(1, 100),
            'is_default' => false,
            'is_resolved' => false,
            'is_closed' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['is_resolved' => true]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['is_closed' => true]);
    }
}
