<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Sources;

/**
 * Data Transfer Object describing a ticket source (e.g. an inbound mail
 * draft) returned by a TicketSourceResolver. All fields are optional —
 * resolvers should fill in whatever they can from the upstream record.
 */
final class TicketSourceData
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly ?string $contactName = null,
        public readonly ?string $contactEmail = null,
        public readonly int|string|null $customerId = null,
        public readonly ?string $url = null,
        public readonly ?string $sourceContext = null,
    ) {}
}
