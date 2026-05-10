<div class="px-6 py-4">
    <div class="mb-4">
        @if ($editingTitle)
            <div class="flex items-center gap-2">
                <input wire:model="editableTitle" wire:keydown.enter="saveTitle" wire:keydown.escape="cancelEditingTitle"
                       class="flex-1 rounded border px-3 py-1.5 text-xl font-semibold" autofocus />
                <button wire:click="saveTitle" class="rounded bg-violet-600 text-white px-3 py-1.5 text-sm">✓</button>
                <button wire:click="cancelEditingTitle" class="rounded border px-3 py-1.5 text-sm">×</button>
            </div>
        @else
            <h1 wire:click="startEditingTitle" class="text-xl font-semibold cursor-pointer hover:text-violet-700 transition-colors">
                {{ $ticket->title }}
            </h1>
        @endif
    </div>

    @include('gaze-ticketsystem::partials.detail-metadata-bar')

    @if ($ticket->sourceUrl())
        <div class="flex items-center gap-2 px-3 py-2 mt-4 text-sm rounded-lg bg-violet-50 text-violet-700">
            <a href="{{ $ticket->sourceUrl() }}" wire:navigate class="underline hover:no-underline">Quelle ansehen ({{ $ticket->source_type }})</a>
        </div>
    @endif

    <div class="p-4 mt-4 bg-white border rounded-lg">
        @if ($editingBody)
            <div class="flex flex-col gap-2">
                <textarea wire:model="editableBody" rows="6" wire:keydown.escape="cancelEditingBody" class="w-full rounded border px-3 py-1.5 text-sm" autofocus></textarea>
                <div class="flex gap-2">
                    <button wire:click="saveBody" class="rounded bg-violet-600 text-white px-3 py-1.5 text-sm">Speichern</button>
                    <button wire:click="cancelEditingBody" class="rounded border px-3 py-1.5 text-sm">Abbrechen</button>
                </div>
            </div>
        @else
            <div wire:click="startEditingBody" class="prose prose-sm max-w-none cursor-pointer hover:bg-zinc-50 rounded -m-1 p-1 transition-colors">
                {!! nl2br(e($ticket->body)) !!}
            </div>
        @endif

        @if ($ticket->getMedia('attachments')->isNotEmpty())
            <div class="pt-4 mt-4 border-t">
                <h4 class="mb-2 text-xs font-semibold text-zinc-500 uppercase">Anhänge</h4>
                <div class="flex flex-wrap gap-3">
                    @foreach ($ticket->getMedia('attachments') as $media)
                        @php $isImage = str_starts_with($media->mime_type, 'image/'); @endphp
                        <div class="relative group">
                            <a href="{{ $media->getUrl() }}" target="_blank" class="block no-underline">
                                @if ($isImage)
                                    <div class="w-36 rounded-lg border overflow-hidden bg-zinc-50">
                                        <img src="{{ $media->getUrl() }}" alt="{{ $media->file_name }}" class="w-full h-24 object-cover" loading="lazy" />
                                        <div class="px-2 py-1.5">
                                            <p class="text-xs text-zinc-500 truncate">{{ $media->file_name }}</p>
                                            <p class="text-xs text-zinc-400">{{ $media->human_readable_size }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg border bg-zinc-50">
                                        <div class="min-w-0">
                                            <p class="text-sm truncate">{{ $media->file_name }}</p>
                                            <p class="text-xs text-zinc-500">{{ $media->human_readable_size }}</p>
                                        </div>
                                    </div>
                                @endif
                            </a>
                            <button wire:click="deleteAttachment({{ $media->id }})" wire:confirm="Datei wirklich löschen?"
                                    class="absolute -top-1.5 -right-1.5 hidden group-hover:flex items-center justify-center size-5 rounded-full bg-red-500 text-white shadow-sm">×</button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="mt-4" x-data="{ showUpload: false }">
        <button @click="showUpload = !showUpload" type="button" class="text-sm text-zinc-500 hover:text-violet-700">📎 Datei hochladen</button>
        <div x-show="showUpload" x-cloak class="mt-2">
            <input type="file" wire:model="ticketAttachments" multiple class="block w-full text-sm" />
            <button wire:click="uploadAttachments" class="mt-2 rounded bg-violet-600 text-white px-3 py-1.5 text-sm">Hochladen</button>
        </div>
    </div>

    @if (config('gaze-ticketsystem.ai.enabled', true))
        <div class="mt-4" x-data="{ showPrompt: false }">
            <div class="flex items-center gap-2">
                <button wire:click="runAiAnalysis" wire:loading.attr="disabled" wire:target="runAiAnalysis" class="rounded border px-3 py-1.5 text-sm">
                    <span wire:loading.remove wire:target="runAiAnalysis">AI-Analyse</span>
                    <span wire:loading wire:target="runAiAnalysis">Analysiere...</span>
                </button>
                <button @click="showPrompt = !showPrompt" type="button" class="text-xs text-zinc-500 hover:text-violet-700 transition-colors">
                    <span x-text="showPrompt ? 'Ausblenden' : 'Kontext mitgeben'"></span>
                </button>
            </div>
            <div x-show="showPrompt" x-cloak class="mt-2">
                <textarea wire:model="aiAdditionalPrompt" rows="2" placeholder="Zusätzliche Hinweise oder Fragen an die AI..."
                          class="w-full rounded border px-3 py-1.5 text-sm"></textarea>
            </div>
        </div>
    @endif

    <div class="mt-6">
        @include('gaze-ticketsystem::partials.comment-thread')
    </div>

    <div class="pt-4 mt-4 border-t">
        <form wire:submit="addComment" class="flex flex-col gap-3">
            <textarea wire:model="commentBody" rows="3" placeholder="Kommentar schreiben..." class="w-full rounded border px-3 py-1.5 text-sm"></textarea>
            <div class="flex items-center gap-3">
                <input type="file" wire:model="commentAttachments" multiple class="text-sm" />
                <button type="submit" class="ml-auto rounded bg-violet-600 text-white px-3 py-1.5 text-sm" wire:loading.attr="disabled">
                    Kommentar senden
                </button>
            </div>
        </form>
    </div>
</div>
