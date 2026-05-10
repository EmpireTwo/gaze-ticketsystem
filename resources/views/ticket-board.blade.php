<div class="flex flex-col" style="height: calc(100vh - 120px);"
     x-data="{
         closing: false,
         handleEscape(e) {
             if (this.isInputFocused()) return;
             e.preventDefault();
             this.animateClose();
         },
         animateClose() {
             if (this.closing) return;
             this.closing = true;
             $dispatch('detail-closing');
             setTimeout(() => { $wire.closeDetail(); this.closing = false; }, 400);
         },
         handleArrowUp(e) {
             if (this.isInputFocused()) return;
             if (!$wire.selectedTicketId) return;
             e.preventDefault();
             $wire.navigateTicket('previous');
         },
         handleArrowDown(e) {
             if (this.isInputFocused()) return;
             if (!$wire.selectedTicketId) return;
             e.preventDefault();
             $wire.navigateTicket('next');
         },
         handleKeydown(e) {
             if (this.isInputFocused()) return;
             if (e.key === 'n' || e.key === 'N') { e.preventDefault(); $dispatch('open-create-modal'); }
             if (e.key === 's' || e.key === 'S') { e.preventDefault(); document.querySelector('[wire\\:model\\.live\\.debounce\\.300ms=search] input, [wire\\:model\\.live\\.debounce\\.300ms=search]')?.focus(); }
         },
         isInputFocused() {
             const el = document.activeElement;
             if (!el) return false;
             const tag = el.tagName.toLowerCase();
             return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
         }
     }"
     @keydown.escape.window="handleEscape"
     @keydown.arrow-up.window="handleArrowUp"
     @keydown.arrow-down.window="handleArrowDown"
     @keydown.window="handleKeydown">

    {{-- Toolbar --}}
    <div class="mb-4">
        @include('gaze-ticketsystem::partials.toolbar')
    </div>

    {{-- Content area --}}
    @if ($selectedTicketId && $this->selectedTicket)
        {{-- SPLIT VIEW --}}
        <div wire:key="split-view" class="flex flex-1 min-h-0 gap-0 border rounded-lg overflow-hidden"
             x-data="{ shown: false }"
             x-init="$nextTick(() => shown = true)">
            <div class="hidden lg:flex lg:flex-col lg:w-72 border-r bg-white overflow-y-auto shrink-0">
                @include('gaze-ticketsystem::partials.split-left-column')
            </div>
            <div class="flex flex-col flex-1 min-h-0 bg-white transition-all ease-out overflow-x-hidden"
                 :class="shown ? 'opacity-100 translate-x-0 duration-500' : 'opacity-0 translate-x-8'">
                @include('gaze-ticketsystem::partials.detail-panel')
            </div>
        </div>
    @else
        {{-- FULL VIEW --}}
        <div wire:key="full-view" class="flex flex-col flex-1 min-h-0">
            @if ($viewMode === 'kanban')
                @include('gaze-ticketsystem::partials.kanban-columns')
            @else
                @include('gaze-ticketsystem::partials.list-table')
            @endif
        </div>
    @endif

    {{-- Create ticket modal --}}
    <div x-data="{ open: false }" x-on:open-create-modal.window="open = true" x-on:ticket-created.window="open = false">
        <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" @click.self="open = false">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-5">
                <livewire:gaze-ticketsystem::ticket-create-modal />
            </div>
        </div>
    </div>

</div>
