<div class="flex flex-col gap-4">
    <h3 class="text-sm font-semibold text-zinc-500 uppercase">Kommentare ({{ $ticket->comments->count() }})</h3>

    @foreach ($ticket->comments as $comment)
        <div class="p-4 rounded-lg border {{ $comment->is_ai_response ? 'bg-violet-50/50 border-violet-200' : 'bg-white' }}">
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="flex items-center gap-2">
                    @if ($comment->is_ai_response)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-700">AI</span>
                    @endif
                    <span class="text-sm font-medium">
                        @if ($comment->is_ai_response)
                            {{ str_starts_with($comment->body, '📋') ? 'AI-Analyse' : 'AI' }}
                        @else
                            {{ $comment->author?->name ?? $comment->author?->email ?? 'Unbekannt' }}
                        @endif
                    </span>
                    <span class="text-xs text-zinc-500">{{ $comment->created_at->diffForHumans() }}</span>
                    @if ($comment->is_internal)
                        <span class="px-1.5 py-0.5 text-xs font-medium text-amber-700 bg-amber-50 rounded">Intern</span>
                    @endif
                </div>
                <div class="flex items-center gap-1">
                    @unless ($comment->is_ai_response)
                        <button wire:click="aiReplyToComment({{ $comment->id }})"
                                wire:loading.attr="disabled"
                                title="AI antworten lassen"
                                class="text-zinc-500 hover:text-violet-600 transition-colors text-sm">⚡</button>
                    @endunless
                    <button wire:click="deleteComment({{ $comment->id }})" wire:confirm="Kommentar wirklich löschen?"
                            class="text-zinc-500 hover:text-red-500 transition-colors text-sm">🗑</button>
                </div>
            </div>

            <div class="text-sm leading-relaxed">
                {!! nl2br(e($comment->body)) !!}
            </div>

            @if ($comment->getMedia('attachments')->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach ($comment->getMedia('attachments') as $media)
                        <a href="{{ $media->getUrl() }}" target="_blank" class="inline-flex items-center gap-1.5 px-2 py-1 text-xs bg-zinc-50 rounded border text-zinc-600 hover:text-violet-700">
                            📎 {{ $media->file_name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    @if ($ticket->comments->isEmpty())
        <p class="text-sm text-zinc-500">Noch keine Kommentare.</p>
    @endif
</div>
