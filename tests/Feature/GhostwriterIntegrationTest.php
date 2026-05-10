<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;
use Empire2\GazeTicketsystem\Sources\GhostwriterSourceResolver;

beforeEach(function () {
    seedTicketDefaults();

    if (! class_exists('Empire2\\GazeGhostwriter\\Models\\SupportDraft')) {
        $this->markTestSkipped('Requires empire2/gaze-ghostwriter to be installed alongside this package.');
    }
});

test('default config maps support_draft to ghostwriter resolver', function () {
    $resolvers = config('gaze-ticketsystem.source_resolvers');

    expect($resolvers)->toHaveKey('support_draft')
        ->and($resolvers['support_draft'])->toBe(GhostwriterSourceResolver::class);
});

test('source resolver returns null for unknown source type when ghostwriter installed', function () {
    /** @var TicketSourceResolver $resolver */
    $resolver = app(TicketSourceResolver::class);

    expect($resolver->resolve('totally_unknown', 1))->toBeNull();
});

// Concrete prefill behaviour is exercised by the ghostwriter package's own
// test suite. The package-level contract here is: when ghostwriter is
// installed, resolving a real support_draft id returns a populated DTO; when
// it is not installed the default resolver returns null (covered by
// TicketModelTest::sourceUrl tests).
