<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Ai;

use Naoray\GazeLaravel\Gaze;

/**
 * Pure-redaction utility for paths that persist sanitized text externally —
 * no restore step.
 *
 * Boundary off (config `gaze-ticketsystem.ai.gaze_enabled` is false) → returns
 * null and the caller falls back to its own heuristics (or skips the call
 * entirely, fail-closed).
 */
final class Sanitizer
{
    public function __construct(
        private readonly Gaze $gaze,
    ) {}

    public function sanitize(string $text): ?string
    {
        if (! (bool) config('gaze-ticketsystem.ai.gaze_enabled', false)) {
            return null;
        }

        return $this->gaze->clean($text)->cleanText;
    }
}
