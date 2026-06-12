<?php

namespace App;

enum FixtureStatus: string
{
    case Upcoming = 'upcoming';
    case Live = 'live';
    case Finished = 'finished';
    case Locked = 'locked';
    case Postponed = 'postponed';

    public function label(): string
    {
        return match($this) {
            self::Upcoming => 'Upcoming',
            self::Live => 'Live',
            self::Finished => 'Finished',
            self::Locked => 'Locked',
            self::Postponed => 'Postponed',
        };
    }
}
