<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Livewire\Admin;

use Empire2\GazeTicketsystem\Models\TicketStatus;
use Empire2\GazeTicketsystem\Models\TicketType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tickets · Einstellungen')]
class TicketSettings extends Component
{
    public string $newStatusName = '';

    public string $newStatusColor = 'blue';

    public ?int $editingStatusId = null;

    public string $editingStatusName = '';

    public string $editingStatusColor = '';

    public bool $editingStatusIsResolved = false;

    public bool $editingStatusIsClosed = false;

    public string $newTypeName = '';

    public string $newTypeColor = 'blue';

    public string $newTypeIcon = 'document-text';

    public ?int $editingTypeId = null;

    public string $editingTypeName = '';

    public string $editingTypeColor = '';

    public string $editingTypeIcon = '';

    public function layout(): string
    {
        return (string) config('gaze-ticketsystem.layout', 'components.layouts.app');
    }

    public function addStatus(): void
    {
        $this->validate([
            'newStatusName' => ['required', 'string', 'max:100'],
            'newStatusColor' => ['required', 'string', 'max:50'],
        ]);

        $maxPosition = TicketStatus::query()->max('position') ?? 0;

        TicketStatus::query()->create([
            'name' => $this->newStatusName,
            'slug' => Str::slug($this->newStatusName),
            'color' => $this->newStatusColor,
            'position' => $maxPosition + 1,
        ]);

        $this->reset('newStatusName', 'newStatusColor');
        $this->dispatch('gaze-ticketsystem-toast', message: 'Status erstellt.', level: 'success');
    }

    public function editStatus(int $statusId): void
    {
        $status = TicketStatus::query()->findOrFail($statusId);
        $this->editingStatusId = $status->id;
        $this->editingStatusName = $status->name;
        $this->editingStatusColor = $status->color;
        $this->editingStatusIsResolved = $status->is_resolved;
        $this->editingStatusIsClosed = $status->is_closed;
    }

    public function saveStatus(): void
    {
        $this->validate([
            'editingStatusName' => ['required', 'string', 'max:100'],
            'editingStatusColor' => ['required', 'string', 'max:50'],
        ]);

        $status = TicketStatus::query()->findOrFail($this->editingStatusId);
        $status->update([
            'name' => $this->editingStatusName,
            'slug' => Str::slug($this->editingStatusName),
            'color' => $this->editingStatusColor,
            'is_resolved' => $this->editingStatusIsResolved,
            'is_closed' => $this->editingStatusIsClosed,
        ]);

        $this->editingStatusId = null;
        $this->dispatch('gaze-ticketsystem-toast', message: 'Status aktualisiert.', level: 'success');
    }

    public function deleteStatus(int $statusId): void
    {
        $status = TicketStatus::query()->findOrFail($statusId);

        if ($status->is_default) {
            $this->dispatch('gaze-ticketsystem-toast', message: 'Der Standard-Status kann nicht gelöscht werden.', level: 'danger');

            return;
        }

        if ($status->tickets()->exists()) {
            $this->dispatch('gaze-ticketsystem-toast', message: 'Status hat noch zugeordnete Tickets.', level: 'danger');

            return;
        }

        $status->delete();
        $this->dispatch('gaze-ticketsystem-toast', message: 'Status gelöscht.', level: 'success');
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorderStatuses(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            TicketStatus::query()->where('id', $id)->update(['position' => $position + 1]);
        }
    }

    public function addType(): void
    {
        $this->validate([
            'newTypeName' => ['required', 'string', 'max:100'],
            'newTypeColor' => ['required', 'string', 'max:50'],
            'newTypeIcon' => ['required', 'string', 'max:100'],
        ]);

        TicketType::query()->create([
            'name' => $this->newTypeName,
            'slug' => Str::slug($this->newTypeName),
            'color' => $this->newTypeColor,
            'icon' => $this->newTypeIcon,
        ]);

        $this->reset('newTypeName', 'newTypeColor', 'newTypeIcon');
        $this->dispatch('gaze-ticketsystem-toast', message: 'Typ erstellt.', level: 'success');
    }

    public function editType(int $typeId): void
    {
        $type = TicketType::query()->findOrFail($typeId);
        $this->editingTypeId = $type->id;
        $this->editingTypeName = $type->name;
        $this->editingTypeColor = $type->color;
        $this->editingTypeIcon = $type->icon ?? 'document-text';
    }

    public function saveType(): void
    {
        $this->validate([
            'editingTypeName' => ['required', 'string', 'max:100'],
            'editingTypeColor' => ['required', 'string', 'max:50'],
            'editingTypeIcon' => ['required', 'string', 'max:100'],
        ]);

        $type = TicketType::query()->findOrFail($this->editingTypeId);
        $type->update([
            'name' => $this->editingTypeName,
            'slug' => Str::slug($this->editingTypeName),
            'color' => $this->editingTypeColor,
            'icon' => $this->editingTypeIcon,
        ]);

        $this->editingTypeId = null;
        $this->dispatch('gaze-ticketsystem-toast', message: 'Typ aktualisiert.', level: 'success');
    }

    public function deleteType(int $typeId): void
    {
        $type = TicketType::query()->findOrFail($typeId);

        if ($type->tickets()->exists()) {
            $this->dispatch('gaze-ticketsystem-toast', message: 'Typ hat noch zugeordnete Tickets.', level: 'danger');

            return;
        }

        $type->delete();
        $this->dispatch('gaze-ticketsystem-toast', message: 'Typ gelöscht.', level: 'success');
    }

    public function render(): View
    {
        return view('gaze-ticketsystem::ticket-settings', [
            'statuses' => TicketStatus::query()->ordered()->get(),
            'types' => TicketType::all(),
        ])->layout($this->layout());
    }
}
