<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Helper around the host-configured customer model. Returns empty results
 * when the host hasn't configured a customer_model so the rest of the
 * package never has to defensively guard against a missing class.
 */
final class CustomerLookup
{
    public static function isEnabled(): bool
    {
        return self::modelClass() !== null;
    }

    /**
     * @return Collection<int, Model>
     */
    public static function search(string $term): Collection
    {
        $model = self::modelClass();

        if ($model === null || mb_strlen($term) < 2) {
            /** @var Collection<int, Model> $empty */
            $empty = new Collection;

            return $empty;
        }

        try {
            /** @var Collection<int, Model> $results */
            $results = $model::query()
                ->when(method_exists($model, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())
                ->where(function ($q) use ($term): void {
                    $q->where('firstname', 'like', '%'.$term.'%')
                        ->orWhere('lastname', 'like', '%'.$term.'%')
                        ->orWhere('number', 'like', '%'.$term.'%');
                })
                ->limit(10)
                ->get();

            return $results;
        } catch (\Throwable) {
            /** @var Collection<int, Model> $empty */
            $empty = new Collection;

            return $empty;
        }
    }

    public static function find(int|string $id): ?Model
    {
        $model = self::modelClass();

        if ($model === null) {
            return null;
        }

        try {
            $query = $model::query();

            if (method_exists($model, 'bootSoftDeletes')) {
                $query = $query->withTrashed();
            }

            /** @var Model|null $found */
            $found = $query->find($id);

            return $found;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return class-string<Model>|null
     */
    private static function modelClass(): ?string
    {
        $class = config('gaze-ticketsystem.customer_model');

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
