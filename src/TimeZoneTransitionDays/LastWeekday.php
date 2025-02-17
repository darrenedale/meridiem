<?php

namespace Meridiem\TimeZoneTransitionDays;

use InvalidArgumentException;
use Meridiem\Contracts\TimeZoneTransitionDay;
use Meridiem\DateTime;
use Meridiem\Month;
use Meridiem\Weekday;

/** A transition day for when a time zone transitions on the last X of the month, where X is a Weekday. */
class LastWeekday implements TimeZoneTransitionDay
{
    private Weekday $weekday;

    public function __construct(Weekday $weekday)
    {
        $this->weekday = $weekday;
    }

    public function weekday(): Weekday
    {
        return $this->weekday;
    }

    public function dayFor(int $year, Month $month): int
    {
        assert(-9999 <= $year && 9999 >= $year, new InvalidArgumentException("Expected year bewteen -9999 and 9999, found {$year}"));

        // What weekday is the last of the month?
        $lastWeekday = DateTime::create($year, $month, $month->dayCount($year))->weekday();

        // How many days is it from the weekday we need to the weekday at the end of the month? Subtract that from the
        // number of days in the month and that's the date of the last weekday we need in the month
        return $month->dayCount($year) - $lastWeekday->distanceFrom($this->weekday());
    }
}
