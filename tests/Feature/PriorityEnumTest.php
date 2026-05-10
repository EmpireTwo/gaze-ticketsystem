<?php

declare(strict_types=1);

use Empire2\GazeTicketsystem\Enums\Priority;

test('priority enum has all expected cases', function () {
    expect(Priority::cases())->toHaveCount(4)
        ->and(Priority::LOW->value)->toBe('low')
        ->and(Priority::MEDIUM->value)->toBe('medium')
        ->and(Priority::HIGH->value)->toBe('high')
        ->and(Priority::URGENT->value)->toBe('urgent');
});

test('priority enum returns labels', function () {
    expect(Priority::LOW->label())->toBe('Niedrig')
        ->and(Priority::MEDIUM->label())->toBe('Mittel')
        ->and(Priority::HIGH->label())->toBe('Hoch')
        ->and(Priority::URGENT->label())->toBe('Dringend');
});

test('priority enum returns badge classes', function () {
    foreach (Priority::cases() as $priority) {
        expect($priority->badgeClasses())->toBeString()->not->toBeEmpty();
    }
});

test('priority enum returns colors', function () {
    foreach (Priority::cases() as $priority) {
        expect($priority->color())->toBeString()->not->toBeEmpty();
    }
});

test('priority enum returns icons', function () {
    foreach (Priority::cases() as $priority) {
        expect($priority->icon())->toBeString()->not->toBeEmpty();
    }
});
