<?php

namespace App;

enum FixtureRound: string
{
    case GroupStage = 'group_stage';
    case RoundOf16 = 'round_of_16';
    case QuarterFinal = 'quarter_final';
    case SemiFinal = 'semi_final';
    case ThirdPlace = 'third_place';
    case Final = 'final';

    public function label(): string
    {
        return match($this) {
            self::GroupStage => 'Group Stage',
            self::RoundOf16 => 'Round of 16',
            self::QuarterFinal => 'Quarter-Final',
            self::SemiFinal => 'Semi-Final',
            self::ThirdPlace => 'Third Place',
            self::Final => 'Final',
        };
    }

    public function isKnockout(): bool
    {
        return $this !== self::GroupStage;
    }
}
