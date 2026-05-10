<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('gaze-ticketsystem.tickets.board') }}" wire:navigate class="rounded border px-3 py-1.5 text-sm">← Zurück</a>
        <h1 class="text-xl font-semibold">Neues Ticket</h1>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <div>
            <label class="block mb-1.5 text-sm font-medium">Titel</label>
            <input wire:model="title" placeholder="Kurze Beschreibung des Tickets" class="w-full rounded border px-3 py-1.5 text-sm" required />
        </div>

        @if ($sourceContext)
            <div>
                <label class="block mb-1.5 text-xs font-semibold text-zinc-500 uppercase">Original-Nachricht</label>
                <div class="p-3 text-sm leading-relaxed bg-zinc-50 border rounded-lg max-h-48 overflow-y-auto whitespace-pre-wrap">{{ $sourceContext }}</div>
            </div>
        @endif

        <div>
            <label class="block mb-1.5 text-sm font-medium">Beschreibung</label>
            <textarea wire:model="body" rows="5" placeholder="Was soll erledigt werden?" class="w-full rounded border px-3 py-1.5 text-sm" required></textarea>
        </div>

        @if (config('gaze-ticketsystem.ai.enabled', true))
            <div class="flex flex-col gap-3">
                <button type="button" wire:click="runAiAnalysis" wire:loading.attr="disabled" wire:target="runAiAnalysis" class="self-start rounded border px-3 py-1.5 text-sm">
                    <span wire:loading.remove wire:target="runAiAnalysis">AI-Analyse</span>
                    <span wire:loading wire:target="runAiAnalysis">Analysiere...</span>
                </button>

                @if ($aiAnalysisResult)
                    <div class="p-3 text-sm leading-relaxed bg-violet-50/50 border border-violet-200 rounded-lg whitespace-pre-wrap">
                        <div class="text-xs font-semibold text-violet-700 mb-2">AI-Analyse</div>
                        <div>{{ $aiAnalysisResult }}</div>
                    </div>
                @endif
            </div>
        @endif

        @if ($this->customerSearchEnabled())
            <div x-data="{ open: false }">
                <label class="block mb-1.5 text-sm font-medium">Kunde (optional)</label>
                @if ($customerDisplay)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-violet-50 text-violet-700 text-sm">
                        <span>{{ $customerDisplay }}</span>
                        <button type="button" wire:click="clearCustomer" class="ml-auto text-violet-400 hover:text-violet-700">×</button>
                    </div>
                @else
                    <div class="relative">
                        <input wire:model.live.debounce.300ms="customerSearch"
                               placeholder="Kunde suchen (Name, E-Mail, Nummer)..."
                               class="w-full rounded border px-3 py-1.5 text-sm"
                               @focus="open = true" @click.away="open = false" />
                        @if ($this->customerResults->isNotEmpty())
                            <div x-show="open" x-cloak class="absolute z-10 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach ($this->customerResults as $customer)
                                    <button type="button" wire:click="selectCustomer({{ $customer->id }})" @click="open = false"
                                            class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-violet-50 transition-colors">
                                        <div class="min-w-0">
                                            <span class="font-medium">{{ $customer->fullname ?? $customer->id }}</span>
                                            @if ($customer->number ?? null)
                                                <span class="text-zinc-500">#{{ $customer->number }}</span>
                                            @endif
                                            @if ($customer->user?->email ?? null)
                                                <span class="block text-xs text-zinc-500 truncate">{{ $customer->user->email }}</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block mb-1.5 text-sm font-medium">Kontaktperson</label>
                <input wire:model="contactName" class="w-full rounded border px-3 py-1.5 text-sm" required />
            </div>
            <div>
                <label class="block mb-1.5 text-sm font-medium">E-Mail</label>
                <input wire:model="contactEmail" type="email" class="w-full rounded border px-3 py-1.5 text-sm" required />
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block mb-1.5 text-sm font-medium">Typ</label>
                <select wire:model="typeId" class="w-full rounded border px-3 py-1.5 text-sm" required>
                    @foreach ($this->types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1.5 text-sm font-medium">Priorität</label>
                <select wire:model="priority" class="w-full rounded border px-3 py-1.5 text-sm">
                    @foreach (\Empire2\GazeTicketsystem\Enums\Priority::cases() as $p)
                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1.5 text-sm font-medium">Zuweisen an</label>
                <select wire:model="assignedTo" class="w-full rounded border px-3 py-1.5 text-sm">
                    <option value="">Nicht zugewiesen</option>
                    @foreach ($this->admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name ?? $admin->email ?? $admin->id }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block mb-1.5 text-sm font-medium">Anhänge</label>
            <input type="file" wire:model="attachments" multiple class="block w-full text-sm" />
            @error('attachments.*') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        @if ($sourceType)
            <div class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg bg-violet-50 text-violet-700">
                <span>Erstellt aus {{ $sourceType }} #{{ $sourceId }}</span>
            </div>
        @endif

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('gaze-ticketsystem.tickets.board') }}" wire:navigate class="rounded border px-4 py-1.5 text-sm">Abbrechen</a>
            <button type="submit" class="rounded bg-violet-600 text-white px-4 py-1.5 text-sm" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Ticket erstellen</span>
                <span wire:loading wire:target="save">Erstelle...</span>
            </button>
        </div>
    </form>
</div>
