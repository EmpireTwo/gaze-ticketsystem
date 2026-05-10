<div class="border rounded-lg overflow-hidden bg-white">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b bg-gray-50/50">
                @php
                    $columns = [
                        'ticket_number' => 'Ticket #',
                        'title' => 'Titel',
                        'status_id' => 'Status',
                        'priority' => 'Priorität',
                        'type_id' => 'Typ',
                        'assigned_to' => 'Zugewiesen',
                        'follow_up_at' => 'Wiedervorlage',
                        'created_at' => 'Erstellt',
                    ];
                @endphp
                @foreach ($columns as $col => $label)
                    <th wire:click="sortTable('{{ $col }}')"
                        class="px-4 py-2.5 text-left text-xs font-semibold text-zinc-500 cursor-pointer select-none hover:text-zinc-900 transition-colors">
                        <span class="inline-flex items-center gap-1">
                            {{ $label }}
                            @if ($sortBy === $col)
                                <span class="text-xs">{{ $sortDirection === 'desc' ? '▼' : '▲' }}</span>
                            @endif
                        </span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($this->sortedTickets as $ticket)
                <tr wire:click="selectTicket({{ $ticket->id }})" wire:key="list-ticket-{{ $ticket->id }}"
                    class="cursor-pointer transition-colors hover:bg-violet-50/50 {{ $selectedTicketId === $ticket->id ? 'bg-violet-50' : '' }}">
                    <td class="px-4 py-3 text-xs font-medium text-zinc-500 whitespace-nowrap">{{ $ticket->ticket_number }}</td>
                    <td class="px-4 py-3 font-medium max-w-xs truncate">{{ $ticket->title }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->status->color }}-50 text-{{ $ticket->status->color }}-700">
                            <span class="size-1.5 rounded-full bg-{{ $ticket->status->color }}-500"></span>
                            {{ $ticket->status->name }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium {{ $ticket->priority->badgeClasses() }}">
                            {{ $ticket->priority->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($ticket->type)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->type->color }}-50 text-{{ $ticket->type->color }}-600">
                                {{ $ticket->type->name }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($ticket->assignee)
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center rounded-full size-6 bg-gradient-to-br from-violet-500 to-purple-400 text-white text-xs font-semibold shrink-0">
                                    {{ strtoupper(substr((string) ($ticket->assignee->name ?? $ticket->assignee->email ?? '?'), 0, 1)) }}
                                </span>
                                <span class="text-xs">{{ $ticket->assignee->name ?? $ticket->assignee->email ?? $ticket->assignee->id }}</span>
                            </div>
                        @else
                            <span class="text-xs text-zinc-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($ticket->follow_up_at)
                            <span class="text-xs {{ $ticket->isOverdue() ? 'text-red-600 font-semibold' : 'text-zinc-500' }}">
                                {{ $ticket->follow_up_at->format('d.m.Y') }}
                            </span>
                        @else
                            <span class="text-xs text-zinc-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-zinc-500 whitespace-nowrap">{{ $ticket->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-sm text-zinc-500">Keine Tickets gefunden.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
