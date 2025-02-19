<?php

namespace Meridiem;

/** Enumeration of months in the Gregorian calendar. */
enum Month: int
{
    case January = 1;
    case February = 2;
    case March = 3;
    case April = 4;
    case May = 5;
    case June = 6;
    case July = 7;
    case August = 8;
    case September = 9;
    case October = 10;
    case November = 11;
    case December = 12;

    /** Determine the number of days in the month in a given year. */
    public function dayCount(int $year = 0): int
    {
        return match($this) {
            self::April, self::June, self::September, self::November => 30,
            self::February => (0 === ($year % 4) && (0 !== ($year % 100) || 0 === ($year % 400))) ? 29 : 28,
            default => 31,
        };
    }

    /** Determine if this Month is before another in the year\. */
    public function isBefore(Month $month): bool
    {
        return $this->value < $month->value;
    }

    /** Determine if this Month is after another in the year. */
    public function isAfter(Month $month): bool
    {
        return $month->value < $this->value;
    }

    public function next(): Month
    {
        return self::from(1 + (($this->value) % 12));
    }

    public function previous(): Month
    {
        return self::from(1 +(($this->value + 10) % 12));
    }
}
