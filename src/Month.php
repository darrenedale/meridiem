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

    /** Determine if this Month is before another in the year. */
    public function isBefore(Month $month): bool
    {
        return $this->value < $month->value;
    }

    /** Determine if this Month is after another in the year. */
    public function isAfter(Month $month): bool
    {
        return $month->value < $this->value;
    }

    /**
     * Fetch the month that is a number of months after this.
     *
     * @param int $months How many months to count forwards. Must be >= 0.
     */
    public function advance(int $months): Month
    {
        assert(0 <= $months, "Expected months >= 0, found {$months}");
        return self::from(1 + ($months + $this->value - 1) % 12);
    }

    /**
     * Fetch the month that is a number of months before this.
     *
     * @param int $months How many months to count backwards. Must be >= 0.
     */
    public function back(int $months): Month
    {
        assert(0 <= $months, "Expected months >= 0, found {$months}");
        return $this->advance(12 - ($months % 12));
    }

    /** Fetch the month after this. */
    public function next(): Month
    {
        return $this->advance(1);
    }

    /** Fetch the month before this. */
    public function previous(): Month
    {
        return $this->back(1);
    }

    /** Count the number of months forward from this month to another. */
    public function distanceTo(Month $month): int
    {
        if ($this->isAfter($month)) {
            return (12 + $month->value) - $this->value;
        }

        return $month->value - $this->value;
    }

    /** Count the number of months forward from another month to this month. */
    public function distanceFrom(Month $month): int
    {
        return $month->distanceTo($this);
    }
}
