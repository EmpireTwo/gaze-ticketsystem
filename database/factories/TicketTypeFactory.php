<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Database\Factories;

use Empire2\GazeTicketsystem\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TicketType> */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'color' => fake()->randomElement(['blue', 'violet', 'amber', 'emerald', 'zinc', 'red']),
            'icon' => 'document-text',
        ];
    }
}
