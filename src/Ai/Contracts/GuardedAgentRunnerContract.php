<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Ai\Contracts;

use Empire2\GazeTicketsystem\Ai\DTO\GuardedAgentResponse;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Files\Image;

interface GuardedAgentRunnerContract
{
    /**
     * Run the agent through the Gaze boundary.
     *
     * Image attachments (`$options['attachments']`) are passed through to the
     * underlying agent untouched — Gaze cannot redact image bytes. Only the
     * text prompt is cleaned and only the LLM response text/structured fields
     * are restored. Callers are responsible for surfacing the privacy
     * implication when attachments are non-empty.
     *
     * @param  array{provider?: string, model?: string, attachments?: list<Image>}  $options
     */
    public function run(
        Agent $agent,
        string $message,
        array $options = [],
    ): GuardedAgentResponse;
}
