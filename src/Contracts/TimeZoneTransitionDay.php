<?php

namespace Meridiem\Contracts;

use Meridiem\Month;

interface TimeZoneTransitionDay
{
    public function dayFor(int $year, Month $month);
}
