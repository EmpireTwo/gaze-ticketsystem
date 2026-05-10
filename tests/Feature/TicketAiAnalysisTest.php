<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Agents\TicketAnalysisAgent;
use Empire2\GazeTicketsystem\Agents\TicketCommentReplyAgent;
use Empire2\GazeTicketsystem\Ai\Exceptions\GazeDisabledException;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketComment;
use Empire2\GazeTicketsystem\Prompts\PromptResolver;
use Empire2\GazeTicketsystem\Services\TicketAiAnalysisService;
use Laravel\Ai\Ai;
use Naoray\GazeLaravel\Facades\Gaze;

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

    $service = app(TicketAiAnalysisService::class);

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

test('analyze routes ticket text through Gaze::clean before reaching the LLM', function () {
    $admin = ticketAdmin();
    $this->actingAs($admin);

    Ai::fakeAgent(TicketAnalysisAgent::class, [
        [
            'summary' => 'Benutzer kann sich nicht einloggen.',
            'suggested_category' => 'Support',
            'suggested_priority' => 'high',
            'key_observations' => ['500er Fehler'],
            'recommended_actions' => ['Logs prüfen'],
        ],
    ]);

    $ticket = Ticket::factory()->create([
        'title' => 'Login kaputt',
        'contact_name' => 'Max Mustermann',
        'contact_email' => 'max@example.com',
        'body' => 'Kann mich nicht einloggen, Fehler 500.',
    ]);

    $service = app(TicketAiAnalysisService::class);
    $comment = $service->analyze($ticket);

    expect($comment)->toBeInstanceOf(TicketComment::class)
        ->and($comment->is_ai_response)->toBeTrue()
        ->and($comment->body)->toContain('AI-Analyse');

    // Boundary proof: every text payload reaching the LLM must first transit
    // Gaze::clean(). The ticket title + contact appear in the user-prompt.
    Gaze::assertCleaned();
    Gaze::assertRestored();
});

test('replyToComment routes prompt through Gaze::clean before reaching the LLM', function () {
    $admin = ticketAdmin();
    $this->actingAs($admin);

    Ai::fakeAgent(TicketCommentReplyAgent::class, [
        'Antwort vom AI-Buddy: bitte logs anschauen.',
    ]);

    $ticket = Ticket::factory()->create([
        'title' => 'Login kaputt',
        'contact_name' => 'Max Mustermann',
        'contact_email' => 'max@example.com',
        'body' => 'Kann mich nicht einloggen.',
    ]);

    $comment = $ticket->comments()->create([
        'user_id' => $admin->id,
        'body' => 'Bitte schnell prüfen.',
        'is_ai_response' => false,
    ]);

    $service = app(TicketAiAnalysisService::class);
    $reply = $service->replyToComment($ticket, $comment);

    expect($reply)->toBeInstanceOf(TicketComment::class)
        ->and($reply->is_ai_response)->toBeTrue()
        ->and($reply->body)->toContain('AI-Buddy');

    Gaze::assertCleaned();
    Gaze::assertRestored();
});

test('analyzeRaw routes the prompt through Gaze::clean before reaching the LLM', function () {
    $admin = ticketAdmin();
    $this->actingAs($admin);

    Ai::fakeAgent(TicketAnalysisAgent::class, [
        [
            'summary' => 'Vorab-Analyse.',
            'suggested_category' => 'Support',
            'suggested_priority' => 'normal',
            'key_observations' => ['Test'],
            'recommended_actions' => ['Action'],
        ],
    ]);

    $service = app(TicketAiAnalysisService::class);
    $result = $service->analyzeRaw(
        title: 'Login kaputt',
        body: 'Kann mich nicht einloggen.',
        contactName: 'Max Mustermann',
        contactEmail: 'max@example.com',
    );

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('text')
        ->and($result['text'])->toContain('AI-Analyse');

    Gaze::assertCleaned();
});

test('analyze fails closed when gaze_enabled is false', function () {
    config()->set('gaze-ticketsystem.ai.gaze_enabled', false);

    $admin = ticketAdmin();
    $this->actingAs($admin);

    // No agent fake required — the boundary refuses before the agent runs.
    $ticket = Ticket::factory()->create([
        'title' => 'Login kaputt',
        'contact_name' => 'Max',
        'contact_email' => 'max@example.com',
        'body' => 'Test',
    ]);

    $service = app(TicketAiAnalysisService::class);

    expect(fn () => $service->analyze($ticket))->toThrow(GazeDisabledException::class);

    // No outbound clean call — the runner exits before Gaze::clean is invoked.
    Gaze::assertNothingCleaned();
});
