<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Services;

use Empire2\GazeTicketsystem\Agents\TicketAnalysisAgent;
use Empire2\GazeTicketsystem\Agents\TicketCommentReplyAgent;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketComment;
use Empire2\GazeTicketsystem\Prompts\PromptResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class TicketAiAnalysisService
{
    private const array IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public function __construct(
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

        try {
            $response = $agent->prompt(
                $userPrompt,
                attachments: $attachments,
                provider: $this->resolveProvider(),
                model: $this->resolveModel(),
            );
        } catch (Throwable $e) {
            Log::error('Ticket AI pre-analysis failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response instanceof StructuredAgentResponse) {
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

        try {
            $response = $agent->prompt(
                $userPrompt,
                attachments: $attachments,
                provider: $this->resolveProvider(),
                model: $this->resolveModel(),
            );
        } catch (Throwable $e) {
            Log::error('Ticket AI analysis failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response instanceof StructuredAgentResponse) {
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

        try {
            $response = $agent->prompt(
                $userPrompt,
                attachments: $attachments,
                provider: $this->resolveProvider(),
                model: $this->resolveModel(),
            );
        } catch (Throwable $e) {
            Log::error('Ticket AI reply failed', [
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $body = (string) $response;

        if (trim($body) === '') {
            return null;
        }

        return $ticket->comments()->create([
            'user_id' => Auth::id(),
            'body' => $body,
            'is_ai_response' => true,
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
