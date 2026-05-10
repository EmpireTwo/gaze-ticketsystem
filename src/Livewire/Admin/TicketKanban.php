<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Livewire\Admin;

use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;
use Empire2\GazeTicketsystem\Support\AdminResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Tickets · Kanban')]
class TicketKanban extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $assignedTo = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $priorityFilter = '';

    #[Url]
    public string $followUpFilter = '';

    public function layout(): string
    {
        return (string) config('gaze-ticketsystem.layout', 'components.layouts.app');
    }

    public function updateTicketStatus(int $ticketId, int $statusId): void
    {
        $ticket = Ticket::query()->findOrFail($ticketId);
        $status = TicketStatus::query()->findOrFail($statusId);

        $ticket->status_id = $status->id;

        if ($status->is_resolved && ! $ticket->resolved_at) {
            $ticket->resolved_at = Carbon::now();
        } elseif (! $status->is_resolved) {
            $ticket->resolved_at = null;
        }

        if ($status->is_closed && ! $ticket->closed_at) {
            $ticket->closed_at = Carbon::now();
        } elseif (! $status->is_closed) {
            $ticket->closed_at = null;
        }

        $ticket->save();

        $this->dispatch('gaze-ticketsystem-toast', message: 'Status aktualisiert.', level: 'success');
    }

    /**
     * @return Collection<int, TicketStatus>
     */
    #[Computed]
    public function statuses(): Collection
    {
        return TicketStatus::query()->ordered()->get();
    }

    /**
     * @return Collection<int, TicketType>
     */
    #[Computed]
    public function types(): Collection
    {
        return TicketType::all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Model>
     */
    #[Computed]
    public function admins(): \Illuminate\Support\Collection
    {
        return AdminResolver::resolve();
    }

    /**
     * @return array<int, Collection<int, Ticket>>
     */
    #[Computed]
    public function ticketsByStatus(): array
    {
        $query = Ticket::query()
            ->with(['status', 'type', 'assignee', 'creator']);

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('ticket_number', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->assignedTo !== '') {
            $query->where('assigned_to', $this->assignedTo);
        }

        if ($this->typeFilter !== '') {
            $query->where('type_id', $this->typeFilter);
        }

        if ($this->priorityFilter !== '') {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->followUpFilter === 'overdue') {
            $query->where('follow_up_at', '<=', now())->whereNull('closed_at');
        } elseif ($this->followUpFilter === 'today') {
            $query->whereDate('follow_up_at', today());
        } elseif ($this->followUpFilter === 'upcoming') {
            $query->where('follow_up_at', '>', now());
        }

        $tickets = $query->latest()->get();

        $grouped = [];
        foreach ($this->statuses as $status) {
            $grouped[$status->id] = $tickets->where('status_id', $status->id)->values();
        }

        return $grouped;
    }

    public function render(): View
    {
        return view('gaze-ticketsystem::ticket-kanban')
            ->layout($this->layout());
    }
}
