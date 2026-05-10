<div class="flex flex-col h-full gap-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Tickets</h1>
        <div class="flex items-center gap-3">
            <input wire:model.live.debounce.300ms="search" placeholder="Suche..." class="w-64 rounded border px-3 py-1.5 text-sm" />
            <a href="{{ route('gaze-ticketsystem.tickets.board') }}" wire:navigate class="rounded bg-violet-600 text-white px-3 py-1.5 text-sm">
                Neues Ticket
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <select wire:model.live="assignedTo" class="w-44 rounded border px-3 py-1.5 text-sm">
            <option value="">Alle Mitarbeiter</option>
            @foreach ($this->admins as $admin)
                <option value="{{ $admin->id }}">{{ $admin->name ?? $admin->email ?? $admin->id }}</option>
            @endforeach
        </select>

        <select wire:model.live="typeFilter" class="w-40 rounded border px-3 py-1.5 text-sm">
            <option value="">Alle Typen</option>
            @foreach ($this->types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="priorityFilter" class="w-40 rounded border px-3 py-1.5 text-sm">
            <option value="">Alle Prioritäten</option>
            @foreach (\Empire2\GazeTicketsystem\Enums\Priority::cases() as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="followUpFilter" class="w-44 rounded border px-3 py-1.5 text-sm">
            <option value="">Alle</option>
            <option value="overdue">Überfällig</option>
            <option value="today">Heute fällig</option>
            <option value="upcoming">Anstehend</option>
        </select>
    </div>

    {{-- Kanban Board --}}
    @include('gaze-ticketsystem::partials.kanban-columns')
</div>
