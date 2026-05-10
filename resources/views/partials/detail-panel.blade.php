@php
    $ticket = $this->selectedTicket;
    $statusTickets = $this->ticketsByStatus[$ticket->status_id] ?? collect();
    $currentIndex = $statusTickets->search(fn ($t) => $t->id === $ticket->id);
    $position = $currentIndex !== false ? $currentIndex + 1 : 1;
    $total = $statusTickets->count();
@endphp

<div class="flex flex-col h-full transition-all ease-out overflow-hidden" data-testid="ticket-detail"
     x-data="{ visible: true, transitioning: false }"
     x-init="$watch(() => $wire.selectedTicketId, () => {
         if (transitioning) return;
         transitioning = true;
         visible = false;
         setTimeout(() => { visible = true; setTimeout(() => transitioning = false, 500) }, 300);
     })"
     @detail-closing.window="visible = false"
     :class="visible ? 'opacity-100 translate-x-0 duration-500' : 'opacity-0 translate-x-24 duration-300'"
>
    <div class="flex items-center gap-3 px-4 py-2.5 border-b bg-white shrink-0">
        <span class="text-xs font-medium text-zinc-500">{{ $ticket->ticket_number }}</span>
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $ticket->priority->badgeClasses() }}">
            {{ $ticket->priority->label() }}
        </span>

        <span class="ml-auto text-xs text-zinc-500">{{ $position }} / {{ $total }}</span>

        <button wire:click="navigateTicket('previous')" class="rounded border px-2 py-1 text-xs" :disabled="$position <= 1">↑</button>
        <button wire:click="navigateTicket('next')" class="rounded border px-2 py-1 text-xs" :disabled="$position >= $total">↓</button>
        <button @click="$dispatch('detail-closing'); setTimeout(() => $wire.closeDetail(), 400)" class="rounded border px-2 py-1 text-xs">×</button>
    </div>

    <div class="flex-1 overflow-y-auto overflow-x-hidden">
        <livewire:gaze-ticketsystem.ticket-show :ticket="$ticket" :key="'ticket-show-'.$ticket->id" />
    </div>
</div>
