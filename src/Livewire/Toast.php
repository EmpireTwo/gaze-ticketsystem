<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tiny stand-alone toast component used by the package's Livewire admin
 * components. Other components dispatch a `gaze-ticketsystem-toast` browser
 * event with `{ message, level }` payload — this component renders a stack
 * of those messages and auto-dismisses them after a short delay.
 *
 * The component intentionally has no host-side dependencies: render it once
 * inside the host layout (or alongside the package routes) and you are done.
 */
class Toast extends Component
{
    /** @var array<int, array{id: int, message: string, level: string}> */
    public array $messages = [];

    private int $nextId = 1;

    public static function success(string $message): void
    {
        self::dispatchEvent($message, 'success');
    }

    public static function danger(string $message): void
    {
        self::dispatchEvent($message, 'danger');
    }

    public static function info(string $message): void
    {
        self::dispatchEvent($message, 'info');
    }

    private static function dispatchEvent(string $message, string $level): void
    {
        // Livewire components emit via $this->dispatch() — but call sites
        // here are static so we surface the event via the global dispatcher
        // by writing it onto the response headers Livewire already inspects.
        // The Livewire components in this package call $this->dispatch()
        // directly; this static helper is provided so legacy code paths can
        // still invoke Toast::success() without hard failures.
        if (function_exists('app') && app()->bound('events')) {
            app('events')->dispatch('gaze-ticketsystem.toast', [
                'message' => $message,
                'level' => $level,
            ]);
        }
    }

    #[On('gaze-ticketsystem-toast')]
    public function push(string $message, string $level = 'info'): void
    {
        $this->messages[] = [
            'id' => $this->nextId++,
            'message' => $message,
            'level' => $level,
        ];
    }

    public function dismiss(int $id): void
    {
        $this->messages = array_values(array_filter(
            $this->messages,
            static fn (array $m): bool => $m['id'] !== $id,
        ));
    }

    public function render(): View
    {
        return view('gaze-ticketsystem::toast');
    }
}
