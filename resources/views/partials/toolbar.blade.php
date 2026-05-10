<div>
    <div class="flex items-center gap-3">
        <div class="inline-flex rounded-lg border overflow-hidden">
            <button wire:click="$set('viewMode', 'kanban')" type="button"
                    class="flex items-center justify-center size-8 transition-colors {{ $viewMode === 'kanban' ? 'bg-violet-600 text-white' : 'bg-white text-zinc-500 hover:text-zinc-900' }}">
                ▥
            </button>
            <button wire:click="$set('viewMode', 'list')" type="button"
                    class="flex items-center justify-center size-8 transition-colors border-l {{ $viewMode === 'list' ? 'bg-violet-600 text-white' : 'bg-white text-zinc-500 hover:text-zinc-900' }}">
                ☰
            </button>
        </div>

        <input wire:model.live.debounce.300ms="search" placeholder="Suche..." class="w-56 rounded border px-3 py-1.5 text-sm" />

        <div x-data="{ open: false }" class="relative">
            <button type="button" x-on:click="open = !open" class="rounded border px-3 py-1.5 text-sm">
                Filter
                @if ($this->hasActiveFilters())
                    <span class="ml-1 inline-flex items-center justify-center size-5 rounded-full bg-violet-600 text-white text-xs font-semibold">
                        {{ count($this->activeFilterChips()) }}
                    </span>
                @endif
            </button>
            <div x-show="open" x-on:click.away="open = false" x-cloak x-transition
                 class="absolute z-50 mt-2 bg-white border rounded-lg shadow-lg">
                @include('gaze-ticketsystem::partials.filter-popover')
            </div>
        </div>

        <div class="flex-1"></div>

        <a href="{{ route('gaze-ticketsystem.tickets.create') }}" wire:navigate class="rounded bg-violet-600 text-white px-3 py-1.5 text-sm">
            Neues Ticket
        </a>
        <button type="button" x-on:click="$dispatch('open-create-modal')" class="rounded border px-3 py-1.5 text-sm">
            + Quick
        </button>
    </div>

    @if ($this->hasActiveFilters())
        <div class="flex flex-wrap items-center gap-2 mt-3">
            @foreach ($this->activeFilterChips() as $chip)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 text-xs font-medium">
                    {{ $chip['label'] }}
                    <button wire:click="clearFilter('{{ $chip['key'] }}')" type="button" class="text-violet-400 hover:text-violet-700 transition-colors">×</button>
                </span>
            @endforeach
        </div>
    @endif
</div>
