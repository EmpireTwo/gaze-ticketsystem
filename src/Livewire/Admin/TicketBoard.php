<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Livewire\Admin;

use Empire2\GazeTicketsystem\Enums\Priority;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;
use Empire2\GazeTicketsystem\Support\AdminResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Tickets')]
class TicketBoard extends Component
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

    #[Url(as: 'view')]
    public string $viewMode = 'kanban';

    #[Url(as: 'ticket')]
    public ?int $selectedTicketId = null;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(?Ticket $ticket = null): void
    {
        if ($ticket?->exists) {
            $this->selectedTicketId = $ticket->id;
        }
    }

    public function layout(): string
    {
        return (string) config('gaze-ticketsystem.layout', 'components.layouts.app');
    }

    public function selectTicket(int $ticketId): void
    {
        $this->selectedTicketId = $ticketId;
    }

    public function closeDetail(): void
    {
        $this->selectedTicketId = null;
    }

    public function navigateTicket(string $direction): void
    {
        if (! $this->selectedTicketId) {
            return;
        }

        $currentTicket = Ticket::query()->find($this->selectedTicketId);

        if (! $currentTicket) {
            return;
        }

        $statusTickets = $this->ticketsByStatus[$currentTicket->status_id] ?? collect();
        $currentIndex = $statusTickets->search(fn (Ticket $t) => $t->id === $this->selectedTicketId);

        if ($currentIndex === false) {
            return;
        }

        $newIndex = $direction === 'next' ? $currentIndex + 1 : $currentIndex - 1;

        if ($newIndex >= 0 && $newIndex < $statusTickets->count()) {
            $this->selectedTicketId = $statusTickets[$newIndex]->id;
        }
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

    /** @var list<string> */
    private const ALLOWED_SORT_COLUMNS = [
        'ticket_number',
        'title',
        'priority',
        'status_id',
        'type_id',
        'assigned_to',
        'created_by',
        'created_at',
        'updated_at',
        'resolved_at',
        'closed_at',
        'follow_up_at',
    ];

    public function sortTable(string $column): void
    {
        if (! in_array($column, self::ALLOWED_SORT_COLUMNS, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilter(string $filter): void
    {
        if (property_exists($this, $filter)) {
            $this->{$filter} = '';
        }
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->assignedTo !== ''
            || $this->typeFilter !== ''
            || $this->priorityFilter !== ''
            || $this->followUpFilter !== '';
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function activeFilterChips(): array
    {
        $chips = [];

        if ($this->search !== '') {
            $chips[] = ['key' => 'search', 'label' => 'Suche: '.$this->search];
        }

        if ($this->assignedTo !== '') {
            $user = $this->admins->firstWhere('id', (int) $this->assignedTo);
            $chips[] = [
                'key' => 'assignedTo',
                'label' => 'Zugewiesen: '.($user?->name ?? $this->assignedTo),
            ];
        }

        if ($this->typeFilter !== '') {
            $type = TicketType::query()->find($this->typeFilter);
            $chips[] = ['key' => 'typeFilter', 'label' => 'Typ: '.($type->name ?? $this->typeFilter)];
        }

        if ($this->priorityFilter !== '') {
            $priority = Priority::tryFrom($this->priorityFilter);
            $chips[] = [
                'key' => 'priorityFilter',
                'label' => 'Priorität: '.($priority?->label() ?? $this->priorityFilter),
            ];
        }

        if ($this->followUpFilter !== '') {
            $label = match ($this->followUpFilter) {
                'overdue' => 'Überfällig',
                'today' => 'Heute',
                'upcoming' => 'Bevorstehend',
                default => $this->followUpFilter,
            };
            $chips[] = ['key' => 'followUpFilter', 'label' => 'Wiedervorlage: '.$label];
        }

        return $chips;
    }

    #[On('ticket-created')]
    public function onTicketCreated(int $ticketId): void
    {
        $this->selectedTicketId = $ticketId;
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

    #[Computed]
    public function selectedTicket(): ?Ticket
    {
        if (! $this->selectedTicketId) {
            return null;
        }

        return Ticket::query()
            ->with(['status', 'type', 'assignee', 'creator', 'customer', 'comments.author', 'media'])
            ->find($this->selectedTicketId);
    }

    /**
     * @return array<int, Collection<int, Ticket>>
     */
    #[Computed]
    public function ticketsByStatus(): array
    {
        $tickets = $this->filteredTickets()->latest()->get();

        $grouped = [];
        foreach ($this->statuses as $status) {
            $grouped[$status->id] = $tickets->where('status_id', $status->id)->values();
        }

        return $grouped;
    }

    /**
     * @return Collection<int, Ticket>
     */
    #[Computed]
    public function sortedTickets(): Collection
    {
        return $this->filteredTickets()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    /**
     * @return Builder<Ticket>
     */
    private function filteredTickets(): Builder
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

        return $query;
    }

    public function render(): View
    {
        return view('gaze-ticketsystem::ticket-board')
            ->layout($this->layout());
    }
}
