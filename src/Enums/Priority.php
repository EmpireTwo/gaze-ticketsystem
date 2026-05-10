<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Enums;

enum Priority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Niedrig',
            self::MEDIUM => 'Mittel',
            self::HIGH => 'Hoch',
            self::URGENT => 'Dringend',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'zinc',
            self::MEDIUM => 'blue',
            self::HIGH => 'amber',
            self::URGENT => 'red',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::LOW => 'bg-zinc-100 text-zinc-600',
            self::MEDIUM => 'bg-blue-50 text-blue-700',
            self::HIGH => 'bg-amber-50 text-amber-700',
            self::URGENT => 'bg-red-50 text-red-700',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LOW => 'arrow-down',
            self::MEDIUM => 'minus',
            self::HIGH => 'arrow-up',
            self::URGENT => 'exclamation-triangle',
        };
    }
}
