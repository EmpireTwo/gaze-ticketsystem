<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Database\Seeders;

use Empire2\GazeTicketsystem\Models\TicketStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Open', 'color' => 'blue', 'position' => 1, 'is_default' => true],
            ['name' => 'In Progress', 'color' => 'violet', 'position' => 2],
            ['name' => 'Waiting', 'color' => 'amber', 'position' => 3],
            ['name' => 'Resolved', 'color' => 'emerald', 'position' => 4, 'is_resolved' => true],
            ['name' => 'Closed', 'color' => 'zinc', 'position' => 5, 'is_closed' => true],
        ];

        foreach ($statuses as $status) {
            TicketStatus::query()->updateOrCreate(
                ['slug' => Str::slug($status['name'])],
                [
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'position' => $status['position'],
                    'is_default' => $status['is_default'] ?? false,
                    'is_resolved' => $status['is_resolved'] ?? false,
                    'is_closed' => $status['is_closed'] ?? false,
                ],
            );
        }
    }
}
