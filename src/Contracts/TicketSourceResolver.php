<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Contracts;

use Empire2\GazeTicketsystem\Sources\TicketSourceData;

/**
 * Resolves an external "source" (such as a Ghostwriter support draft) into a
 * neutral DTO that the ticket package can use to:
 *
 *  - prefill the create form (title, body, contact, customer)
 *  - build the "view source" link displayed on the ticket detail page
 *
 * Implementations MUST be defensive: when the upstream record is missing or
 * the host integration is not installed, they MUST return null instead of
 * throwing.
 */
interface TicketSourceResolver
{
    public function resolve(string $sourceType, mixed $sourceId): ?TicketSourceData;
}
