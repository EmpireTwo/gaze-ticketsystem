<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Notifications;

use Empire2\GazeTicketsystem\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_assigned',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
            'message' => "Ticket {$this->ticket->ticket_number} wurde dir zugewiesen: {$this->ticket->title}",
            'url' => $this->ticketUrl(),
        ];
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
