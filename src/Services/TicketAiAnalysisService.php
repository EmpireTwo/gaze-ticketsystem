<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Services;

use Empire2\GazeTicketsystem\Agents\TicketAnalysisAgent;
use Empire2\GazeTicketsystem\Agents\TicketCommentReplyAgent;
use Empire2\GazeTicketsystem\Ai\Contracts\GuardedAgentRunnerContract;
use Empire2\GazeTicketsystem\Ai\DTO\GuardedAgentResponse;
use Empire2\GazeTicketsystem\Ai\Exceptions\GazeDisabledException;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketComment;
use Empire2\GazeTicketsystem\Prompts\PromptResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files\Image;
use Naoray\GazeLaravel\Exceptions\GazeUnknownTokenException;
use Throwable;

/**
 * Wraps every outbound LLM call (analysis + reply generation) in a
 * Gaze::clean() / Gaze::restore() pair via the GuardedAgentRunner.
 *
 * IMAGE ATTACHMENTS ARE NOT REDACTED. Gaze is a text-only boundary; ticket
 * screenshots and other image attachments are forwarded to the AI provider
 * as-is. Each AI call with non-empty attachments emits a `Log::warning`
 * with the ticket id (when available) so the operator can audit out-of-band
 * PII exposure. Hosts whose compliance posture forbids this should disable
 * image upload entirely or set `gaze-ticketsystem.ai.gaze_enabled=false`
 * which fails the call closed.
 */
class TicketAiAnalysisService
{
    private const array IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function __construct(
        private readonly GuardedAgentRunnerContract $runner,
        private readonly PromptResolver $promptResolver = new PromptResolver,
    ) {}

    /**
     * Pre-create analysis: runs AI on raw input before a ticket exists.
     *
     * @param  list<string>  $imagePaths  Paths to temporary uploaded image files
     * @return array{text: string, structured: array<string, mixed>}|null
     */
    public function analyzeRaw(
        string $title,
        string $body,
        string $contactName,
        string $contactEmail,
        string $sourceContext = '',
        array $imagePaths = [],
    ): ?array {
        $agent = new TicketAnalysisAgent($this->promptResolver);

        $combinedBody = $sourceContext !== '' && $body !== ''
            ? "Kontext (Original-Nachricht):\n{$sourceContext}\n\nBeschreibung des Mitarbeiters:\n{$body}"
            : ($sourceContext !== '' ? $sourceContext : $body);

        $attachmentInfo = $imagePaths !== []
            ? 'Es wurden '.count($imagePaths).' Bild(er) angehängt. Bitte analysiere diese.'
            : '';

        $userPrompt = $this->promptResolver->resolve('ticket-analysis-user', [
            'ticketTitle' => $title,
            'contactName' => $contactName,
            'contactEmail' => $contactEmail,
            'ticketBody' => $combinedBody,
            'attachmentInfo' => $attachmentInfo,
        ]);

        $attachments = array_map(
            fn (string $path) => Image::fromPath($path, mime_content_type($path) ?: null),
            $imagePaths,
        );

        $this->warnAboutAttachments($attachments, ticketId: null);

        $response = $this->runGuarded($agent, $userPrompt, $attachments, context: ['stage' => 'analyze_raw']);

        if ($response === null || $response->structured === null) {
            return null;
        }

        /** @var array<string, mixed> $structured */
        $structured = $response->structured;

        return [
            'text' => $this->formatAnalysisAsComment($structured),
            'structured' => $structured,
        ];
    }

    public function analyze(Ticket $ticket, string $additionalPrompt = ''): ?TicketComment
    {
        $agent = new TicketAnalysisAgent($this->promptResolver);

        $userPrompt = $this->buildUserPrompt($ticket);

        if ($additionalPrompt !== '') {
            $userPrompt .= "\n\nZusätzliche Hinweise/Fragen vom Mitarbeiter:\n".$additionalPrompt;
        }

        $attachments = $this->buildImageAttachments($ticket);

        $this->warnAboutAttachments($attachments, ticketId: $ticket->id);

        $response = $this->runGuarded(
            $agent,
            $userPrompt,
            $attachments,
            context: ['stage' => 'analyze', 'ticket_id' => $ticket->id],
        );

        if ($response === null || $response->structured === null) {
            return null;
        }

        /** @var array<string, mixed> $structured */
        $structured = $response->structured;

        $body = $this->formatAnalysisAsComment($structured);

        return $ticket->comments()->create([
            'user_id' => Auth::id(),
            'body' => $body,
            'is_ai_response' => true,
        ]);
    }

    public function replyToComment(Ticket $ticket, TicketComment $comment): ?TicketComment
    {
        $agent = new TicketCommentReplyAgent($this->promptResolver);

        $conversationContext = $ticket->comments()
            ->orderBy('created_at')
            ->get()
            ->map(function (TicketComment $c): string {
                $authorName = $c->author->name ?? 'Mitarbeiter';
                $role = $c->is_ai_response ? 'AI-Buddy' : $authorName;
                $when = $c->created_at?->format('d.m.Y H:i') ?? '';

                return "[{$role}, {$when}]:\n{$c->body}";
            })
            ->implode("\n\n");

        $commenterName = $comment->author->name ?? 'Mitarbeiter';

        $userPrompt = "Ticket: {$ticket->title}\nKontakt: {$ticket->contact_name} ({$ticket->contact_email})";

        if (trim($ticket->body) !== '') {
            $userPrompt .= "\nBeschreibung: {$ticket->body}";
        }

        $userPrompt .= "\n\nDiskussionsverlauf:\n{$conversationContext}";
        $userPrompt .= "\n\n---\nReagiere kritisch auf diesen Kommentar von {$commenterName}:\n\"{$comment->body}\"";

        $attachments = $this->buildImageAttachments($ticket);

        $this->warnAboutAttachments($attachments, ticketId: $ticket->id);

        $response = $this->runGuarded(
            $agent,
            $userPrompt,
            $attachments,
            context: ['stage' => 'reply', 'ticket_id' => $ticket->id, 'comment_id' => $comment->id],
        );

        if ($response === null) {
            return null;
        }

        $body = $response->text;

        if (trim($body) === '') {
            return null;
        }

        return $ticket->comments()->create([
            'user_id' => Auth::id(),
            'body' => $body,
            'is_ai_response' => true,
        ]);
    }

    /**
     * @param  list<Image>  $attachments
     * @param  array<string, mixed>  $context
     */
    private function runGuarded(
        TicketAnalysisAgent|TicketCommentReplyAgent $agent,
        string $userPrompt,
        array $attachments,
        array $context,
    ): ?GuardedAgentResponse {
        try {
            return $this->runner->run(
                agent: $agent,
                message: $userPrompt,
                options: [
                    'provider' => $this->resolveProvider(),
                    'model' => $this->resolveModel(),
                    'attachments' => $attachments,
                ],
            );
        } catch (GazeDisabledException|GazeUnknownTokenException $e) {
            // Fail-closed: surface the disabled-boundary signal to the caller
            // (controllers / Livewire components) so they can render a clear
            // error instead of silently dropping the analysis.
            throw $e;
        } catch (Throwable $e) {
            Log::error('Ticket AI call failed', array_merge($context, [
                'error' => $e->getMessage(),
            ]));

            return null;
        }
    }

    /**
     * @param  list<Image>  $attachments
     */
    private function warnAboutAttachments(array $attachments, ?int $ticketId): void
    {
        if ($attachments === []) {
            return;
        }

        Log::warning('gaze-ticketsystem AI call with un-redactable image attachments', [
            'ticket_id' => $ticketId,
            'count' => count($attachments),
        ]);
    }

    private function buildUserPrompt(Ticket $ticket): string
    {
        $media = $ticket->getMedia('attachments');
        $imageCount = $media->filter(fn ($m): bool => in_array($m->mime_type, self::IMAGE_MIME_TYPES, true))->count();

        $attachmentInfo = $imageCount > 0
            ? "Es wurden {$imageCount} Bild(er) angehängt. Bitte analysiere diese."
            : '';

        return $this->promptResolver->resolve('ticket-analysis-user', [
            'ticketTitle' => $ticket->title,
            'contactName' => $ticket->contact_name,
            'contactEmail' => $ticket->contact_email,
            'ticketBody' => $ticket->body,
            'attachmentInfo' => $attachmentInfo,
        ]);
    }

    /**
     * @return list<Image>
     */
    private function buildImageAttachments(Ticket $ticket): array
    {
        return $ticket->getMedia('attachments')
            ->filter(fn ($media): bool => in_array($media->mime_type, self::IMAGE_MIME_TYPES, true))
            ->map(fn ($media) => Image::fromPath($media->getPath(), $media->mime_type))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    private function formatAnalysisAsComment(array $structured): string
    {
        $lines = [];

        $lines[] = '📋 AI-Analyse';
        $lines[] = '';

        if (isset($structured['summary'])) {
            $lines[] = 'Zusammenfassung: '.$structured['summary'];
            $lines[] = '';
        }

        if (isset($structured['suggested_category'])) {
            $lines[] = 'Vorgeschlagene Kategorie: '.$structured['suggested_category'];
        }

        if (isset($structured['suggested_priority'])) {
            $lines[] = 'Vorgeschlagene Priorität: '.$structured['suggested_priority'];
            $lines[] = '';
        }

        if (! empty($structured['key_observations'])) {
            $lines[] = 'Wichtige Beobachtungen:';
            foreach ($structured['key_observations'] as $observation) {
                $lines[] = '• '.$observation;
            }
            $lines[] = '';
        }

        if (! empty($structured['recommended_actions'])) {
            $lines[] = 'Empfohlene Maßnahmen:';
            foreach ($structured['recommended_actions'] as $action) {
                $lines[] = '• '.$action;
            }
        }

        return implode("\n", $lines);
    }

    private function resolveProvider(): string
    {
        $provider = config('ai.default', 'openai');

        return is_string($provider) && $provider !== '' ? $provider : 'openai';
    }

    private function resolveModel(): string
    {
        $model = config('gaze-ticketsystem.ai.analysis_model', 'gpt-4o-mini');

        return is_string($model) && $model !== '' ? $model : 'gpt-4o-mini';
    }
}
