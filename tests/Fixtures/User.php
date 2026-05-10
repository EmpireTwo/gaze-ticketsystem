<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Tests\Fixtures;

use Empire2\GazeTicketsystem\Concerns\IsTicketAdmin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Test-only User fixture that mirrors the surface area the package expects
 * from the host User model: Authenticatable + Notifiable + an `admins` query
 * scope (via IsTicketAdmin trait).
 */
class User extends Authenticatable
{
    use HasFactory;
    use IsTicketAdmin;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = true;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
