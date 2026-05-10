@php
    $activeStatusId = $this->selectedTicket?->status_id;
@endphp

<div class="flex flex-col h-full overflow-y-auto"
     x-data="{ expandedGroups: { {{ $activeStatusId }}: true } }"
     x-init="expandedGroups[{{ $activeStatusId }}] = true">

    @foreach ($this->statuses as $status)
        @php
            $statusTickets = $this->ticketsByStatus[$status->id] ?? collect();
        @endphp

        <button type="button"
                @click="expandedGroups[{{ $status->id }}] = !expandedGroups[{{ $status->id }}]"
                class="flex items-center gap-2 w-full px-3 py-2 bg-{{ $status->color }}-50 text-left sticky top-0 z-10 border-b border-{{ $status->color }}-100">
            <span class="text-xs text-{{ $status->color }}-400 transition-transform duration-150"
                  :class="expandedGroups[{{ $status->id }}] ? 'rotate-90' : ''">&#9654;</span>
            <span class="size-2 rounded-full bg-{{ $status->color }}-500"></span>
            <span class="text-xs font-semibold text-{{ $status->color }}-700">{{ $status->name }}</span>
            <span class="ml-auto text-xs font-medium text-{{ $status->color }}-400">{{ $statusTickets->count() }}</span>
        </button>

        <div x-show="expandedGroups[{{ $status->id }}]">
            @forelse ($statusTickets as $ticket)
                <div wire:click="selectTicket({{ $ticket->id }})"
                     wire:key="split-ticket-{{ $ticket->id }}"
                     class="ml-1 mx-2 my-1 p-2.5 rounded-lg border cursor-pointer transition-all duration-100
                        {{ $this->selectedTicketId === $ticket->id
                            ? 'bg-violet-50 border-violet-500 ring-1 ring-violet-500'
                            : 'bg-white hover:bg-zinc-50' }}">

                    <div class="flex items-center justify-between gap-1 mb-1">
                        <span class="text-xs font-medium text-zinc-500">{{ $ticket->ticket_number }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $ticket->priority->badgeClasses() }}">
                            {{ $ticket->priority->label() }}
                        </span>
                    </div>

                    <h4 class="text-xs font-medium leading-snug line-clamp-2">
                        {{ $ticket->title }}
                    </h4>

                    <div class="flex items-center gap-1.5 mt-1.5">
                        @if ($ticket->type)
                            <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded text-xs bg-{{ $ticket->type->color }}-50 text-{{ $ticket->type->color }}-600">
                                {{ $ticket->type->name }}
                            </span>
                        @endif

                        <div class="flex items-center gap-1 ml-auto">
                            @if ($ticket->follow_up_at)
                                <span title="Wiedervorlage: {{ $ticket->follow_up_at->format('d.m.Y') }}"
                                      class="{{ $ticket->isOverdue() ? 'text-red-500' : 'text-zinc-500' }}">⏱</span>
                            @endif

                            @if ($ticket->assignee)
                                <span title="{{ $ticket->assignee->name ?? $ticket->assignee->email ?? '' }}"
                                      class="flex items-center justify-center rounded-full size-4 bg-gradient-to-br from-violet-500 to-purple-400 text-white text-xs font-semibold shrink-0">
                                    {{ strtoupper(substr((string) ($ticket->assignee->name ?? $ticket->assignee->email ?? '?'), 0, 1)) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-3 text-center text-xs text-zinc-400">Keine Tickets</div>
            @endforelse
        </div>
    @endforeach
</div>
