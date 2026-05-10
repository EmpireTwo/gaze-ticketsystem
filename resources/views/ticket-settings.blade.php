<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('gaze-ticketsystem.tickets.board') }}" wire:navigate class="rounded border px-3 py-1.5 text-sm">← Zurück</a>
        <h1 class="text-xl font-semibold">Ticket-Einstellungen</h1>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        <div class="p-5 bg-white border rounded-lg">
            <h2 class="mb-4 text-base font-semibold">Status (Kanban-Spalten)</h2>

            <div class="flex flex-col gap-2 mb-4">
                @foreach ($statuses as $status)
                    <div class="flex items-center gap-3 px-3 py-2 rounded-md bg-zinc-50 group">
                        @if ($editingStatusId === $status->id)
                            <div class="flex flex-col flex-1 gap-2">
                                <input wire:model="editingStatusName" placeholder="Name" class="rounded border px-2 py-1 text-sm" />
                                <div class="flex gap-2">
                                    <input wire:model="editingStatusColor" placeholder="Farbe" class="w-24 rounded border px-2 py-1 text-sm" />
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" wire:model="editingStatusIsResolved" class="rounded"> Gelöst
                                    </label>
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" wire:model="editingStatusIsClosed" class="rounded"> Geschlossen
                                    </label>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="saveStatus" class="rounded bg-violet-600 text-white px-3 py-1 text-xs">Speichern</button>
                                    <button wire:click="$set('editingStatusId', null)" class="rounded border px-3 py-1 text-xs">Abbrechen</button>
                                </div>
                            </div>
                        @else
                            <span class="size-3 rounded-full bg-{{ $status->color }}-500 shrink-0"></span>
                            <span class="flex-1 text-sm font-medium">{{ $status->name }}</span>
                            <div class="flex items-center gap-1 text-xs text-zinc-500">
                                @if ($status->is_default)
                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded">Standard</span>
                                @endif
                                @if ($status->is_resolved)
                                    <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-600 rounded">Gelöst</span>
                                @endif
                                @if ($status->is_closed)
                                    <span class="px-1.5 py-0.5 bg-zinc-100 text-zinc-600 rounded">Geschlossen</span>
                                @endif
                            </div>
                            <div class="hidden gap-1 group-hover:flex">
                                <button wire:click="editStatus({{ $status->id }})" class="text-zinc-500 hover:text-violet-700">✎</button>
                                @unless ($status->is_default)
                                    <button wire:click="deleteStatus({{ $status->id }})" wire:confirm="Status wirklich löschen?" class="text-zinc-500 hover:text-red-500">🗑</button>
                                @endunless
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex items-end gap-2 pt-3 border-t">
                <input wire:model="newStatusName" placeholder="Neuer Status" class="flex-1 rounded border px-2 py-1 text-sm" />
                <input wire:model="newStatusColor" placeholder="Farbe" class="w-24 rounded border px-2 py-1 text-sm" />
                <button wire:click="addStatus" class="rounded bg-violet-600 text-white px-3 py-1 text-sm">+ Hinzufügen</button>
            </div>
        </div>

        <div class="p-5 bg-white border rounded-lg">
            <h2 class="mb-4 text-base font-semibold">Typen</h2>

            <div class="flex flex-col gap-2 mb-4">
                @foreach ($types as $type)
                    <div class="flex items-center gap-3 px-3 py-2 rounded-md bg-zinc-50 group">
                        @if ($editingTypeId === $type->id)
                            <div class="flex flex-col flex-1 gap-2">
                                <input wire:model="editingTypeName" placeholder="Name" class="rounded border px-2 py-1 text-sm" />
                                <div class="flex gap-2">
                                    <input wire:model="editingTypeColor" placeholder="Farbe" class="w-24 rounded border px-2 py-1 text-sm" />
                                    <input wire:model="editingTypeIcon" placeholder="Icon" class="flex-1 rounded border px-2 py-1 text-sm" />
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="saveType" class="rounded bg-violet-600 text-white px-3 py-1 text-xs">Speichern</button>
                                    <button wire:click="$set('editingTypeId', null)" class="rounded border px-3 py-1 text-xs">Abbrechen</button>
                                </div>
                            </div>
                        @else
                            <span class="size-3 rounded-full bg-{{ $type->color }}-500 shrink-0"></span>
                            <span class="flex-1 text-sm font-medium">{{ $type->name }}</span>
                            <div class="hidden gap-1 group-hover:flex">
                                <button wire:click="editType({{ $type->id }})" class="text-zinc-500 hover:text-violet-700">✎</button>
                                <button wire:click="deleteType({{ $type->id }})" wire:confirm="Typ wirklich löschen?" class="text-zinc-500 hover:text-red-500">🗑</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex items-end gap-2 pt-3 border-t">
                <input wire:model="newTypeName" placeholder="Neuer Typ" class="flex-1 rounded border px-2 py-1 text-sm" />
                <input wire:model="newTypeColor" placeholder="Farbe" class="w-20 rounded border px-2 py-1 text-sm" />
                <input wire:model="newTypeIcon" placeholder="Icon" class="w-28 rounded border px-2 py-1 text-sm" />
                <button wire:click="addType" class="rounded bg-violet-600 text-white px-3 py-1 text-sm">+ Hinzufügen</button>
            </div>
        </div>
    </div>
</div>
