<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Drop-in trait the host User model can `use` to expose an `admins()` query
 * scope used by the package when no custom admin resolver is configured.
 *
 * Override `scopeAdmins()` in the host model to apply your own role logic
 * (Spatie roles, a boolean `is_admin` column, gates, …). The default
 * implementation returns all users — adapt it to taste.
 *
 * Example with `spatie/laravel-permission`:
 *
 * ```php
 * public function scopeAdmins(Builder $query): Builder
 * {
 *     return $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']));
 * }
 * ```
 *
 * @method static Builder admins()
 */
trait IsTicketAdmin
{
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query;
    }
}
