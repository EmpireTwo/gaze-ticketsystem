<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Notifications;

use Empire2\GazeTicketsystem\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notification;

class TicketCommentAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly Authenticatable $commenter,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $commenterName = $this->resolveCommenterName();

        return [
            'type' => 'ticket_comment_added',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'message' => "{$commenterName} hat einen Kommentar zu {$this->ticket->ticket_number} hinzugefügt.",
            'url' => $this->ticketUrl(),
        ];
    }

    private function resolveCommenterName(): string
    {
        $commenter = $this->commenter;

        $name = $commenter->name ?? null;

        if (is_string($name) && $name !== '') {
            return $name;
        }

        $email = $commenter->email ?? null;

        return is_string($email) && $email !== '' ? $email : 'Unbekannt';
    }

    private function ticketUrl(): ?string
    {
        try {
            return route('gaze-ticketsystem.tickets.show', $this->ticket);
        } catch (\Throwable) {
            return null;
        }
    }
}
