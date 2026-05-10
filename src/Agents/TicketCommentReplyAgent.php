<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Agents;

use Empire2\GazeTicketsystem\Prompts\PromptResolver;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class TicketCommentReplyAgent implements Agent, Conversational
{
    use Promptable;

    private readonly PromptResolver $promptResolver;

    public function __construct(?PromptResolver $promptResolver = null)
    {
        $this->promptResolver = $promptResolver ?? new PromptResolver;
    }

    public function instructions(): Stringable|string
    {
        return $this->promptResolver->resolve('ticket-comment-reply-system');
    }

    /** @return list<Message> */
    public function messages(): iterable
    {
        return [];
    }
}
