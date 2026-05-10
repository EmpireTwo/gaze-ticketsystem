<div
    x-data="{ visible: {} }"
    @gaze-ticketsystem-toast.window="visible[$event.detail?.id ?? Date.now()] = true"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2"
>
    @foreach ($messages as $message)
        <div
            wire:key="toast-{{ $message['id'] }}"
            x-data="{ shown: true }"
            x-init="setTimeout(() => { shown = false; setTimeout(() => $wire.dismiss({{ $message['id'] }}), 300) }, 4000)"
            x-show="shown"
            x-transition
            class="flex items-center gap-3 rounded-lg px-4 py-3 shadow-lg ring-1 ring-black/5 text-sm
                @class([
                    'bg-emerald-50 text-emerald-900' => $message['level'] === 'success',
                    'bg-red-50 text-red-900' => $message['level'] === 'danger',
                    'bg-blue-50 text-blue-900' => $message['level'] === 'info',
                    'bg-zinc-50 text-zinc-900' => ! in_array($message['level'], ['success', 'danger', 'info'], true),
                ])"
        >
            <span>{{ $message['message'] }}</span>
            <button type="button" wire:click="dismiss({{ $message['id'] }})" class="ml-2 text-current opacity-60 hover:opacity-100">
                &times;
            </button>
        </div>
    @endforeach
</div>
