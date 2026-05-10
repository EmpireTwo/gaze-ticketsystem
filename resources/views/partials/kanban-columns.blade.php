<div class="flex gap-4 pb-4 overflow-x-auto flex-1 min-h-0"
     x-data="{
         dragTicketId: null,
         dragOverStatus: null,
         handleDragStart(e, ticketId) {
             this.dragTicketId = ticketId;
             e.dataTransfer.effectAllowed = 'move';
             e.target.classList.add('opacity-50');
         },
         handleDragEnd(e) {
             e.target.classList.remove('opacity-50');
             this.dragTicketId = null;
             this.dragOverStatus = null;
         },
         handleDragOver(e, statusId) {
             e.preventDefault();
             e.dataTransfer.dropEffect = 'move';
             this.dragOverStatus = statusId;
         },
         handleDragLeave(e, statusId) {
             if (!e.currentTarget.contains(e.relatedTarget)) {
                 this.dragOverStatus = null;
             }
         },
         handleDrop(e, statusId) {
             e.preventDefault();
             if (this.dragTicketId) {
                 $wire.updateTicketStatus(this.dragTicketId, statusId);
             }
             this.dragOverStatus = null;
             this.dragTicketId = null;
         }
     }">
    @foreach ($this->statuses as $status)
        <div class="flex flex-col flex-shrink-0 w-72 min-h-0"
             @dragover.prevent="handleDragOver($event, {{ $status->id }})"
             @dragleave="handleDragLeave($event, {{ $status->id }})"
             @drop="handleDrop($event, {{ $status->id }})">
            {{-- Column Header --}}
            <div class="flex items-center gap-2 px-3 py-2.5 mb-2 rounded-lg bg-{{ $status->color }}-50">
                <span class="size-2.5 rounded-full bg-{{ $status->color }}-500"></span>
                <span class="text-sm font-semibold text-{{ $status->color }}-700">{{ $status->name }}</span>
                <span class="ml-auto text-xs font-medium text-{{ $status->color }}-400">
                    {{ ($this->ticketsByStatus[$status->id] ?? collect())->count() }}
                </span>
            </div>

            {{-- Cards --}}
            <div class="flex flex-col flex-1 gap-2 p-1 overflow-y-auto rounded-lg transition-colors duration-150"
                 :class="dragOverStatus === {{ $status->id }} ? 'bg-{{ $status->color }}-50/50 ring-2 ring-{{ $status->color }}-200 ring-dashed' : ''">
                @forelse ($this->ticketsByStatus[$status->id] ?? [] as $ticket)
                    @include('gaze-ticketsystem::partials.ticket-card', ['ticket' => $ticket])
                @empty
                    <div class="py-8 text-xs text-center text-zinc-400">Keine Tickets</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
