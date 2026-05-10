<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Database\Seeders;

use Empire2\GazeTicketsystem\Models\TicketType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'General', 'color' => 'zinc', 'icon' => 'document-text'],
            ['name' => 'Support', 'color' => 'blue', 'icon' => 'chat-bubble-left-right'],
            ['name' => 'Incident Report', 'color' => 'red', 'icon' => 'exclamation-triangle'],
            ['name' => 'Feature Request', 'color' => 'violet', 'icon' => 'light-bulb'],
            ['name' => 'Internal Task', 'color' => 'amber', 'icon' => 'clipboard-document-check'],
        ];

        foreach ($types as $type) {
            TicketType::query()->updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => $type['name'],
                    'color' => $type['color'],
                    'icon' => $type['icon'],
                ],
            );
        }
    }
}
