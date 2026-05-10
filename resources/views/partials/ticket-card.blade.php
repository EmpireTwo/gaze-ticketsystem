<div draggable="true"
     @dragstart="handleDragStart($event, {{ $ticket->id }})"
     @dragend="handleDragEnd($event)"
     class="p-3 bg-white border rounded-lg cursor-grab hover:shadow-sm transition-shadow group">
    <div wire:click="selectTicket({{ $ticket->id }})" class="block no-underline cursor-pointer">
        {{-- Header: Ticket number + Priority --}}
        <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="text-xs font-medium text-zinc-500">{{ $ticket->ticket_number }}</span>
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium {{ $ticket->priority->badgeClasses() }}">
                {{ $ticket->priority->label() }}
            </span>
        </div>

        {{-- Title --}}
        <h3 class="text-sm font-medium leading-snug line-clamp-2 group-hover:text-violet-700">
            {{ $ticket->title }}
        </h3>

        {{-- Footer: Type, Assignee, Follow-up --}}
        <div class="flex items-center gap-2 mt-2.5">
            @if ($ticket->type)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs bg-{{ $ticket->type->color }}-50 text-{{ $ticket->type->color }}-600">
                    {{ $ticket->type->name }}
                </span>
            @endif

            <div class="flex items-center gap-1.5 ml-auto">
                @if ($ticket->follow_up_at)
                    <span title="Wiedervorlage: {{ $ticket->follow_up_at->format('d.m.Y') }}" class="flex items-center {{ $ticket->isOverdue() ? 'text-red-500' : 'text-zinc-500' }}">
                        ⏱
                    </span>
                @endif

                @if ($ticket->assignee)
                    <span title="{{ $ticket->assignee->name ?? $ticket->assignee->email ?? '' }}"
                          class="flex items-center justify-center rounded-full size-5 bg-gradient-to-br from-violet-500 to-purple-400 text-white text-xs font-semibold shrink-0">
                        {{ strtoupper(substr((string) ($ticket->assignee->name ?? $ticket->assignee->email ?? '?'), 0, 1)) }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
