<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Sources;

use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;

/**
 * Default no-op resolver. Used as the bottom of the resolver chain so the
 * package never throws when no host integration is configured.
 */
final class NullTicketSourceResolver implements TicketSourceResolver
{
    public function resolve(string $sourceType, mixed $sourceId): ?TicketSourceData
    {
        return null;
    }
}
