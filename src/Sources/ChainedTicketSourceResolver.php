<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Sources;

use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;
use Illuminate\Contracts\Container\Container;

/**
 * Dispatches a `(sourceType, sourceId)` pair to the resolver registered for
 * that type in `config('gaze-ticketsystem.source_resolvers')`. Falls back to
 * NullTicketSourceResolver when no resolver is registered or when the
 * registered resolver returns null.
 */
final class ChainedTicketSourceResolver implements TicketSourceResolver
{
    /**
     * @param  array<string, class-string<TicketSourceResolver>>  $map
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $map = [],
    ) {}

    public function resolve(string $sourceType, mixed $sourceId): ?TicketSourceData
    {
        $resolverClass = $this->map[$sourceType] ?? null;

        if ($resolverClass === null) {
            return null;
        }

        if (! is_subclass_of($resolverClass, TicketSourceResolver::class)) {
            return null;
        }

        /** @var TicketSourceResolver $resolver */
        $resolver = $this->container->make($resolverClass);

        return $resolver->resolve($sourceType, $sourceId);
    }
}
