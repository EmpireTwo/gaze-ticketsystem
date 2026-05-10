<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Console\Commands;

use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Notifications\TicketFollowUpDueNotification;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;

use function Laravel\Prompts\info;

class CheckTicketFollowUpsCommand extends Command
{
    protected $signature = 'gaze-ticketsystem:check-follow-ups';

    protected $description = 'Check for tickets with due follow-ups and send notifications';

    /** @var array<int, string> */
    protected $aliases = ['ticket:check-follow-ups'];

    public function handle(): void
    {
        $tickets = Ticket::query()
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now())
            ->whereNull('closed_at')
            ->where(function ($query): void {
                $query->whereNull('follow_up_notified_at')
                    ->orWhere('follow_up_notified_at', '<', now()->startOfDay());
            })
            ->with(['assignee', 'creator'])
            ->get();

        if ($tickets->isEmpty()) {
            info('Keine fälligen Wiedervorlagen gefunden.');

            return;
        }

        $notified = 0;

        foreach ($tickets as $ticket) {
            /** @var Authenticatable|null $recipient */
            $recipient = $ticket->assignee ?? $ticket->creator;

            if ($recipient === null) {
                continue;
            }

            if (! method_exists($recipient, 'notify')) {
                continue;
            }

            $recipient->notify(new TicketFollowUpDueNotification($ticket));

            $ticket->update(['follow_up_notified_at' => now()]);
            $notified++;
        }

        info("{$notified} Wiedervorlage-Benachrichtigungen gesendet.");
    }
}
