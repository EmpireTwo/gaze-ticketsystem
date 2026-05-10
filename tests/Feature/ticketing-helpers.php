<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Database\Seeders\TicketStatusSeeder;
use Empire2\GazeTicketsystem\Database\Seeders\TicketTypeSeeder;
use Empire2\GazeTicketsystem\Tests\Fixtures\User;

/**
 * Test helpers (loaded automatically by Pest via composer's files autoload
 * convention — but since this is the package itself, the helpers are
 * required from each test file that needs them). Importable from any
 * Feature test under tests/Feature.
 */
function ticketAdmin(): User
{
    return User::factory()->create();
}

function seedTicketDefaults(): void
{
    (new TicketStatusSeeder)->run();
    (new TicketTypeSeeder)->run();
}
