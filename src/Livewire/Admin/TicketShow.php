<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Livewire\Admin;

use Empire2\GazeTicketsystem\Enums\Priority;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketComment;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;
use Empire2\GazeTicketsystem\Notifications\TicketAssignedNotification;
use Empire2\GazeTicketsystem\Notifications\TicketCommentAddedNotification;
use Empire2\GazeTicketsystem\Services\TicketAiAnalysisService;
use Empire2\GazeTicketsystem\Support\AdminResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class TicketShow extends Component
{
    use WithFileUploads;

    #[Locked]
    public Ticket $ticket;

    public string $commentBody = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $commentAttachments = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $ticketAttachments = [];

    public ?string $followUpDate = null;

    public bool $aiAnalysisRunning = false;

    public string $aiAdditionalPrompt = '';

    public bool $editingTitle = false;

    public string $editableTitle = '';

    public bool $editingBody = false;

    public string $editableBody = '';

    public string $statusId = '';

    public string $typeId = '';

    public string $priorityValue = '';

    public string $assigneeId = '';

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket->loadMissing(['status', 'type', 'assignee', 'creator', 'customer', 'comments.author', 'media']);
        $this->followUpDate = $ticket->follow_up_at?->format('Y-m-d');
        $this->editableTitle = $ticket->title;
        $this->editableBody = $ticket->body;
        $this->statusId = (string) $ticket->status_id;
        $this->typeId = (string) $ticket->type_id;
        $this->priorityValue = $ticket->priority->value;
        $this->assigneeId = (string) ($ticket->assigned_to ?? '');
    }

    public function updatedStatusId(string $value): void
    {
        $this->updateStatus((int) $value);
    }

    public function updatedTypeId(string $value): void
    {
        $this->updateType((int) $value);
    }

    public function updatedPriorityValue(string $value): void
    {
        $this->updatePriority($value);
    }

    public function updatedAssigneeId(string $value): void
    {
        $this->assignTo($value !== '' ? (int) $value : null);
    }

    public function startEditingTitle(): void
    {
        $this->editableTitle = $this->ticket->title;
        $this->editingTitle = true;
    }

    public function saveTitle(): void
    {
        $this->validate(['editableTitle' => ['required', 'string', 'max:255']]);
        $this->ticket->update(['title' => $this->editableTitle]);
        $this->editingTitle = false;
        $this->dispatch('gaze-ticketsystem-toast', message: 'Titel aktualisiert.', level: 'success');
    }

    public function cancelEditingTitle(): void
    {
        $this->editableTitle = $this->ticket->title;
        $this->editingTitle = false;
    }

    public function startEditingBody(): void
    {
        $this->editableBody = $this->ticket->body;
        $this->editingBody = true;
    }

    public function saveBody(): void
    {
        $this->validate(['editableBody' => ['required', 'string']]);
        $this->ticket->update(['body' => $this->editableBody]);
        $this->editingBody = false;
        $this->dispatch('gaze-ticketsystem-toast', message: 'Beschreibung aktualisiert.', level: 'success');
    }

    public function cancelEditingBody(): void
    {
        $this->editableBody = $this->ticket->body;
        $this->editingBody = false;
    }

    public function addComment(): void
    {
        $this->validate([
            'commentBody' => ['required', 'string'],
            'commentAttachments.*' => ['file', 'max:20480'],
        ]);

        $comment = $this->ticket->comments()->create([
            'user_id' => Auth::id(),
            'body' => $this->commentBody,
        ]);

        foreach ($this->commentAttachments as $attachment) {
            $comment->addMedia($attachment->getRealPath())
                ->usingFileName($attachment->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        $this->reset('commentBody', 'commentAttachments');
        $this->ticket->loadMissing('comments.author');

        $this->notifyCommentAdded();

        $this->dispatch('gaze-ticketsystem-toast', message: 'Kommentar hinzugefügt.', level: 'success');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = TicketComment::query()->findOrFail($commentId);

        if ($comment->ticket_id !== $this->ticket->id) {
            return;
        }

        $comment->delete();
        $this->ticket->load('comments.author');

        $this->dispatch('gaze-ticketsystem-toast', message: 'Kommentar gelöscht.', level: 'success');
    }

    public function updateStatus(int $statusId): void
    {
        $status = TicketStatus::query()->findOrFail($statusId);
        $this->ticket->status_id = $status->id;

        if ($status->is_resolved && ! $this->ticket->resolved_at) {
            $this->ticket->resolved_at = Carbon::now();
        } elseif (! $status->is_resolved) {
            $this->ticket->resolved_at = null;
        }

        if ($status->is_closed && ! $this->ticket->closed_at) {
            $this->ticket->closed_at = Carbon::now();
        } elseif (! $status->is_closed) {
            $this->ticket->closed_at = null;
        }

        $this->ticket->save();
        $this->ticket->load('status');

        $this->dispatch('gaze-ticketsystem-toast', message: 'Status aktualisiert.', level: 'success');
    }

    public function updatePriority(string $priority): void
    {
        $this->ticket->update(['priority' => $priority]);
        $this->dispatch('gaze-ticketsystem-toast', message: 'Priorität aktualisiert.', level: 'success');
    }

    public function updateType(int $typeId): void
    {
        $this->ticket->update(['type_id' => $typeId]);
        $this->ticket->load('type');
        $this->dispatch('gaze-ticketsystem-toast', message: 'Typ aktualisiert.', level: 'success');
    }

    public function assignTo(?int $userId): void
    {
        $previousAssignee = $this->ticket->assigned_to;
        $this->ticket->update(['assigned_to' => $userId ?: null]);
        $this->ticket->load('assignee');

        if ($userId && $userId !== Auth::id() && $userId !== $previousAssignee) {
            $userModel = $this->resolveUserModel();

            if ($userModel !== null) {
                /** @var Model|null $assignee */
                $assignee = $userModel::query()->find($userId);
                if ($assignee !== null && method_exists($assignee, 'notify')) {
                    $assignee->notify(new TicketAssignedNotification($this->ticket));
                }
            }
        }

        $this->dispatch(
            'gaze-ticketsystem-toast',
            message: $userId ? 'Ticket zugewiesen.' : 'Zuweisung entfernt.',
            level: 'success'
        );
    }

    public function setFollowUp(): void
    {
        $this->validate(['followUpDate' => ['nullable', 'date']]);

        $this->ticket->update([
            'follow_up_at' => $this->followUpDate ?: null,
            'follow_up_notified_at' => null,
        ]);

        $this->dispatch(
            'gaze-ticketsystem-toast',
            message: $this->followUpDate ? 'Wiedervorlage gesetzt.' : 'Wiedervorlage entfernt.',
            level: 'success'
        );
    }

    public function uploadAttachments(): void
    {
        $this->validate(['ticketAttachments.*' => ['file', 'max:20480']]);

        foreach ($this->ticketAttachments as $attachment) {
            $this->ticket->addMedia($attachment->getRealPath())
                ->usingFileName($attachment->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        $this->reset('ticketAttachments');
        $this->ticket->load('media');

        $this->dispatch('gaze-ticketsystem-toast', message: 'Dateien hochgeladen.', level: 'success');
    }

    public function deleteAttachment(int $mediaId): void
    {
        $media = $this->ticket->media->firstWhere('id', $mediaId);

        if ($media) {
            $media->delete();
            $this->ticket->load('media');
            $this->dispatch('gaze-ticketsystem-toast', message: 'Datei gelöscht.', level: 'success');
        }
    }

    public function runAiAnalysis(): void
    {
        $this->aiAnalysisRunning = true;

        $service = app(TicketAiAnalysisService::class);
        $comment = $service->analyze($this->ticket, $this->aiAdditionalPrompt);

        if ($comment) {
            $this->ticket->load('comments.author');
            $this->aiAdditionalPrompt = '';
            $this->dispatch('gaze-ticketsystem-toast', message: 'AI-Analyse abgeschlossen.', level: 'success');
        } else {
            $this->dispatch('gaze-ticketsystem-toast', message: 'AI-Analyse fehlgeschlagen.', level: 'danger');
        }

        $this->aiAnalysisRunning = false;
    }

    public function aiReplyToComment(int $commentId): void
    {
        $comment = TicketComment::query()->findOrFail($commentId);

        if ($comment->ticket_id !== $this->ticket->id) {
            return;
        }

        $service = app(TicketAiAnalysisService::class);
        $result = $service->replyToComment($this->ticket, $comment);

        if ($result) {
            $this->ticket->load('comments.author');
            $this->dispatch('gaze-ticketsystem-toast', message: 'AI-Antwort erstellt.', level: 'success');
        } else {
            $this->dispatch('gaze-ticketsystem-toast', message: 'AI-Antwort fehlgeschlagen.', level: 'danger');
        }
    }

    /**
     * @return EloquentCollection<int, TicketStatus>
     */
    #[Computed]
    public function statuses(): EloquentCollection
    {
        return TicketStatus::query()->ordered()->get();
    }

    /**
     * @return EloquentCollection<int, TicketType>
     */
    #[Computed]
    public function types(): EloquentCollection
    {
        return TicketType::all();
    }

    /**
     * @return Collection<int, Model>
     */
    #[Computed]
    public function admins(): Collection
    {
        return AdminResolver::resolve();
    }

    /**
     * @return list<Priority>
     */
    #[Computed]
    public function priorities(): array
    {
        return Priority::cases();
    }

    public function render(): View
    {
        return view('gaze-ticketsystem::ticket-show');
    }

    private function notifyCommentAdded(): void
    {
        $currentUser = Auth::user();

        if ($currentUser === null) {
            return;
        }

        $notification = new TicketCommentAddedNotification($this->ticket, $currentUser);

        $currentUserId = Auth::id();

        $recipientIds = collect([
            $this->ticket->assigned_to,
            $this->ticket->created_by,
        ])
            ->filter()
            ->unique()
            ->reject(fn (int $id): bool => $id === $currentUserId);

        $userModel = $this->resolveUserModel();

        if ($userModel === null || $recipientIds->isEmpty()) {
            return;
        }

        $userModel::query()
            ->whereIn('id', $recipientIds->all())
            ->get()
            ->each(function (Model $user) use ($notification): void {
                if (method_exists($user, 'notify')) {
                    $user->notify($notification);
                }
            });
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveUserModel(): ?string
    {
        $class = config('gaze-ticketsystem.user_model');

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return null;
        }

        if (! is_subclass_of($class, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $class */
        return $class;
    }
}
