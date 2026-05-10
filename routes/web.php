<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Livewire\Admin\TicketBoard;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketCreate;
use Empire2\GazeTicketsystem\Livewire\Admin\TicketSettings;
use Illuminate\Support\Facades\Route;

Route::get('/settings', TicketSettings::class)->name('tickets.settings');
Route::get('/create', TicketCreate::class)->name('tickets.create');
Route::get('/{ticket}', TicketBoard::class)->name('tickets.show');
Route::get('/', TicketBoard::class)->name('tickets.board');
