<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Prompts\PromptResolver;
use Empire2\GazeTicketsystem\Services\TicketAiAnalysisService;

beforeEach(function () {
    seedTicketDefaults();
});

test('prompt resolver resolves ticket analysis system prompt', function () {
    $resolver = new PromptResolver;
    $prompt = $resolver->resolve('ticket-analysis-system');

    expect($prompt)->toContain('Support-Analyse-Assistent')
        ->and($prompt)->toContain('summary');
});

test('prompt resolver resolves user prompt with variables', function () {
    $resolver = new PromptResolver;
    $prompt = $resolver->resolve('ticket-analysis-user', [
        'ticketTitle' => 'Login kaputt',
        'contactName' => 'Max',
        'contactEmail' => 'max@test.de',
        'ticketBody' => 'Kann mich nicht einloggen',
        'attachmentInfo' => '',
    ]);

    expect($prompt)->toContain('Login kaputt')
        ->and($prompt)->toContain('Max')
        ->and($prompt)->toContain('Kann mich nicht einloggen');
});

test('ai analysis service formats result as comment', function () {
    $ticket = Ticket::factory()->create();
    $admin = ticketAdmin();

    $this->actingAs($admin);

    $service = new TicketAiAnalysisService;

    $formatMethod = new ReflectionMethod($service, 'formatAnalysisAsComment');

    $result = $formatMethod->invoke($service, [
        'summary' => 'Benutzer kann sich nicht einloggen.',
        'suggested_category' => 'Support',
        'suggested_priority' => 'high',
        'key_observations' => ['Login-Seite gibt 500er', 'Seit heute Morgen'],
        'recommended_actions' => ['Server-Logs prüfen', 'Auth-Service neustarten'],
    ]);

    expect($result)->toContain('AI-Analyse')
        ->and($result)->toContain('Benutzer kann sich nicht einloggen')
        ->and($result)->toContain('Support')
        ->and($result)->toContain('high')
        ->and($result)->toContain('Login-Seite gibt 500er')
        ->and($result)->toContain('Server-Logs prüfen');
});
