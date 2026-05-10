<div class="flex flex-wrap items-stretch gap-0 bg-zinc-50 rounded-lg border">
    <div class="flex-1 min-w-[120px] px-3 py-2.5 border-r">
        <label class="block mb-1 text-xs font-semibold text-zinc-500 uppercase">Status</label>
        <select wire:model.live="statusId" class="w-full rounded border px-2 py-1 text-sm">
            @foreach ($this->statuses as $status)
                <option value="{{ $status->id }}">{{ $status->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 min-w-[120px] px-3 py-2.5 border-r">
        <label class="block mb-1 text-xs font-semibold text-zinc-500 uppercase">Typ</label>
        <select wire:model.live="typeId" class="w-full rounded border px-2 py-1 text-sm">
            @foreach ($this->types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 min-w-[120px] px-3 py-2.5 border-r">
        <label class="block mb-1 text-xs font-semibold text-zinc-500 uppercase">Priorität</label>
        <select wire:model.live="priorityValue" class="w-full rounded border px-2 py-1 text-sm">
            @foreach ($this->priorities as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 min-w-[120px] px-3 py-2.5 border-r">
        <label class="block mb-1 text-xs font-semibold text-zinc-500 uppercase">Zugewiesen</label>
        <select wire:model.live="assigneeId" class="w-full rounded border px-2 py-1 text-sm">
            <option value="">Nicht zugewiesen</option>
            @foreach ($this->admins as $admin)
                <option value="{{ $admin->id }}">{{ $admin->name ?? $admin->email ?? $admin->id }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex-1 min-w-[120px] px-3 py-2.5 border-r">
        <label class="block mb-1 text-xs font-semibold text-zinc-500 uppercase">Wiedervorlage</label>
        <div class="flex items-center gap-1.5">
            <input wire:model="followUpDate" type="date" class="flex-1 rounded border px-2 py-1 text-sm" />
            <button wire:click="setFollowUp" class="rounded border px-2 py-1 text-xs">✓</button>
        </div>
        @if ($ticket->isOverdue())
            <p class="mt-1 text-xs font-medium text-red-500">Überfällig seit {{ $ticket->follow_up_at->diffForHumans() }}</p>
        @endif
    </div>

    <div class="flex-1 min-w-[120px] px-3 py-2.5">
        <label class="block mb-1 text-xs font-semibold text-zinc-500 uppercase">Kontakt</label>
        <p class="text-xs font-medium truncate">{{ $ticket->contact_name }}</p>
        <p class="text-xs text-zinc-500 truncate">{{ $ticket->contact_email }}</p>
    </div>
</div>
