<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Agents;

use Empire2\GazeTicketsystem\Prompts\PromptResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class TicketAnalysisAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;

    private readonly PromptResolver $promptResolver;

    public function __construct(?PromptResolver $promptResolver = null)
    {
        $this->promptResolver = $promptResolver ?? new PromptResolver;
    }

    public function instructions(): Stringable|string
    {
        return $this->promptResolver->resolve('ticket-analysis-system');
    }

    /** @return list<Message> */
    public function messages(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'suggested_category' => $schema->string()->required(),
            'suggested_priority' => $schema->string()->required(),
            'key_observations' => $schema->array()
                ->items($schema->string())
                ->required(),
            'recommended_actions' => $schema->array()
                ->items($schema->string())
                ->required(),
        ];
    }
}
