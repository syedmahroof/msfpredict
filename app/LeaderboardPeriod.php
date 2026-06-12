<?php

namespace App;

enum LeaderboardPeriod: string
{
    case Global = 'global';
    case Daily = 'daily';
    case Weekly = 'weekly';
}
