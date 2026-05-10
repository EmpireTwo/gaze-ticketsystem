<div class="flex flex-col gap-3 p-4 w-72">
    <label class="block text-xs font-semibold text-zinc-500">Mitarbeiter</label>
    <select wire:model.live="assignedTo" class="rounded border px-3 py-1.5 text-sm">
        <option value="">Alle Mitarbeiter</option>
        @foreach ($this->admins as $admin)
            <option value="{{ $admin->id }}">{{ $admin->name ?? $admin->email ?? $admin->id }}</option>
        @endforeach
    </select>

    <label class="block text-xs font-semibold text-zinc-500">Typ</label>
    <select wire:model.live="typeFilter" class="rounded border px-3 py-1.5 text-sm">
        <option value="">Alle Typen</option>
        @foreach ($this->types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
        @endforeach
    </select>

    <label class="block text-xs font-semibold text-zinc-500">Priorität</label>
    <select wire:model.live="priorityFilter" class="rounded border px-3 py-1.5 text-sm">
        <option value="">Alle Prioritäten</option>
        @foreach (\Empire2\GazeTicketsystem\Enums\Priority::cases() as $p)
            <option value="{{ $p->value }}">{{ $p->label() }}</option>
        @endforeach
    </select>

    <label class="block text-xs font-semibold text-zinc-500">Wiedervorlage</label>
    <select wire:model.live="followUpFilter" class="rounded border px-3 py-1.5 text-sm">
        <option value="">Alle</option>
        <option value="overdue">Überfällig</option>
        <option value="today">Heute fällig</option>
        <option value="upcoming">Anstehend</option>
    </select>
</div>
