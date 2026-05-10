<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Livewire\Admin;

use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;
use Empire2\GazeTicketsystem\Enums\Priority;
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;
use Empire2\GazeTicketsystem\Services\TicketAiAnalysisService;
use Empire2\GazeTicketsystem\Support\AdminResolver;
use Empire2\GazeTicketsystem\Support\CustomerLookup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class TicketCreateModal extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $body = '';

    public string $contactName = '';

    public string $contactEmail = '';

    public int|string|null $customerId = null;

    public string $customerSearch = '';

    public ?string $customerDisplay = null;

    public ?int $assignedTo = null;

    public string $typeId = '';

    public string $priority = 'medium';

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public ?string $sourceType = null;

    public int|string|null $sourceId = null;

    public string $sourceContext = '';

    public string $aiAnalysisResult = '';

    public bool $aiAnalysisRunning = false;

    public function mount(?string $sourceType = null, mixed $sourceId = null): void
    {
        $this->typeId = (string) (TicketType::query()->value('id') ?? '');

        if (is_string($sourceType) && $sourceType !== '' && $sourceId !== null) {
            $this->prefillFromSource($sourceType, $sourceId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'contactName' => ['required', 'string', 'max:255'],
            'contactEmail' => ['required', 'email', 'max:255'],
            'typeId' => ['required', 'exists:ticket_types,id'],
            'priority' => ['required', Rule::in(array_column(Priority::cases(), 'value'))],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $defaultStatus = TicketStatus::query()->default()->firstOrFail();

        $ticket = Ticket::query()->create([
            'title' => $this->title,
            'body' => $this->body,
            'contact_name' => $this->contactName,
            'contact_email' => $this->contactEmail,
            'customer_id' => $this->customerId,
            'assigned_to' => $this->assignedTo ?: null,
            'created_by' => Auth::id(),
            'status_id' => $defaultStatus->id,
            'type_id' => $this->typeId,
            'priority' => $this->priority,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
        ]);

        foreach ($this->attachments as $attachment) {
            $ticket->addMedia($attachment->getRealPath())
                ->usingFileName($attachment->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        if ($this->sourceContext !== '') {
            $ticket->comments()->create([
                'user_id' => Auth::id(),
                'body' => "Original-Nachricht:\n\n{$this->sourceContext}",
                'is_internal' => true,
            ]);
        }

        if ($this->aiAnalysisResult !== '') {
            $ticket->comments()->create([
                'user_id' => Auth::id(),
                'body' => $this->aiAnalysisResult,
                'is_ai_response' => true,
            ]);
        }

        $this->dispatch('gaze-ticketsystem-toast', message: "Ticket {$ticket->ticket_number} erstellt.", level: 'success');
        $this->dispatch('ticket-created', ticketId: $ticket->id, ticketNumber: $ticket->ticket_number);
    }

    /**
     * @return Collection<int, Model>
     */
    #[Computed]
    public function customerResults(): Collection
    {
        return CustomerLookup::search($this->customerSearch);
    }

    public function customerSearchEnabled(): bool
    {
        return CustomerLookup::isEnabled();
    }

    public function runAiAnalysis(): void
    {
        $this->aiAnalysisRunning = true;

        $imagePaths = collect($this->attachments)
            ->filter(fn ($file): bool => str_starts_with((string) $file->getMimeType(), 'image/'))
            ->map(fn ($file): string => (string) $file->getRealPath())
            ->values()
            ->all();

        $service = app(TicketAiAnalysisService::class);
        $result = $service->analyzeRaw(
            title: $this->title,
            body: $this->body,
            contactName: $this->contactName,
            contactEmail: $this->contactEmail,
            sourceContext: $this->sourceContext,
            imagePaths: $imagePaths,
        );

        if ($result) {
            $this->aiAnalysisResult = $result['text'];

            $structured = $result['structured'];
            if (isset($structured['suggested_priority'])) {
                $mapped = match (strtolower((string) $structured['suggested_priority'])) {
                    'low', 'niedrig' => 'low',
                    'medium', 'mittel' => 'medium',
                    'high', 'hoch' => 'high',
                    'urgent', 'dringend' => 'urgent',
                    default => null,
                };
                if ($mapped) {
                    $this->priority = $mapped;
                }
            }

            $this->dispatch('gaze-ticketsystem-toast', message: 'AI-Analyse abgeschlossen.', level: 'success');
        } else {
            $this->dispatch('gaze-ticketsystem-toast', message: 'AI-Analyse fehlgeschlagen.', level: 'danger');
        }

        $this->aiAnalysisRunning = false;
    }

    public function selectCustomer(int|string $customerId): void
    {
        $customer = CustomerLookup::find($customerId);

        if (! $customer) {
            return;
        }

        $this->customerId = $customer->id;
        $this->customerDisplay = $this->formatCustomerDisplay($customer);

        $name = $customer->fullname ?? null;
        if (is_string($name) && $name !== '') {
            $this->contactName = $name;
        }

        $email = $customer->user->email ?? null;
        if (is_string($email) && $email !== '') {
            $this->contactEmail = $email;
        }

        $this->customerSearch = '';
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerDisplay = null;
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

    public function render(): View
    {
        return view('gaze-ticketsystem::partials.ticket-create-modal');
    }

    private function prefillFromSource(string $sourceType, mixed $sourceId): void
    {
        $resolver = app(TicketSourceResolver::class);
        $data = $resolver->resolve($sourceType, $sourceId);

        if ($data === null) {
            return;
        }

        if (is_string($data->title) && $data->title !== '') {
            $this->title = $data->title;
        }

        if (is_string($data->contactName) && $data->contactName !== '') {
            $this->contactName = $data->contactName;
        }

        if (is_string($data->contactEmail) && $data->contactEmail !== '') {
            $this->contactEmail = $data->contactEmail;
        }

        if ($data->customerId !== null) {
            $this->customerId = $data->customerId;
            $customer = CustomerLookup::find($data->customerId);
            if ($customer !== null) {
                $this->customerDisplay = $this->formatCustomerDisplay($customer);
            }
        }

        if (is_string($data->sourceContext)) {
            $this->sourceContext = $data->sourceContext;
        }

        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
    }

    private function formatCustomerDisplay(Model $customer): string
    {
        $name = $customer->fullname ?? null;
        $email = $customer->user->email ?? null;

        if (is_string($name) && is_string($email)) {
            return "{$name} ({$email})";
        }

        return is_string($name) ? $name : (string) ($customer->id ?? '');
    }
}
