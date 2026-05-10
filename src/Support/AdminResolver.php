<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Resolves the list of "admins" eligible for ticket assignment.
 *
 * Lookup order:
 *  1. `config('gaze-ticketsystem.admin_resolver')` — host-supplied callable.
 *  2. `User::admins()` query scope on the configured user_model (e.g. via the
 *     `IsTicketAdmin` trait).
 *  3. All users from the configured user_model, ordered by name when present.
 */
final class AdminResolver
{
    /**
     * @return Collection<int, Model>
     */
    public static function resolve(): Collection
    {
        $resolver = config('gaze-ticketsystem.admin_resolver');

        if (is_callable($resolver)) {
            $result = $resolver();

            if ($result instanceof Collection) {
                return $result;
            }
        }

        $userModel = self::userModel();

        if ($userModel === null) {
            /** @var Collection<int, Model> $empty */
            $empty = new Collection;

            return $empty;
        }

        /** @var Builder<Model> $query */
        $query = $userModel::query();

        if (method_exists($userModel, 'scopeAdmins')) {
            /** @var Builder<Model> $query */
            $query = $query->admins();
        }

        if (self::userModelHasColumn($userModel, 'name')) {
            $query->orderBy('name');
        }

        /** @var Collection<int, Model> $collection */
        $collection = $query->get();

        return $collection;
    }

    /**
     * @return class-string<Model>|null
     */
    private static function userModel(): ?string
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

    /**
     * @param  class-string<Model>  $model
     */
    private static function userModelHasColumn(string $model, string $column): bool
    {
        try {
            /** @var Model $instance */
            $instance = new $model;
            $connection = $instance->getConnection();
            $schema = $connection->getSchemaBuilder();

            return $schema->hasColumn($instance->getTable(), $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
