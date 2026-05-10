<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Optional seeder that registers a "Create Ticket" smart-action with the
 * Ghostwriter package. The seeder feature-detects the
 * Empire2\GazeGhostwriter\Models\GhostwriterSmartAction model at runtime and
 * silently no-ops when ghostwriter is not installed, so it is safe to run
 * unconditionally from a host's `DatabaseSeeder`.
 */
class TicketSmartActionSeeder extends Seeder
{
    private const SMART_ACTION_MODEL = 'Empire2\\GazeGhostwriter\\Models\\GhostwriterSmartAction';

    public function run(): void
    {
        if (! class_exists(self::SMART_ACTION_MODEL)) {
            return;
        }

        $model = self::SMART_ACTION_MODEL;

        $model::query()->updateOrCreate(
            ['marker' => 'CREATE_TICKET'],
            [
                'label' => 'Ticket erstellen',
                'prompt_hint' => 'Wenn der Kunde ein Problem, eine Anfrage oder einen Vorfall beschreibt, der als Ticket nachverfolgt werden sollte',
                'route_template' => '/tickets/create?source_type=support_draft&source_id={draftId}&prefill=1',
                'is_active' => true,
            ],
        );
    }
}
